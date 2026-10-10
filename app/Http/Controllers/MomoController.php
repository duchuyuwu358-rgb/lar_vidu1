<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MomoService;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Mail\OrderStatusUpdatedMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

class MomoController extends Controller
{
    private function sendOrderStatusEmail(Order $order)
    {
        $recipientEmail = $order->customer_email ?? $order->email ?? $order->user->email ?? null;

        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new OrderStatusUpdatedMail($order));
            } catch (\Exception $e) {
                Log::error("Lỗi gửi mail MoMo đơn hàng #{$order->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * HOÀN LẠI TỒN KHO NẾU GIAO DỊCH LỖI HOẶC HỦY
     */
    private function restoreStockIfCancelled(Order $order): void
    {
        if (in_array($order->status, ['cancelled', 'paid', 'processing', 'completed'])) {
            return;
        }

        DB::transaction(function () use ($order) {
            $items = $order->items ?? $order->orderDetails ?? $order->details ?? [];

            foreach ($items as $item) {
                $product  = $item->hood ?? $item->product ?? null;
                $quantity = $item->quantity ?? $item->qty ?? 1;

                if ($product && $quantity > 0) {
                    $attributes = $product->getAttributes();

                    if (array_key_exists('stock', $attributes)) {
                        $product->increment('stock', $quantity);
                    } elseif (array_key_exists('stock_quantity', $attributes)) {
                        $product->increment('stock_quantity', $quantity);
                    } elseif (array_key_exists('quantity', $attributes)) {
                        $product->increment('quantity', $quantity);
                    } else {
                        try {
                            $product->increment('stock', $quantity);
                        } catch (\Exception $e) {
                            Log::error("Lỗi hoàn kho sản phẩm ID {$product->id}: " . $e->getMessage());
                        }
                    }
                }
            }

            $order->update([
                'payment_status' => 'failed',
                'status'         => 'cancelled'
            ]);
        });
    }

    /**
     * Khởi tạo thanh toán MoMo Thẻ Visa/Mastercard
     */
    public function startPayment(Order $order, MomoService $momoService)
    {
        $amount = $order->total_amount ?? $order->total_price ?? $order->total ?? 0;

        $transaction = PaymentTransaction::create([
            'order_id'       => $order->id,
            'user_id'        => auth()->id() ?? $order->user_id,
            'gateway'        => 'momo',
            'payment_method' => 'momo_visa',
            'amount'         => $amount,
            'status'         => 'pending',
        ]);

        try {
            $result = $momoService->createPayment($order, $transaction);

            if (!empty($result['payUrl'])) {
                return redirect()->away($result['payUrl']);
            }

            $this->restoreStockIfCancelled($order);

            $showOrderRoute = Route::has('orders.show')
                ? route('orders.show', $order->id)
                : (Route::has('user.orders.show') ? route('user.orders.show', $order->id) : url("/orders/{$order->id}"));

            return redirect($showOrderRoute)->with('error', 'Lỗi MoMo Visa: ' . ($result['message'] ?? 'Không tạo được liên kết thanh toán.'));
        } catch (\Exception $e) {
            $this->restoreStockIfCancelled($order);
            return redirect()->back()->with('error', 'Lỗi khởi tạo thanh toán: ' . $e->getMessage());
        }
    }

    public function callback(Request $request, MomoService $momoService)
    {
        $payload = $request->all();
        Log::info('MoMo Callback Received:', $payload);

        $orderId = $momoService->orderId($payload);
        $order   = $orderId ? Order::find($orderId) : null;

        if (!$order) {
            return redirect('/orders')->with('error', 'Không tìm thấy thông tin đơn hàng.');
        }

        if (!Auth::check() && $order->user_id) {
            Auth::loginUsingId($order->user_id);
        }

        $transaction = null;
        if (!empty($payload['orderId'])) {
            $transaction = PaymentTransaction::where('gateway_order_id', $payload['orderId'])->first();
        }
        if (!$transaction) {
            $transaction = PaymentTransaction::where('order_id', $order->id)->latest()->first();
        }

        $resultCode       = isset($payload['resultCode']) ? (int)$payload['resultCode'] : -1;
        $isValidSignature = $momoService->isValidResponse($payload);

        $showOrderRoute = Route::has('orders.show')
            ? route('orders.show', $order->id)
            : (Route::has('user.orders.show') ? route('user.orders.show', $order->id) : url("/orders/{$order->id}"));

        if ($resultCode === 0 && $isValidSignature) {
            if ($transaction) {
                $momoService->markPaid($transaction, $payload);
            }

            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
            ]);

            session()->forget('cart');
            $this->sendOrderStatusEmail($order);

            return redirect($showOrderRoute)
                ->with('success', 'Thanh toán đơn hàng qua Visa thành công!');
        }

        if ($transaction) {
            $momoService->markFailed($transaction, $payload);
        }

        $this->restoreStockIfCancelled($order);

        return redirect($showOrderRoute)
            ->with('error', 'Thanh toán không thành công hoặc đã bị hủy!');
    }

    public function ipn(Request $request, MomoService $momoService)
    {
        $payload = $request->all();
        Log::info('MoMo IPN Received:', $payload);

        $orderId = $momoService->orderId($payload);
        $order   = $orderId ? Order::find($orderId) : null;

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $transaction = null;
        if (!empty($payload['orderId'])) {
            $transaction = PaymentTransaction::where('gateway_order_id', $payload['orderId'])->first();
        }
        if (!$transaction) {
            $transaction = PaymentTransaction::where('order_id', $order->id)->latest()->first();
        }

        $resultCode       = isset($payload['resultCode']) ? (int)$payload['resultCode'] : -1;
        $isValidSignature = $momoService->isValidResponse($payload);

        if ($resultCode === 0 && $isValidSignature) {
            if ($transaction) {
                $momoService->markPaid($transaction, $payload);
            }

            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
            ]);

            $this->sendOrderStatusEmail($order);

            return response()->json(['message' => 'Success'], 200);
        }

        if ($transaction) {
            $momoService->markFailed($transaction, $payload);
        }

        $this->restoreStockIfCancelled($order);

        return response()->json(['message' => 'Invalid signature or failed transaction'], 400);
    }
}