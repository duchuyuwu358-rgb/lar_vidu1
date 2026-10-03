<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GhnWebhookController extends Controller
{
    /**
     * Nhận dữ liệu webhook từ GHN
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        Log::info('GHN Webhook Payload:', $data);

        $ghnOrderCode = $data['OrderCode'] ?? null;
        $ghnStatus    = strtolower($data['Status'] ?? '');

        if (!$ghnOrderCode) {
            return response()->json(['message' => 'Missing OrderCode'], 400);
        }

        $order = Order::where('ghn_order_code', $ghnOrderCode)->first();

        if ($order) {
            $shippingStatus = $this->mapGhnStatusToLocal($ghnStatus);

            $updateData = [
                'shipping_status' => $shippingStatus
            ];

            // Cập nhật trạng thái chung của đơn hàng
            if ($shippingStatus === 'delivered') {
                $updateData['status'] = 'completed';
            } elseif ($shippingStatus === 'other') {
                $updateData['status'] = 'cancelled';
            }

            $order->update($updateData);
        }

        return response()->json(['message' => 'Webhook Processed Successfully'], 200);
    }

    /**
     * Quy đổi mã trạng thái GHN sang 6 nhóm chuẩn
     */
    private function mapGhnStatusToLocal(string $status): string
    {
        return match ($status) {
            // 1. Chờ lấy
            'ready_to_pick', 'picking' => 'ready_to_pick',

            // 2. Đang vận chuyển
            'storing', 'transporting', 'sorting', 'delivering', 'money_collect_delivering' => 'delivering',

            // 3. Giao thành công
            'delivered' => 'delivered',

            // 4. Giao thất bại
            'delivery_fail' => 'delivery_fail',

            // 5. Hoàn hàng
            'return', 'returning', 'return_transporting', 'return_sorting', 'returned', 'return_fail' => 'returned',

            // 6. Khác / Hủy
            'cancel', 'exception', 'damage', 'lost' => 'other',

            default => 'ready_to_pick',
        };
    }
}