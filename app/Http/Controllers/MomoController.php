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
     * SỬA LỖI TẬN GỐC: TỰ ĐỘNG CỘNG TRẢ LẠI TỒN KHO NẾU HỦY HOẶC THANH TOÁN THẤT BẠI
     */
    private function restoreStockIfCancelled(Order $order): void
    {
        // 1. Chỉ không hoàn kho nếu đơn ĐÃ THANH TOÁN THÀNH CÔNG hoặc ĐÃ HOÀN THÀNH
        if (in_array($order->status, ['paid', 'processing', 'completed'])) {
            return;
        }

        // 2. Tránh cộng lặp 2 lần nếu đơn hàng này ĐÃ ĐƯỢC XỬ LÝ HỦY & THẤT BẠI TRƯỚC ĐÓ
        if ($order->payment_status === 'failed' && $order->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($order) {
            // Lấy tất cả các quan hệ chi tiết đơn hàng có thể có
            $items = $order->items 
                ?? $order->orderDetails 
                ?? $order->details 
                ?? $order->orderItems 
                ?? [];

            foreach ($items as $item) {
                // Lấy Model Sản phẩm (hỗ trợ cả hood, product, service)
                $product  = $item->hood ?? $item->product ?? $item->service ?? null;
                $quantity = $item->quantity ?? $item->qty ?? 1;

                if ($product && $quantity > 0) {
                    $attributes = method_exists($product, 'getAttributes') ? $product->getAttributes() : [];

                    // Kiểm tra và cộng đúng tên cột tồn kho trong CSDL
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

            // Đánh dấu đơn hàng là ĐÃ HỦY sau khi đã hoàn lại số lượng tồn kho thành công
            $order->update([
                'payment_status' => 'failed',
                'status'         => 'cancelled'
            ]);
        });
    }

    /**
     * Khởi tạo thanh toán MoMo
     */
    public function startPayment(Order $order, MomoService $momoService)
    {
        $amount = $order->total_amount ?? $order->total_price ?? $order->total ?? 0;

        $transaction = PaymentTransaction::create([
            'order_id'       => $order->id,
            'user_id'        => auth()->id() ?? $order->user_id,
            'gateway'        => 'momo',
            'payment_method' => 'momo_wallet',
            'amount'         => $amount,
            'status'         => 'pending',
        ]);

        try {
            $result = $momoService->createPayment($order, $transaction);

            // NẾU CÓ PAYURL -> CHUYỂN HƯỚNG TỚI CỔNG MOMO
            if (!empty($result['payUrl'])) {
                return redirect()->away($result['payUrl']);
            }

            // KHÔNG TẠO ĐƯỢC PAYURL -> HOÀN TỒN KHO VÀ TRẢ VỀ TRANG XEM ĐƠN HÀNG
            $this->restoreStockIfCancelled($order);

            $showOrderRoute = Route::has('orders.show')
                ? route('orders.show', $order->id)
                : (Route::has('user.orders.show') ? route('user.orders.show', $order->id) : url("/orders/{$order->id}"));

            return redirect($showOrderRoute)->with('error', 'Lỗi MoMo: ' . ($result['message'] ?? 'Không thể khởi tạo thanh toán.'));
        } catch (\Exception $e) {
            $this->restoreStockIfCancelled($order);
            return redirect()->back()->with('error', 'Lỗi khởi tạo thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Callback khi khách hàng hoàn tất hoặc hủy thanh toán trên MoMo
     */
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

        // THANH TOÁN THÀNH CÔNG
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
                ->with('success', 'Thanh toán đơn hàng qua MoMo thành công!');
        }

        // THANH TOÁN THẤT BẠI HOẶC HỦY -> HOÀN TỒN KHO NGAY LẬP TỨC
        if ($transaction) {
            $momoService->markFailed($transaction, $payload);
        }

        $this->restoreStockIfCancelled($order);

        return redirect($showOrderRoute)
            ->with('error', 'Thanh toán không thành công hoặc đã bị hủy!');
    }

    /**
     * Xử lý thông báo ngầm IPN từ MoMo
     */
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