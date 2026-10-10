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
use Illuminate\Support\Facades\Schema;

class MomoController extends Controller
{
    private function sendOrderStatusEmail(Order $order)
    {
        $recipientEmail =$order->customer_email ?? $order->email ?? $order->user->email ?? null;

        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new OrderStatusUpdatedMail($order));
            } catch (\Exception $e) {
                Log::error("Lỗi gửi mail MoMo đơn hàng #{$order->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * HOÀN TỒN KHO AN TOÀN TUYỆT ĐỐI BẰNG TRUY VẤN SQL TRỰC TIẾP (DIRECT DATABASE QUERY)
     */
    private function restoreStockIfCancelled(Order $order): void
    {
        // 1. Bỏ qua nếu đơn đã thanh toán thành công hoặc đang xử lý giao hàng
        if (in_array(strtolower($order->status), ['paid', 'processing', 'completed'])) {
            return;
        }

        // 2. Tránh hoàn kho lặp 2 lần nếu đơn đã hủy từ trước
        if ($order->payment_status === 'failed' &&$order->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($order) {
            // Lấy trực tiếp danh sách chi tiết đơn hàng từ CSDL
            $items = collect();
            if (Schema::hasTable('order_details')) {
                $items = DB::table('order_details')->where('order_id',$order->id)->get();
            }
            if ($items->isEmpty() && Schema::hasTable('order_items')) {
                $items = DB::table('order_items')->where('order_id',$order->id)->get();
            }
            if ($items->isEmpty() && Schema::hasTable('details')) {
                $items = DB::table('details')->where('order_id',$order->id)->get();
            }

            // Fallback sang Eloquent nếu chưa lấy được
            if ($items->isEmpty()) {
                if ($order->items &&$order->items->isNotEmpty()) {
                    $items =$order->items;
                } elseif ($order->orderDetails &&$order->orderDetails->isNotEmpty()) {
                    $items =$order->orderDetails;
                } elseif ($order->details &&$order->details->isNotEmpty()) {
                    $items =$order->details;
                }
            }

            foreach ($items as$item) {
                $itemArr  = (array)$item;
                $quantity = (int) ($itemArr['quantity'] ?? $itemArr['qty'] ?? 1);

                $hoodId    =$itemArr['hood_id'] ?? null;
                $productId = $itemArr['product_id'] ?? $itemArr['service_id'] ?? null;

                // 1. Cộng trả lại tồn kho bảng `hoods`
                if ($hoodId && Schema::hasTable('hoods')) {
                    if (Schema::hasColumn('hoods', 'stock')) {
                        DB::table('hoods')->where('id', $hoodId)->increment('stock',$quantity);
                    } elseif (Schema::hasColumn('hoods', 'stock_quantity')) {
                        DB::table('hoods')->where('id', $hoodId)->increment('stock_quantity',$quantity);
                    }
                }

                // 2. Cộng trả lại tồn kho bảng `products`
                if ($productId && Schema::hasTable('products')) {
                    if (Schema::hasColumn('products', 'stock')) {
                        DB::table('products')->where('id', $productId)->increment('stock',$quantity);
                    } elseif (Schema::hasColumn('products', 'stock_quantity')) {
                        DB::table('products')->where('id', $productId)->increment('stock_quantity',$quantity);
                    }
                }
            }

            // Cập nhật trạng thái đơn hàng thành Hủy & Thanh toán thất bại
            DB::table('orders')->where('id', $order->id)->update([
                'payment_status' => 'failed',
                'status'         => 'cancelled',
                'updated_at'     => now(),
            ]);
        });
    }

    /**
     * Khởi tạo thanh toán MoMo Thẻ Visa / Mastercard
     */
    public function startPayment(Order $order, MomoService$momoService)
    {
        $amount =$order->total_amount ?? $order->total_price ?? $order->total ?? 0;

        $transaction = PaymentTransaction::create([
            'order_id'       => $order->id,
            'user_id'        => auth()->id() ?? $order->user_id,
            'gateway'        => 'momo',
            'payment_method' => 'momo_visa',
            'amount'         => $amount,
            'status'         => 'pending',
        ]);

        try {
            $result =$momoService->createPayment($order,$transaction);

            // CÓ PAYURL -> CHUYỂN HƯỚNG SANG MOMO
            if (!empty($result['payUrl'])) {
                return redirect()->away($result['payUrl']);
            }

            // LỖI LẤY PAYURL -> TỰ ĐỘNG HOÀN KHO & CHUYỂN VỀ TRANG ĐƠN HÀNG
            $this->restoreStockIfCancelled($order);

            $showOrderRoute = Route::has('orders.show')
                ? route('orders.show', $order->id)
                : (Route::has('user.orders.show') ? route('user.orders.show', $order->id) : url("/orders/{$order->id}"));

            return redirect($showOrderRoute)->with('error', 'Lỗi MoMo Visa: ' . ($result['message'] ?? 'Không thể khởi tạo thanh toán.'));
        } catch (\Exception $e) {
            $this->restoreStockIfCancelled($order);
            return redirect()->back()->with('error', 'Lỗi khởi tạo thanh toán: ' . $e->getMessage());
        }
    }

    public function callback(Request $request, MomoService$momoService)
    {
        $payload =$request->all();
        Log::info('MoMo Callback Received:', $payload);

        $orderId =$momoService->orderId($payload);$order   = $orderId ? Order::find($orderId) : null;

        if (!$order) {
            return redirect('/orders')->with('error', 'Không tìm thấy thông tin đơn hàng.');
        }

        if (!Auth::check() && $order->user_id) {
            Auth::loginUsingId($order->user_id);
        }

        $transaction = null;
        if (!empty($payload['orderId'])) {
            $transaction = PaymentTransaction::where('gateway_order_id',$payload['orderId'])->first();
        }
        if (!$transaction) {
            $transaction = PaymentTransaction::where('order_id',$order->id)->latest()->first();
        }

        $resultCode       = isset($payload['resultCode']) ? (int)$payload['resultCode'] : -1;
        $isValidSignature = $momoService->isValidResponse($payload);

        $showOrderRoute = Route::has('orders.show')
            ? route('orders.show', $order->id)
            : (Route::has('user.orders.show') ? route('user.orders.show', $order->id) : url("/orders/{$order->id}"));

        if ($resultCode === 0 &&$isValidSignature) {
            if ($transaction) {$momoService->markPaid($transaction,$payload);
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

        if ($transaction) {$momoService->markFailed($transaction,$payload);
        }

        $this->restoreStockIfCancelled($order);

        return redirect($showOrderRoute)
            ->with('error', 'Thanh toán không thành công hoặc đã bị hủy!');
    }

    public function ipn(Request $request, MomoService$momoService)
    {
        $payload =$request->all();
        Log::info('MoMo IPN Received:', $payload);

        $orderId =$momoService->orderId($payload);$order   = $orderId ? Order::find($orderId) : null;

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $transaction = null;
        if (!empty($payload['orderId'])) {
            $transaction = PaymentTransaction::where('gateway_order_id',$payload['orderId'])->first();
        }
        if (!$transaction) {
            $transaction = PaymentTransaction::where('order_id',$order->id)->latest()->first();
        }

        $resultCode       = isset($payload['resultCode']) ? (int)$payload['resultCode'] : -1;
        $isValidSignature = $momoService->isValidResponse($payload);

        if ($resultCode === 0 &&$isValidSignature) {
            if ($transaction) {$momoService->markPaid($transaction,$payload);
            }

            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
            ]);

            $this->sendOrderStatusEmail($order);

            return response()->json(['message' => 'Success'], 200);
        }

        if ($transaction) {$momoService->markFailed($transaction,$payload);
        }

        $this->restoreStockIfCancelled($order);

        return response()->json(['message' => 'Invalid signature or failed transaction'], 400);
    }
}