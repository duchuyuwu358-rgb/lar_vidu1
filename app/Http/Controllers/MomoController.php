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
     * Chỉ gọi hàm này khi người dùng CHỦ ĐỘNG HỦY giao dịch
     */
    private function restoreStockAndCancelOrder(Order $order): void
    {
        if (in_array(strtolower((string) $order->payment_status), ['paid']) || 
            in_array(strtolower((string) $order->status), ['paid', 'processing', 'completed', 'shipping', 'cancelled'])) {
            return;
        }

        DB::transaction(function () use ($order) {
            $items = collect();

            if (method_exists($order, 'items') && $order->items && $order->items->isNotEmpty()) {
                $items = $order->items;
            } elseif (method_exists($order, 'orderDetails') && $order->orderDetails && $order->orderDetails->isNotEmpty()) {
                $items = $order->orderDetails;
            } elseif (method_exists($order, 'details') && $order->details && $order->details->isNotEmpty()) {
                $items = $order->details;
            } elseif (method_exists($order, 'orderItems') && $order->orderItems && $order->orderItems->isNotEmpty()) {
                $items = $order->orderItems;
            }

            if ($items->isEmpty()) {
                foreach (['order_details', 'order_items', 'details', 'order_product', 'hood_order'] as $table) {
                    if (Schema::hasTable($table)) {
                        $dbItems = DB::table($table)->where('order_id', $order->id)->get();
                        if ($dbItems->isNotEmpty()) {
                            $items = $dbItems;
                            break;
                        }
                    }
                }
            }

            foreach ($items as $item) {
                $itemArr  = is_object($item) ? (array) $item : $item;
                $quantity = (int) ($itemArr['quantity'] ?? $itemArr['qty'] ?? 1);
                if ($quantity <= 0) $quantity = 1;

                $productModel = null;
                if (is_object($item) && method_exists($item, 'getAttributes')) {
                    $productModel = $item->hood ?? $item->product ?? $item->service ?? null;
                }

                if ($productModel) {
                    $attrs = method_exists($productModel, 'getAttributes') ? $productModel->getAttributes() : [];
                    if (array_key_exists('stock', $attrs)) {
                        $productModel->increment('stock', $quantity);
                    } elseif (array_key_exists('stock_quantity', $attrs)) {
                        $productModel->increment('stock_quantity', $quantity);
                    } elseif (array_key_exists('quantity', $attrs)) {
                        $productModel->increment('quantity', $quantity);
                    }
                } else {
                    $itemId = $itemArr['hood_id'] ?? $itemArr['product_id'] ?? $itemArr['service_id'] ?? $itemArr['item_id'] ?? null;

                    if ($itemId) {
                        if (Schema::hasTable('hoods')) {
                            if (Schema::hasColumn('hoods', 'stock')) {
                                DB::table('hoods')->where('id', $itemId)->increment('stock', $quantity);
                            } elseif (Schema::hasColumn('hoods', 'stock_quantity')) {
                                DB::table('hoods')->where('id', $itemId)->increment('stock_quantity', $quantity);
                            }
                        }

                        if (Schema::hasTable('products')) {
                            if (Schema::hasColumn('products', 'stock')) {
                                DB::table('products')->where('id', $itemId)->increment('stock', $quantity);
                            } elseif (Schema::hasColumn('products', 'stock_quantity')) {
                                DB::table('products')->where('id', $itemId)->increment('stock_quantity', $quantity);
                            }
                        }
                    }
                }
            }

            DB::table('orders')->where('id', $order->id)->update([
                'payment_status' => 'failed',
                'status'         => 'cancelled',
                'updated_at'     => now(),
            ]);
        });
    }

    public function startPayment(Order $order, MomoService $momoService)
    {
        $showOrderRoute = Route::has('orders.show')
            ? route('orders.show', $order->id)
            : (Route::has('user.orders.show') ? route('user.orders.show', $order->id) : (Route::has('orders.index') ? route('orders.index') : url("/orders/{$order->id}")));

        try {
            // Nếu đã thanh toán thành công thì không cho thanh toán lại
            if (in_array(strtolower((string) $order->payment_status), ['paid']) || 
                in_array(strtolower((string) $order->status), ['paid', 'processing', 'completed', 'shipping'])) {
                return redirect($showOrderRoute)->with('info', 'Đơn hàng này đã được thanh toán thành công.');
            }

            $amount = $order->total_amount ?? $order->total_price ?? $order->total ?? 0;

            // Đánh dấu các lượt transaction pending cũ thành failed để tạo lượt thanh toán hoàn toàn MỚI
            PaymentTransaction::where('order_id', $order->id)
                ->where('gateway', 'momo')
                ->where('status', 'pending')
                ->update(['status' => 'failed', 'message' => 'Khởi tạo lượt thanh toán mới']);

            // Tạo Transaction MỚI
            $transaction = PaymentTransaction::create([
                'order_id' => $order->id,
                'gateway'  => 'momo',
                'amount'   => $amount,
                'status'   => 'pending',
            ]);

            // Gọi MoMo payWithCC (Visa)
            $result = $momoService->createPayment($order, $transaction, 'payWithCC');

            if (!empty($result['payUrl'])) {
                return redirect()->away($result['payUrl']);
            }

            Log::error('MoMo Start Payment Failed: ', $result);

            // KHÔNG hủy đơn hàng ở đây, giữ đơn hàng pending để user thử lại
            return redirect($showOrderRoute)->with('error', 'Không thể kết nối MoMo: ' . ($result['message'] ?? 'Thử lại sau.'));
        } catch (\Exception $e) {
            Log::error('MoMo Start Payment Exception: ' . $e->getMessage());

            return redirect($showOrderRoute)->with('error', 'Lỗi khởi tạo thanh toán: ' . $e->getMessage());
        }
    }

    public function callback(Request $request, MomoService $momoService)
    {
        $payload = $request->all();
        Log::info('MoMo Callback Received:', $payload);

        $orderId = $momoService->orderId($payload);
        $order   = $orderId ? Order::find($orderId) : null;

        if (!$order) {
            return redirect('/orders')->with('error', 'Không tìm thấy thông tin đơn hàng.');
        }

        if (!Auth::check() && $order->user_id) {
            Auth::loginUsingId($order->user_id);
        }

        $transaction = null;
        if (!empty($payload['orderId'])) {
            $transaction = PaymentTransaction::where('gateway_order_id', $payload['orderId'])->first();
        }
        if (!$transaction) {
            $transaction = PaymentTransaction::where('order_id', $order->id)->latest()->first();
        }

        $resultCode       = isset($payload['resultCode']) ? (int)$payload['resultCode'] : -1;
        $isValidSignature = $momoService->isValidResponse($payload);

        $showOrderRoute = Route::has('orders.show')
            ? route('orders.show', $order->id)
            : (Route::has('user.orders.show') ? route('user.orders.show', $order->id) : (Route::has('orders.index') ? route('orders.index') : url("/orders/{$order->id}")));

        // 1. THANH TOÁN THÀNH CÔNG (resultCode = 0)
        if (($resultCode === 0 && $isValidSignature) || strtolower((string)$order->payment_status) === 'paid') {
            if ($transaction) {
                $momoService->markPaid($transaction, $payload);
            }

            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
            ]);

            session()->forget('cart');
            $this->sendOrderStatusEmail($order);

            return redirect($showOrderRoute)
                ->with('success', 'Thanh toán đơn hàng qua MoMo thành công!');
        }

        // 2. NGƯỜI DÙNG BẤM "QUAY VỀ / HỦY GIAO DỊCH" TRÊN MOMO (resultCode = 1006)
        if ($resultCode === 1006) {
            if ($transaction) {
                $momoService->markFailed($transaction, $payload);
            }

            // Hủy đơn hàng và hoàn lại tồn kho
            $this->restoreStockAndCancelOrder($order);

            return redirect($showOrderRoute)
                ->with('info', 'Bạn đã hủy giao dịch thanh toán MoMo.');
        }

        // 3. ĐIỀN SAI THẺ / NGÂN HÀNG TỪ CHỐI / LỖI KHÁC (resultCode khác 0 và khác 1006)
        if ($transaction) {
            $momoService->markFailed($transaction, $payload);
        }

        // GIỮ NGUYÊN ĐƠN HÀNG (Pending/Unpaid), KHÔNG HOÀN KHO, ĐỂ KHÁCH BẤM THANH TOÁN LAI
        return redirect($showOrderRoute)
            ->with('error', 'Thanh toán chưa thành công (' . ($payload['message'] ?? 'Thẻ bị từ chối') . '). Đơn hàng của bạn vẫn được lưu, hãy bấm nút "Thanh toán qua MoMo" để thử lại!');
    }

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
        $isValidSignature = $momoService->isValidResponse($payload);

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

        if ($resultCode === 1006) {
            if ($transaction) {
                $momoService->markFailed($transaction, $payload);
            }
            $this->restoreStockAndCancelOrder($order);
            return response()->json(['message' => 'User cancelled'], 200);
        }

        if ($transaction) {
            $momoService->markFailed($transaction, $payload);
        }

        return response()->json(['message' => 'Transaction failed'], 400);
    }

    /**
     * Cho phép bấm Hủy đơn từ giao diện Web
     */
    public function cancelPayment(Order $order)
    {
        $showOrderRoute = Route::has('orders.show')
            ? route('orders.show', $order->id)
            : (Route::has('user.orders.show') ? route('user.orders.show', $order->id) : (Route::has('orders.index') ? route('orders.index') : url("/orders/{$order->id}")));

        $this->restoreStockAndCancelOrder($order);

        return redirect($showOrderRoute)->with('info', 'Đã hủy đơn hàng thành công.');
    }
}