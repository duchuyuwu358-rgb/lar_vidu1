<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Mail\OrderStatusUpdatedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Hàm phụ hỗ trợ gửi email cập nhật trạng thái đơn hàng
     */
    private function sendOrderStatusEmail(Order $order)
    {
        $recipientEmail = $order->email ?? $order->user->email ?? null;

        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new OrderStatusUpdatedMail($order));
            } catch (\Exception $e) {
                Log::error("Lỗi gửi email cho đơn hàng #{$order->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Danh sách đơn hàng phía Khách hàng
     */
    public function index()
    {
        $orders = Order::with(['paymentTransactions', 'orderItems.hood', 'orderItems.service', 'items.hood', 'items.service'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /**
     * Danh sách đơn hàng Admin (Hỗ trợ Tab lọc + Tìm kiếm + Bộ lọc ngày)
     */
    public function adminIndex(Request $request)
    {
        $currentTab = $request->get('tab', 'all');
        $search     = trim($request->get('search', ''));
        $startDate  = $request->get('start_date');
        $endDate    = $request->get('end_date');

        $counts = [
            'all'             => Order::count(),
            'pending_payment' => Order::whereIn('status', ['pending_payment', 'pending'])->count(),
            'paid'            => Order::whereIn('status', ['paid', 'completed'])->count(),
            'processing'      => Order::where('status', 'processing')->count(),
            'shipping'        => Order::where('status', 'shipping')->count(),
            'completed'       => Order::where('status', 'completed')->count(),
            'cancelled'       => Order::whereIn('status', ['cancelled', 'failed'])->count(),
        ];

        $query = Order::with(['user', 'paymentTransactions'])->latest();

        if ($currentTab !== 'all') {
            if ($currentTab === 'pending_payment') {
                $query->whereIn('status', ['pending_payment', 'pending']);
            } elseif ($currentTab === 'cancelled') {
                $query->whereIn('status', ['cancelled', 'failed']);
            } elseif (array_key_exists($currentTab, $counts)) {
                $query->where('status', $currentTab);
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('ghn_order_code', 'like', "%{$search}%")
                  ->orWhere('momo_transaction_id', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('paymentTransactions', function ($pt) use ($search) {
                      $pt->where('transaction_id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $orders = $query->paginate(15)->appends([
            'tab'        => $currentTab,
            'search'     => $search,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);

        return view('admin.orders.index', compact('orders', 'currentTab', 'counts', 'search', 'startDate', 'endDate'));
    }

    /**
     * Xem chi tiết đơn hàng
     */
    public function show($id)
    {
        $order = Order::with([
            'user', 
            'paymentTransactions', 
            'orderItems.hood', 
            'orderItems.service', 
            'orderItems.product',
            'items.hood', 
            'items.service',
            'items.product'
        ])->findOrFail($id);

        return view('orders.show', compact('order'));
    }

    /**
     * Tạo đơn hàng COD
     */
    public function store(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'phone'   => 'required|string',
            'name'    => 'required|string',
            'email'   => 'required|email',
        ]);

        // Lấy mã giảm giá và số tiền giảm từ Session hoặc Request
        $couponCode     = session('coupon.code') ?? session('coupon.name') ?? $request->coupon_code ?? null;
        $discountAmount = session('coupon.discount') ?? session('coupon.discount_amount') ?? $request->discount_amount ?? 0;

        $order = Order::create([
            'user_id'         => auth()->id(),
            'total_price'     => $request->total_price ?? 0,
            'coupon_code'     => $couponCode,
            'discount_amount' => $discountAmount,
            'payment_method'  => 'cod',
            'payment_status'  => 'unpaid',
            'status'          => 'pending_payment',
            'address'         => $request->address,
            'phone'           => $request->phone,
            'name'            => $request->name,
            'email'           => $request->email,
        ]);

        // Xóa mã giảm giá khỏi Session sau khi tạo đơn hàng thành công
        session()->forget('coupon');

        // Gửi email xác nhận đặt hàng
        $this->sendOrderStatusEmail($order);

        return redirect()->route('orders.show', $order->id)->with('status', 'Đặt hàng thành công!');
    }

    /**
     * Cập nhật trạng thái đơn hàng thủ công trên Web Admin
     */
    public function updateStatus(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return back()->with('error', 'Nhân viên không có quyền cập nhật trạng thái đơn hàng!');
        }

        $request->validate([
            'status' => 'required|in:pending,pending_payment,paid,processing,shipping,completed,failed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $request->status;

        $paymentStatus = $order->payment_status;
        if (in_array($newStatus, ['paid', 'completed'])) {
            $paymentStatus = 'paid';
        } elseif ($newStatus === 'failed') {
            $paymentStatus = 'failed';
        } elseif (in_array($newStatus, ['cancelled', 'pending', 'pending_payment'])) {
            $paymentStatus = 'unpaid';
        }

        $order->update([
            'status'         => $newStatus,
            'payment_status' => $paymentStatus,
        ]);

        if ($oldStatus !== $newStatus) {
            $this->sendOrderStatusEmail($order);
        }

        $ghnNotice = '';
        if ($newStatus === 'cancelled' && !empty($order->ghn_order_code)) {
            try {
                $baseUrl   = env('GHN_BASE_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api');
                $token     = env('GHN_TOKEN');
                $shopId    = (int) env('GHN_SHOP_ID');
                $verifySsl = filter_var(env('GHN_VERIFY_SSL', false), FILTER_VALIDATE_BOOLEAN);

                $response = Http::withOptions(['verify' => $verifySsl])
                    ->withHeaders([
                        'Token'  => trim($token),
                        'ShopId' => $shopId,
                    ])->post("{$baseUrl}/v2/switch-status/cancel", [
                        'order_codes' => [$order->ghn_order_code],
                    ]);

                if ($response->successful()) {
                    $ghnNotice = ' & Đã gửi yêu cầu hủy đơn sang GHN!';
                }
            } catch (\Exception $e) {
                $ghnNotice = ' (Lỗi gửi yêu cầu hủy GHN: ' . $e->getMessage() . ')';
            }
        }

        return back()->with('status', 'Cập nhật trạng thái đơn hàng thành công và đã gửi mail thông báo!' . $ghnNotice);
    }

    /**
     * Đẩy đơn sang GHN
     */
    public function pushToGhn($id)
    {
        if (auth()->user()->role !== 'admin') {
            return back()->with('error', 'Nhân viên không có quyền đẩy đơn sang GHN!');
        }

        $order = Order::with([
            'orderItems.hood', 
            'orderItems.service', 
            'orderItems.product',
            'items.hood', 
            'items.service',
            'items.product'
        ])->findOrFail($id);

        if (!empty($order->ghn_order_code)) {
            return back()->with('error', 'Đơn hàng này đã có mã GHN: ' . $order->ghn_order_code);
        }

        $baseUrl        = env('GHN_BASE_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api');
        $token          = env('GHN_TOKEN');
        $shopId         = (int) env('GHN_SHOP_ID');
        $fromDistrictId = (int) env('GHN_FROM_DISTRICT_ID', 1485);
        $verifySsl      = filter_var(env('GHN_VERIFY_SSL', false), FILTER_VALIDATE_BOOLEAN);

        if (empty($token) || empty($shopId)) {
            return back()->with('error', 'Chưa cấu hình GHN_TOKEN hoặc GHN_SHOP_ID trong file .env!');
        }

        $items = [];
        $orderItems = ($order->orderItems && $order->orderItems->isNotEmpty()) 
            ? $order->orderItems 
            : ($order->items && $order->items->isNotEmpty() ? $order->items : collect());

        if ($orderItems->count() > 0) {
            foreach ($orderItems as $item) {
                $itemName = $item->display_name;
                $itemCode = 'SKU_' . ($item->service_id ?? $item->hood_id ?? $item->product_id ?? $item->id);

                $items[] = [
                    'name'     => (string) $itemName,
                    'code'     => (string) $itemCode,
                    'quantity' => (int) ($item->quantity ?? $item->qty ?? 1),
                    'price'    => (int) ($item->price ?? $item->unit_price ?? $order->total_price),
                    'length'   => 10,
                    'width'    => 10,
                    'height'   => 10,
                    'weight'   => 200,
                ];
            }
        } else {
            $items[] = [
                'name'     => 'Đơn hàng #' . $order->id,
                'code'     => 'ORDER_' . $order->id,
                'quantity' => 1,
                'price'    => (int) $order->total_price,
                'length'   => 10,
                'width'    => 10,
                'height'   => 10,
                'weight'   => 500,
            ];
        }

        $payload = [
            'payment_type_id'   => ($order->payment_method === 'cod') ? 2 : 1,
            'note'              => 'Cho xem hàng, không cho thử',
            'required_note'     => 'KHONGCHOXEMHANG',
            'from_name'         => config('app.name', 'XFAN Store'),
            'from_phone'        => '0986891911',
            'from_address'      => 'Cửa hàng XFAN Store',
            'from_district_id'  => $fromDistrictId,
            'to_name'           => (string) $order->name,
            'to_phone'          => (string) $order->phone,
            'to_address'        => (string) ($order->address ?? $order->shipping_address),
            'to_ward_code'      => (string) ($order->ward_code ?? '20314'),
            'to_district_id'    => (int) ($order->district_id ?? 1485),
            'cod_amount'        => ($order->payment_status === 'paid') ? 0 : (int) $order->total_price,
            'weight'            => 1200,
            'length'            => 30,
            'width'             => 20,
            'height'            => 10,
            'service_type_id'   => 2,
            'items'             => $items,
        ];

        try {
            $response = Http::withOptions(['verify' => $verifySsl])
                ->withHeaders([
                    'Token'  => trim($token),
                    'ShopId' => $shopId,
                ])->post("{$baseUrl}/v2/shipping-order/create", $payload);

            $resData = $response->json();

            if ($response->successful() && isset($resData['code']) && $resData['code'] === 200) {
                $ghnOrderCode = $resData['data']['order_code'];
                $oldStatus    = $order->status;

                $order->update([
                    'ghn_order_code' => $ghnOrderCode,
                    'status'         => 'processing',
                ]);

                if ($oldStatus !== 'processing') {
                    $this->sendOrderStatusEmail($order);
                }

                return back()->with('status', "Tạo đơn GHN thành công! Mã vận đơn: {$ghnOrderCode}");
            }

            $errorMsg = $resData['message'] ?? $resData['code_message_value'] ?? 'Lỗi không xác định từ GHN';
            return back()->with('error', "Đẩy đơn sang GHN thất bại: {$errorMsg}");

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi kết nối API GHN: ' . $e->getMessage());
        }
    }

    /**
     * Tra cứu/Đồng bộ trạng thái từ GHN về Web Local
     */
    public function syncGhnStatus(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return back()->with('error', 'Nhân viên không có quyền đồng bộ trạng thái GHN!');
        }

        $order = Order::findOrFail($id);

        if ($request->filled('ghn_order_code')) {
            $order->ghn_order_code = trim($request->ghn_order_code);
            $order->save();
        }

        if (empty($order->ghn_order_code)) {
            return back()->with('error', 'Đơn hàng #' . $order->id . ' chưa có Mã vận đơn GHN!');
        }

        try {
            $baseUrl   = env('GHN_BASE_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api');
            $token     = env('GHN_TOKEN');
            $shopId    = (int) env('GHN_SHOP_ID');
            $verifySsl = filter_var(env('GHN_VERIFY_SSL', false), FILTER_VALIDATE_BOOLEAN);

            $response = Http::withOptions(['verify' => $verifySsl])
                ->withHeaders([
                    'Token'  => trim($token),
                    'ShopId' => $shopId,
                ])->post("{$baseUrl}/v2/shipping-order/detail", [
                    'order_code' => $order->ghn_order_code,
                ]);

            $resData = $response->json();

            if ($response->successful() && isset($resData['code']) && $resData['code'] === 200) {
                $ghnStatus = $resData['data']['status'] ?? '';

                $statusMapping = [
                    'ready_to_pick' => 'processing',
                    'picking'       => 'processing',
                    'storing'       => 'processing',
                    'delivering'    => 'shipping',
                    'delivered'     => 'completed',
                    'cancel'        => 'cancelled',
                    'returned'      => 'cancelled',
                ];

                if (isset($statusMapping[$ghnStatus])) {
                    $oldStatus  = $order->status;
                    $newStatus  = $statusMapping[$ghnStatus];
                    $updateData = ['status' => $newStatus];

                    if ($ghnStatus === 'delivered') {
                        $updateData['payment_status'] = 'paid';
                    }

                    $order->update($updateData);

                    if ($oldStatus !== $newStatus) {
                        $this->sendOrderStatusEmail($order);
                    }

                    return back()->with('status', "Đồng bộ thành công đơn #{$order->id}! Trạng thái GHN: {$ghnStatus}");
                }

                return back()->with('status', "Mã GHN [{$order->ghn_order_code}] - Trạng thái GHN: {$ghnStatus}");
            }

            $msg = $resData['message'] ?? 'Không tìm thấy đơn hàng trên GHN';
            return back()->with('error', "Lỗi tra cứu GHN [{$order->ghn_order_code}]: {$msg}");

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi kết nối GHN API: ' . $e->getMessage());
        }
    }

    /**
     * Nhận Webhook tự động từ GHN
     */
    public function handleGhnWebhook(Request $request)
    {
        $orderCode = $request->input('OrderCode') ?? $request->input('order_code');
        $ghnStatus = $request->input('Status') ?? $request->input('status');

        if (!$orderCode || !$ghnStatus) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu thông tin OrderCode hoặc Status',
            ], 400);
        }

        $order = Order::where('ghn_order_code', $orderCode)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy đơn hàng nào có mã GHN: {$orderCode}",
            ], 404);
        }

        $statusMapping = [
            'ready_to_pick' => 'processing',
            'picking'       => 'processing',
            'storing'       => 'processing',
            'delivering'    => 'shipping',
            'delivered'     => 'completed',
            'cancel'        => 'cancelled',
            'returned'      => 'cancelled',
        ];

        if (isset($statusMapping[$ghnStatus])) {
            $oldStatus  = $order->status;
            $newStatus  = $statusMapping[$ghnStatus];
            $updateData = ['status' => $newStatus];

            if ($ghnStatus === 'delivered') {
                $updateData['payment_status'] = 'paid';
            }

            $order->update($updateData);

            if ($oldStatus !== $newStatus) {
                $this->sendOrderStatusEmail($order);
            }

            return response()->json([
                'success'    => true,
                'message'    => "Đã cập nhật đơn hàng #{$order->id} sang trạng thái [{$newStatus}] thành công!",
                'order_id'   => $order->id,
                'new_status' => $order->status,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => "Trạng thái GHN [{$ghnStatus}] không nằm trong danh sách xử lý",
        ], 400);
    }
}