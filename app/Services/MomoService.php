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
     * Lấy cấu hình MoMo Sandbox API v2 chuẩn chính thức
     */
    protected function getConfigs(): array
    {
        $partnerCode = config('services.momo.partner_code', 'MOMO');
        $accessKey   = config('services.momo.access_key', 'F8BBA842ECF82');
        $secretKey   = config('services.momo.secret_key', 'K951B6PE1waDMi640xX08332A9UWE15i');

        if ($partnerCode === 'MOMOBKUN20180529' || empty($partnerCode)) {
            $partnerCode = 'MOMO';
            $accessKey   = 'F8BBA842ECF82';
            $secretKey   = 'K951B6PE1waDMi640xX08332A9UWE15i';
        }

        return [
            'endpoint'    => 'https://test-payment.momo.vn/v2/gateway/api/create',
            'partnerCode' => $partnerCode,
            'accessKey'   => $accessKey,
            'secretKey'   => $secretKey,
            'verifySsl'   => false,
        ];
    }

    /**
     * Khởi tạo thanh toán MoMo Gateway (captureWallet)
     */
    public function createPayment(Order $order, PaymentTransaction $transaction, string $requestType = 'captureWallet'): array
    {
        $cfg = $this->getConfigs();

        $rawAmount = $transaction->amount > 0 
            ? $transaction->amount 
            : ($order->total_amount ?? $order->total_price ?? $order->total ?? 0);
        
        $intAmount = (int) round($rawAmount);
        $strAmount = (string) $intAmount;

        $rawCode   = $order->order_code ?? (string)$order->id;
        $cleanCode = preg_replace('/[^a-zA-Z0-9-]/', '', $rawCode);
        $orderInfo = 'Thanh toan don hang ' . $cleanCode;
        $orderId   = $order->id . '_' . $transaction->id . '_' . time();

        $redirectUrl = config('services.momo.redirect_url');
        $ipnUrl      = config('services.momo.ipn_url');

        if (empty($redirectUrl) || empty($ipnUrl) || str_contains($redirectUrl, '127.0.0.1')) {
            $host          = request()->getHost();
            $scheme        = (request()->secure() || app()->environment('production') || str_contains($host, 'onrender.com')) ? 'https' : request()->getScheme();
            $currentDomain = $scheme . '://' . $host;

            $redirectUrl = $currentDomain . '/payment/momo/callback';
            $ipnUrl      = $currentDomain . '/payment/momo/ipn';
        }

        $extraData = ""; 
        $requestId = (string) time();

        // Chuỗi mã hóa HMAC SHA256 chuẩn MoMo API v2
        $rawHash = "accessKey={$cfg['accessKey']}&amount={$strAmount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$cfg['partnerCode']}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";

        $signature = hash_hmac('sha256', $rawHash, $cfg['secretKey']);

        $data = [
            'partnerCode' => $cfg['partnerCode'],
            'accessKey'   => $cfg['accessKey'],
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
            ])->withOptions([
                'verify' => $cfg['verifySsl'],
            ])->post($cfg['endpoint'], $data);

            $result = $response->json() ?? [];

            Log::info('MoMo RawHash: ' . $rawHash);
            Log::info('MoMo Response:', $result);

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

    public function isValidSuccessfulResponse(array $payload): bool
    {
        return $this->isSuccessful($payload) && $this->isValidResponse($payload);
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
            $parts = explode('_', $payload['orderId']);
            if (isset($parts[0]) && is_numeric($parts[0])) {
                return (int) $parts[0];
            }
        }

        return null;
    }
}