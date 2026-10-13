<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MomoService
{
    public function createPayment(Order $order, PaymentTransaction $transaction, string $requestType = 'captureWallet'): array
    {
        // Thông tin cố định
        $accessKey   = 'klm05TvNBzhg7h7j';
        $secretKey   = 'at67qH6mk8w5Y1nAyMoYKMwAC1E12bsa';
        $partnerCode = 'MOMOBKUN20180529';
        $endpoint    = 'https://test-payment.momo.vn/v2/gateway/api/create';
        $redirectUrl = 'https://lar-vidu1-kov7.onrender.com/payment/momo/callback';
        $ipnUrl      = 'https://lar-vidu1-kov7.onrender.com/payment/momo/ipn';

        // Thông tin đơn hàng
        $rawAmount = $transaction->amount > 0 ? $transaction->amount : ($order->total_amount ?? $order->total_price ?? $order->total ?? 0);
        $intAmount = (int) round($rawAmount);
        if ($intAmount < 1000) $intAmount = 10000; 
        $strAmount = (string) $intAmount;

        $rawCode   = $order->order_code ?? (string)$order->id;
        $cleanCode = preg_replace('/[^a-zA-Z0-9-]/', '', $rawCode);
        $orderInfo = 'Thanh toan don hang ' . $cleanCode;

        $time      = time();
        $requestId = (string) $time;
        $orderId   = $order->id . 'T' . $transaction->id . 'T' . $time;
        $extraData = ""; 

        // 1. Tạo chuỗi băm HMAC SHA256 chuẩn
        $rawHash = "accessKey={$accessKey}&amount={$strAmount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";

        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        // 2. Mảng dữ liệu gửi đi (Truyền trực tiếp chuỗi 'klm05TvNBzhg7h7j')
        $data = [
            'partnerCode' => $partnerCode,
            'accessKey'   => 'klm05TvNBzhg7h7j', // BẮT BUỘC KHÔNG THAY ĐỔI
            'partnerName' => 'XFAN Store',
            'storeId'     => 'XFANStore',
            'requestId'   => $requestId,
            'amount'      => $intAmount,
            'orderId'     => $orderId,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl'      => $ipnUrl,
            'lang'        => 'vi',
            'extraData'   => $extraData,
            'requestType' => $requestType,
            'signature'   => $signature,
        ];

        $transaction->update([
            'gateway_order_id' => $orderId,
            'request_payload'  => $data,
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
            ])->post($endpoint, $data);

            $result = $response->json() ?? [];

            Log::info("MoMo Response:", $result);

            $transaction->update([
                'response_payload' => $result,
                'result_code'      => isset($result['resultCode']) ? (int) $result['resultCode'] : null,
                'message'          => $result['message'] ?? null,
                'status'           => !empty($result['payUrl']) ? 'initiated' : 'failed',
            ]);

            return $result;
        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed', 'message' => $e->getMessage()]);
            return ['resultCode' => -1, 'message' => 'Lỗi kết nối tới MoMo: ' . $e->getMessage()];
        }
    }

    public function isSuccessful(array $payload): bool { return isset($payload['resultCode']) && (string) $payload['resultCode'] === '0'; }
    public function markPaid(PaymentTransaction $transaction, array $payload): void { /*...*/ }
    public function markFailed(PaymentTransaction $transaction, array $payload): void { /*...*/ }
    public function isValidResponse(array $payload): bool { return true; }
    public function orderId(array $payload): ?int
    {
        if (!empty($payload['orderId'])) {
            $parts = explode('T', $payload['orderId']);
            if (isset($parts[0]) && is_numeric($parts[0])) return (int) $parts[0];
        }
        return null;
    }
}