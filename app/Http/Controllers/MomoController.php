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

class MomoController extends Controller
{
    /**
     * Gửi Gmail thông báo cập nhật đơn hàng
     */
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
     * Khởi tạo thanh toán MoMo
     */
    public function startPayment(Order $order, MomoService $momoService)
    {
        // Tính tiền chuẩn xác từ đơn hàng
        $amount = $order->total_amount ?? $order->total_price ?? $order->total ?? 0;

        $transaction = PaymentTransaction::create([
            'order_id'       => $order->id,
            'user_id'        => auth()->id() ?? $order->user_id,
            'gateway'        => 'momo',
            'payment_method' => 'momo',
            'amount'         => $amount,
            'status'         => 'pending',
        ]);

        try {
            $result = $momoService->createPayment($order, $transaction);

            if (isset($result['payUrl'])) {
                return redirect()->away($result['payUrl']);
            }

            return redirect()->back()->with('error', $result['message'] ?? 'Không thể khởi tạo giao dịch MoMo.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi khởi tạo thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Xử lý khi MoMo chuyển hướng khách hàng quay lại website
     */
    public function callback(Request $request, MomoService $momoService)
    {
        $payload = $request->all();
        Log::info('MoMo Callback Received:', $payload);

        $orderId = $momoService->orderId($payload);
        $order   = $orderId ? Order::find($orderId) : null;

        if (!$order) {
            Log::error('MoMo Callback Error: Order not found.', ['payload' => $payload]);
            return redirect('/orders')->with('error', 'Không tìm thấy thông tin đơn hàng.');
        }

        // Tự động khôi phục phiên đăng nhập nếu mất Cookie khi redirect từ MoMo
        if (!Auth::check() && $order->user_id) {
            Auth::loginUsingId($order->user_id);
        }

        // Tìm đúng bản ghi giao dịch theo gateway_order_id hoặc order_id
        $transaction = null;
        if (!empty($payload['orderId'])) {
            $transaction = PaymentTransaction::where('gateway_order_id', $payload['orderId'])->first();
        }
        if (!$transaction) {
            $transaction = PaymentTransaction::where('order_id', $order->id)->latest()->first();
        }

        $resultCode       = isset($payload['resultCode']) ? (int)$payload['resultCode'] : -1;
        $isValidSignature = $momoService->isValidSuccessfulResponse($payload);

        // Đường dẫn trả về giao diện đơn hàng an toàn
        $showOrderRoute = Route::has('orders.show')
            ? route('orders.show', $order->id)
            : (Route::has('user.orders.show') ? route('user.orders.show', $order->id) : url("/orders/{$order->id}"));

        if ($resultCode === 0 && $isValidSignature) {
            if ($transaction) {
                $momoService->markPaid($transaction, $payload);
            }

            // Cập nhật trạng thái đơn hàng (Chỉ cập nhật các trường có sẵn trong CSDL)
            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
            ]);

            // Xóa giỏ hàng khỏi Session
            session()->forget('cart');

            // Gửi Mail thông báo đơn hàng
            $this->sendOrderStatusEmail($order);

            return redirect($showOrderRoute)
                ->with('success', 'Thanh toán đơn hàng qua MoMo thành công! Email xác nhận đã được gửi đến hộp thư của bạn.');
        }

        // Trường hợp Thanh toán thất bại hoặc Hủy giao dịch
        if ($transaction) {
            $momoService->markFailed($transaction, $payload);
        }

        $order->update([
            'payment_status' => 'failed',
            'status'         => 'cancelled'
        ]);

        return redirect($showOrderRoute)
            ->with('error', 'Thanh toán không thành công hoặc đã bị hủy!');
    }

    /**
     * Xử lý thông báo ngầm (IPN) từ server MoMo -> Server của bạn
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
        $isValidSignature = $momoService->isValidSuccessfulResponse($payload);

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

        $order->update([
            'payment_status' => 'failed',
            'status'         => 'cancelled'
        ]);

        return response()->json(['message' => 'Invalid signature or failed transaction'], 400);
    }
}