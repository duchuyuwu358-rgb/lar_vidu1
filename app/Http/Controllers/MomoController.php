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
        $recipientEmail = $order->email ?? $order->user->email ?? null;

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
        $transaction = PaymentTransaction::create([
            'order_id'       => $order->id,
            'user_id'        => auth()->id() ?? $order->user_id,
            'gateway'        => 'momo',
            'payment_method' => 'momo',
            'amount'         => $order->total_price ?? $order->total_amount ?? $order->total ?? 0,
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

        // TỰ ĐỘNG KHÔI PHỤC SESSION ĐĂNG NHẬP CHO KHÁCH HÀNG NẾU MẤT COOKIE KHI REDIRECT TỪ MOMO
        if (!Auth::check() && $order->user_id) {
            Auth::loginUsingId($order->user_id);
        }

        $transaction = PaymentTransaction::where('order_id', $order->id)->latest()->first();

        $resultCode       = isset($payload['resultCode']) ? (int)$payload['resultCode'] : -1;
        $isValidSignature = $momoService->isValidSuccessfulResponse($payload);
        $transId          = $payload['transId'] ?? null;

        // Xác định đường dẫn hiển thị chi tiết đơn hàng an toàn
        $showOrderRoute = Route::has('orders.show')
            ? route('orders.show', $order->id)
            : (Route::has('user.orders.show') ? route('user.orders.show', $order->id) : url("/orders/{$order->id}"));

        if ($resultCode === 0 && $isValidSignature) {
            if ($transaction) {
                $momoService->markPaid($transaction, $payload);
            }

            $updateData = [
                'payment_status' => 'paid',
                'status'         => 'processing',
            ];

            if ($transId) {
                $updateData['momo_transaction_id'] = $transId;
                $updateData['transaction_id']      = $transId;
            }

            $order->update($updateData);

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

        $transaction = PaymentTransaction::where('order_id', $order->id)->latest()->first();

        $resultCode       = isset($payload['resultCode']) ? (int)$payload['resultCode'] : -1;
        $isValidSignature = $momoService->isValidSuccessfulResponse($payload);
        $transId          = $payload['transId'] ?? null;

        if ($resultCode === 0 && $isValidSignature) {
            if ($transaction) {
                $momoService->markPaid($transaction, $payload);
            }

            $updateData = [
                'payment_status' => 'paid',
                'status'         => 'processing',
            ];

            if ($transId) {
                $updateData['momo_transaction_id'] = $transId;
                $updateData['transaction_id']      = $transId;
            }

            $order->update($updateData);

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