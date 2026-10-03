<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class MomoService
{
    protected function getConfigs(): array
    {
        return [
            'endpoint'     => config('services.momo.endpoint')     ?: 'https://test-payment.momo.vn/v2/gateway/api/create',
            'partnerCode'  => config('services.momo.partner_code') ?: 'MOMOBKUN20180529',
            'accessKey'    => config('services.momo.access_key')   ?: 'klm05TvNBzhg7h7j',
            'secretKey'    => config('services.momo.secret_key')   ?: 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa',
            'verifySsl'    => filter_var(config('services.momo.verify_ssl', false), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public function createPayment(Order $order, PaymentTransaction $transaction): array
    {
        $cfg = $this->getConfigs();

        $orderInfo   = 'Thanh toan don hang #' . $order->id;
        $amount      = (string) ((int) $order->total_price);
        $orderId     = $order->id . '_' . $transaction->id . '_' . time();
        
        $redirectUrl = config('services.momo.redirect_url') ?: url('/payment/momo/callback');
        $ipnUrl      = config('services.momo.ipn_url') ?: url('/payment/momo/ipn');
        
        $extraData   = ""; 
        $requestId   = (string) time();
        $requestType = 'payWithATM';

        $rawHash = "accessKey={$cfg['accessKey']}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$cfg['partnerCode']}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";

        $signature = hash_hmac('sha256', $rawHash, $cfg['secretKey']);

        $data = [
            'partnerCode' => $cfg['partnerCode'],
            'partnerName' => 'XFAN Store',
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

        $response = Http::withOptions([
            'verify' => $cfg['verifySsl'],
        ])->post($cfg['endpoint'], $data);

        $result = $response->json() ?? [];

        $transaction->update([
            'response_payload' => $result,
            'result_code'      => isset($result['resultCode']) ? (int) $result['resultCode'] : null,
            'message'          => $result['message'] ?? null,
            'status'           => isset($result['payUrl']) ? 'initiated' : 'failed',
        ]);

        return $result;
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
            'message'          => $payload['message'] ?? null,
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
            'message'          => $payload['message'] ?? null,
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

        $cfg = $this->getConfigs();
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