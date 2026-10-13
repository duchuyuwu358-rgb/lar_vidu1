<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MomoService
{
    /**
     * Lấy cấu hình MoMo từ config/services.php
     */
    protected function getConfigs(): array
    {
        $config = config('services.momo', []);

        return [
            'endpoint'    => $config['endpoint'] ?? 'https://test-payment.momo.vn/v2/gateway/api/create',
            'partnerCode' => $config['partner_code'] ?? 'MOMO',
            'accessKey'   => $config['access_key'] ?? 'F8BBA842ECF85', 
            'secretKey'   => $config['secret_key'] ?? 'K951B6PE1waDMi640xX08PD3vg6EkVlz',
            'redirectUrl' => $config['redirect_url'] ?? 'https://lar-vidu1-kov7.onrender.com/payment/momo/callback',
            'ipnUrl'      => $config['ipn_url'] ?? 'https://lar-vidu1-kov7.onrender.com/payment/momo/ipn',
            'requestType' => $config['request_type'] ?? 'captureWallet',
            'verifySsl'   => $config['verify_ssl'] ?? false,
        ];
    }

    /**
     * Tạo yêu cầu thanh toán MoMo Sandbox
     */
    public function createPayment(Order $order, PaymentTransaction $transaction, ?string $requestType = null): array
    {
        $cfg  = $this->getConfigs();
        $type = $requestType ?? $cfg['requestType'];

        $rawAmount = $transaction->amount > 0 
            ? $transaction->amount 
            : ($order->total_amount ?? $order->total_price ?? $order->total ?? 0);
        
        $intAmount = (int) round($rawAmount);
        if ($intAmount < 1000) {
            $intAmount = 10000; 
        }
        $strAmount = (string) $intAmount;

        $rawCode   = $order->order_code ?? (string)$order->id;
        $cleanCode = preg_replace('/[^a-zA-Z0-9-]/', '', $rawCode);
        $orderInfo = 'Thanh toan don hang ' . $cleanCode;

        $time      = time();
        $requestId = (string) $time;
        $orderId   = $order->id . 'T' . $transaction->id . 'T' . $time;
        $extraData = ""; 

        // Chuỗi mã hóa HMAC SHA256 theo thứ tự chữ cái (A-Z) của MoMo API V2
        $rawHash = "accessKey={$cfg['accessKey']}&amount={$strAmount}&extraData={$extraData}&ipnUrl={$cfg['ipnUrl']}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$cfg['partnerCode']}&redirectUrl={$cfg['redirectUrl']}&requestId={$requestId}&requestType={$type}";

        $signature = hash_hmac('sha256', $rawHash, $cfg['secretKey']);

        // Payload gửi sang MoMo API V2
        $data = [
            'partnerCode' => $cfg['partnerCode'],
            'accessKey'   => $cfg['accessKey'],
            'requestId'   => $requestId,
            'amount'      => $intAmount,
            'orderId'     => $orderId,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $cfg['redirectUrl'],
            'ipnUrl'      => $cfg['ipnUrl'],
            'lang'        => 'vi',
            'extraData'   => $extraData,
            'requestType' => $type,
            'signature'   => $signature,
        ];

        $transaction->update([
            'gateway_order_id' => $orderId,
            'request_payload'  => $data,
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
            ])->withOptions([
                'verify' => $cfg['verifySsl'],
            ])->post($cfg['endpoint'], $data);

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
            Log::error('MoMo Create Payment Exception: ' . $e->getMessage());
            $transaction->update(['status' => 'failed', 'message' => $e->getMessage()]);
            return ['resultCode' => -1, 'message' => 'Lỗi kết nối tới MoMo: ' . $e->getMessage()];
        }
    }

    public function isSuccessful(array $payload): bool
    {
        return isset($payload['resultCode']) && (string) $payload['resultCode'] === '0';
    }

    public function markPaid(PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'transaction_id'   => $payload['transId'] ?? null,
            'result_code'      => (int) ($payload['resultCode'] ?? 0),
            'message'          => $payload['message'] ?? 'Thanh toán thành công',
            'response_payload' => $payload,
            'status'           => 'paid',
            'paid_at'          => Carbon::now(),
        ]);
    }

    public function markFailed(PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'transaction_id'   => $payload['transId'] ?? null,
            'result_code'      => isset($payload['resultCode']) ? (int) $payload['resultCode'] : null,
            'message'          => $payload['message'] ?? 'Thanh toán thất bại',
            'response_payload' => $payload,
            'status'           => 'failed',
        ]);
    }

    public function isValidResponse(array $payload): bool
    {
        if (!isset($payload['signature'])) {
            return false;
        }

        $cfg       = $this->getConfigs();
        $accessKey = $cfg['accessKey'];
        $secretKey = $cfg['secretKey'];

        $amount       = (string) ($payload['amount'] ?? '');
        $extraData    = (string) ($payload['extraData'] ?? '');
        $message      = (string) ($payload['message'] ?? '');
        $orderId      = (string) ($payload['orderId'] ?? '');
        $orderInfo    = (string) ($payload['orderInfo'] ?? '');
        $orderType    = (string) ($payload['orderType'] ?? '');
        $partnerCode  = (string) ($payload['partnerCode'] ?? '');
        $payType      = (string) ($payload['payType'] ?? '');
        $requestId    = (string) ($payload['requestId'] ?? '');
        $responseTime = (string) ($payload['responseTime'] ?? '');
        $resultCode   = (string) ($payload['resultCode'] ?? '');
        $transId      = (string) ($payload['transId'] ?? '');

        $rawHash = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&message={$message}&orderId={$orderId}&orderInfo={$orderInfo}&orderType={$orderType}&partnerCode={$partnerCode}&payType={$payType}&requestId={$requestId}&responseTime={$responseTime}&resultCode={$resultCode}&transId={$transId}";

        $expectedSignature = hash_hmac('sha256', $rawHash, $secretKey);

        return hash_equals($expectedSignature, (string) $payload['signature']);
    }

    public function orderId(array $payload): ?int
    {
        if (!empty($payload['extraData']) && is_numeric($payload['extraData'])) {
            return (int) $payload['extraData'];
        }

        if (!empty($payload['orderId'])) {
            $parts = explode('T', $payload['orderId']);
            if (isset($parts[0]) && is_numeric($parts[0])) {
                return (int) $parts[0];
            }
        }

        return null;
    }
}