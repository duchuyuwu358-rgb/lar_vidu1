<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MomoService;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;

class MomoController extends Controller
{
    // Khởi tạo thanh toán MoMo và chuyển hướng người dùng sang trang MoMo
    public function startPayment(Order $order, MomoService $momoService)
    {
        // Tạo bản ghi giao dịch trong cơ sở dữ liệu
        $transaction = PaymentTransaction::create([
            'order_id'       => $order->id,
            'user_id'        => auth()->id() ?? $order->user_id,
            'gateway'        => 'momo',
            'payment_method' => 'momo',
            'amount'         => $order->total_price,
            'status'         => 'pending',
        ]);

        try {
            // Gọi MomoService để lấy link thanh toán
            $result = $momoService->createPayment($order, $transaction);

            // Chuyển hướng sang giao diện thanh toán MoMo
            if (isset($result['payUrl'])) {
                return redirect()->away($result['payUrl']);
            }

            return redirect()->back()->with('error', $result['message'] ?? 'Không thể khởi tạo giao dịch MoMo.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi khởi tạo thanh toán: ' . $e->getMessage());
        }
    }

    // Xử lý khi MoMo chuyển hướng trình duyệt của khách hàng về lại website
    public function callback(Request $request, MomoService $momoService)
    {
        $payload = $request->all();

        Log::info('MoMo Callback Received:', $payload);

        // Lấy Order ID từ MomoService
        $orderId = $momoService->orderId($payload);
        $order   = $orderId ? Order::find($orderId) : null;

        if (!$order) {
            Log::error('MoMo Callback Error: Order not found.', ['payload' => $payload]);
            return redirect('/orders')->with('error', 'Không tìm thấy thông tin đơn hàng.');
        }

        $transaction = PaymentTransaction::where('order_id', $order->id)->latest()->first();

        // Kiểm tra resultCode = 0 (Thành công) và Chữ ký hợp lệ
        $resultCode       = isset($payload['resultCode']) ? (int)$payload['resultCode'] : -1;
        $isValidSignature = $momoService->isValidSuccessfulResponse($payload);

        if ($resultCode === 0 && $isValidSignature) {
            if ($transaction) {
                $momoService->markPaid($transaction, $payload);
            }

            // Cập nhật trạng thái đơn hàng
            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
            ]);

            // Xóa giỏ hàng sau khi thanh toán thành công
            session()->forget('cart');

            return redirect('/orders')->with('success', 'Thanh toán đơn hàng qua MoMo thành công!');
        }

        // Trường hợp resultCode != 0 hoặc chữ ký không hợp lệ
        Log::warning('MoMo Payment Failed or Invalid Signature', [
            'order_id'   => $order->id,
            'resultCode' => $resultCode,
            'is_valid'   => $isValidSignature
        ]);

        if ($transaction) {
            $momoService->markFailed($transaction, $payload);
        }

        $order->update([
            'payment_status' => 'failed',
            'status'         => 'cancelled'
        ]);

        return redirect('/cart')->with('error', 'Thanh toán không thành công hoặc đã bị hủy. Giỏ hàng của bạn vẫn được giữ nguyên!');
    }

    // Xử lý thông báo ngầm từ máy chủ MoMo (IPN)
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

        if ($resultCode === 0 && $isValidSignature) {
            if ($transaction) {
                $momoService->markPaid($transaction, $payload);
            }

            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
            ]);

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