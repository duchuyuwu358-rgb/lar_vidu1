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
     * Làm sạch giá trị cấu hình (Loại bỏ khoảng trắng, ngoặc kép, nháy đơn)
     */
    private function cleanConfigValue(?string $value, string $default): string
    {
        if (empty($value)) {
            return $default;
        }
        $cleaned = trim($value, " \t\n\r\0\x0B\"'");
        return !empty($cleaned) ? $cleaned : $default;
    }

    /**
     * Lấy cấu hình MoMo (Đã xử lý làm sạch Key tuyệt đối)
     */
    protected function getConfigs(): array
    {
        $partnerCode = $this->cleanConfigValue(config('services.momo.partner_code'), 'MOMOBKUN20180529');
        $accessKey   = $this->cleanConfigValue(config('services.momo.access_key'), 'klm99x0Za7RdUODe');
        $secretKey   = $this->cleanConfigValue(config('services.momo.secret_key'), 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa');

        if ($accessKey === 'klm05TvNBzhg7h7j') {
            $accessKey = 'klm99x0Za7RdUODe';
        }

        if (str_contains(strtoupper($partnerCode), 'MONO')) {
            $partnerCode = 'MOMOBKUN20180529';
        }

        return [
            'endpoint'     => $this->cleanConfigValue(config('services.momo.endpoint'), 'https://test-payment.momo.vn/v2/gateway/api/create'),
            'partnerCode'  => $partnerCode,
            'accessKey'    => $accessKey,
            'secretKey'    => $secretKey,
            'verifySsl'    => filter_var(config('services.momo.verify_ssl', false), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Khởi tạo giao dịch thanh toán MoMo
     */
    public function createPayment(Order $order, PaymentTransaction $transaction, string $requestType = 'captureWallet'): array
    {
        $cfg = $this->getConfigs();

        $rawAmount = $transaction->amount > 0 
            ? $transaction->amount 
            : ($order->total_amount ?? $order->total_price ?? $order->total ?? 0);
        $amount = (string) (int) round($rawAmount);

        $rawCode   = $order->order_code ?? (string)$order->id;
        $cleanCode = preg_replace('/[^a-zA-Z0-9-]/', '', $rawCode);
        $orderInfo = 'Thanh toan don hang ' . $cleanCode;
        $orderId   = $order->id . '_' . $transaction->id . '_' . time();

        // Tự động lấy domain thực tế từ Request
        $host   = request()->getHost();
        $scheme = (request()->secure() || app()->environment('production') || str_contains($host, 'onrender.com')) ? 'https' : request()->getScheme();
        $currentDomain = $scheme . '://' . $host;

        $envRedirect = $this->cleanConfigValue(config('services.momo.redirect_url'), '');
        $envIpn      = $this->cleanConfigValue(config('services.momo.ipn_url'), '');

        $redirectUrl = (!empty($envRedirect) && str_contains($envRedirect, $host)) 
            ? $envRedirect 
            : $currentDomain . '/payment/momo/callback';

        $ipnUrl = (!empty($envIpn) && str_contains($envIpn, $host)) 
            ? $envIpn 
            : $currentDomain . '/payment/momo/ipn';

        if (app()->environment('production') || str_contains($host, 'onrender.com')) {
            $redirectUrl = str_replace('http://', 'https://', $redirectUrl);
            $ipnUrl      = str_replace('http://', 'https://', $ipnUrl);
        }

        $extraData = ""; 
        $requestId = (string) time();

        // Chuỗi mã hóa SHA256 chuẩn MoMo v2
        $rawHash = "accessKey={$cfg['accessKey']}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$cfg['partnerCode']}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";

        $signature = hash_hmac('sha256', $rawHash, $cfg['secretKey']);

        $data = [
            'partnerCode' => $cfg['partnerCode'],
            'partnerName' => config('app.name', 'XFAN Store'),
            'storeId'     => 'XFANStore',
            'requestId'   => $requestId,
            'amount'      => $amount,
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
            $response = Http::withOptions([
                'verify' => $cfg['verifySsl'],
            ])->post($cfg['endpoint'], $data);

            $result = $response->json() ?? [];

            Log::info('MoMo Create Payment RawHash: ' . $rawHash);
            Log::info('MoMo Create Payment Response:', $result);

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