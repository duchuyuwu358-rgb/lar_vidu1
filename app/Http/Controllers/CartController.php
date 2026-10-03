<?php

namespace App\Http\Controllers;

use App\Models\Hood;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CartController extends Controller
{
    /**
     * 1. Hiển thị danh sách giỏ hàng
     */
    public function index()
    {
        $cart = session()->get('cart', []);

        $groupedCart = [];
        foreach ($cart as $key => $item) {
            $categoryName = $item['category'] ?? 'Sản Phẩm Khác';
            $groupedCart[$categoryName][$key] = $item;
        }

        return view('cart.index', compact('groupedCart', 'cart'));
    }

    /**
     * 2. Thêm sản phẩm vào giỏ
     */
    public function add(Request $request)
    {
        $request->validate([
            'hood_id'  => 'required|exists:hoods,id',
            'quantity' => 'nullable|integer|min:1',
            'color'    => 'nullable|string',
        ]);

        $hood = Hood::with('category')->findOrFail($request->hood_id);
        $cart = session()->get('cart', []);
        $quantity = (int)($request->quantity ?? 1);
        $color = $request->input('color');

        $cartKey = $color ? $hood->id . '_' . md5($color) : (string)$hood->id;

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'cart_key' => $cartKey,
                'id'       => $hood->id,
                'name'     => $hood->name,
                'price'    => $hood->price,
                'quantity' => $quantity,
                'color'    => $color,
                'category' => $hood->category->name ?? 'Máy Hút Mùi',
                'model'    => $hood->model_code ?? $hood->model ?? ('Model ' . $hood->id),
                'image'    => $hood->image ?? $hood->image_path ?? $hood->image_url ?? null,
            ];
        }

        session()->put('cart', $cart);
        return redirect()->route('cart.index')->with('success', 'Đã thêm sản phẩm vào giỏ hàng!');
    }

    /**
     * 3. Trang thanh toán
     */
    public function checkout(Request $request)
    {
        $selectedKeys = $request->input('selected_items', []);
        $quantities   = $request->input('quantities', []);
        $cart         = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống!');
        }

        foreach ($quantities as $key => $qty) {
            if (isset($cart[$key])) {
                $cart[$key]['quantity'] = max(1, (int)$qty);
            }
        }
        session()->put('cart', $cart);

        $checkoutCart = [];
        $totalAmount  = 0;
        $targetKeys   = !empty($selectedKeys) ? $selectedKeys : array_keys($cart);

        foreach ($targetKeys as $key) {
            if (isset($cart[$key])) {
                $item = $cart[$key];
                $subtotal = $item['price'] * $item['quantity'];
                $item['subtotal'] = $subtotal;
                $checkoutCart[$key] = $item;
                $totalAmount += $subtotal;
            }
        }

        if (empty($checkoutCart)) {
            return redirect()->route('cart.index')->with('error', 'Vui lòng chọn ít nhất một sản phẩm để thanh toán!');
        }

        session()->put('checkout_cart', $checkoutCart);

        return view('cart.checkout', compact('checkoutCart', 'totalAmount'));
    }

    /**
     * 4. Xóa món khỏi giỏ
     */
    public function remove($key)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put('cart', $cart);
        }
        return redirect()->route('cart.index')->with('success', 'Đã xóa sản phẩm khỏi giỏ!');
    }

    /**
     * 5. Xử lý tạo đơn hàng
     */
    public function processCheckout(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'province_id'    => 'required',
            'to_district_id' => 'required',
            'to_ward_code'   => 'required',
            'address'        => 'required|string',
            'payment_method' => 'required',
        ]);

        $checkoutCart = session()->get('checkout_cart', []);
        if (empty($checkoutCart)) {
            return redirect()->route('cart.index')->with('error', 'Phiên thanh toán đã hết hạn hoặc giỏ hàng trống.');
        }

        // Kiểm tra tồn kho
        foreach ($checkoutCart as $item) {
            $hood = Hood::find($item['id']);
            if (!$hood || ($hood->stock_quantity ?? 0) < $item['quantity']) {
                return redirect()->route('cart.index')->with('error', "Sản phẩm '{$item['name']}' không đủ số lượng trong kho!");
            }
        }

        $subtotal    = collect($checkoutCart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $shippingFee = (int)$request->input('shipping_fee', 0);
        $finalTotal  = $subtotal + $shippingFee;

        // Lưu đơn hàng vào DB Local
        $order = DB::transaction(function () use ($request, $shippingFee, $finalTotal, $checkoutCart) {
            $order = Order::create([
                'user_id'         => Auth::id(),
                'name'            => $request->name,
                'address'         => $request->address,
                'phone'           => $request->phone,
                'total_price'     => $finalTotal,
                'status'          => 'pending',
                'to_district_id'  => (int)$request->to_district_id,
                'to_ward_code'    => (string)$request->to_ward_code,
                'ghn_total_fee'   => $shippingFee,
                'shipping_status' => 'pending',
            ]);

            foreach ($checkoutCart as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                    'color'      => $item['color'] ?? null,
                ]);

                // Trừ tồn kho
                Hood::where('id', $item['id'])->decrement('stock_quantity', $item['quantity']);
            }

            return $order;
        });

        // Xóa sản phẩm vừa mua khỏi giỏ hàng
        $cart = session()->get('cart', []);
        foreach (array_keys($checkoutCart) as $key) {
            unset($cart[$key]);
        }
        session()->put('cart', $cart);
        session()->forget('checkout_cart');

        // TRƯỜNG HỢP 1: Thanh toán MoMo
        if ($request->payment_method === 'momo') {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'gateway'  => 'momo',
                'amount'   => $order->total_price,
                'status'   => 'pending',
            ]);

            // Chuyển sang flow MoMo (Chưa tạo đơn GHN cho đến khi nhận được IPN/Callback thành công)
            return redirect()->route('user.orders.momo.start', $order);
        }

        // TRƯỜNG HỢP 2: Thanh toán COD
        // Đẩy đơn lên GHN ngay lập tức
        $ghnOrderCode = $this->pushToGhn($order);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway'  => 'cod',
            'amount'   => $order->total_price,
            'status'   => 'pending',
            'message'  => 'Thanh toán COD - Trạng thái: Chờ thanh toán khi nhận hàng',
        ]);

        $msg = 'Đặt hàng thành công!';
        if ($ghnOrderCode) {
            $msg .= " Mã vận đơn GHN: {$ghnOrderCode}";
        }

        return redirect()->route('storefront')->with('success', $msg);
    }

    /**
     * 6. Hàm bổ trợ: Đẩy đơn hàng sang Giao Hàng Nhanh (GHN)
     */
    public function pushToGhn(Order $order)
    {
        $baseUrl        = $this->getGhnConfig('base_url');
        $token          = $this->getGhnConfig('token');
        $shopId         = $this->getGhnConfig('shop_id');
        $fromDistrictId = (int)($this->getGhnConfig('from_district_id') ?? 1485);

        $order->loadMissing('items.product');

        $ghnItems = [];
        $totalWeight = 0;
        foreach ($order->items as $item) {
            $itemWeight = 1000 * (int)$item->quantity;
            $totalWeight += $itemWeight;
            $ghnItems[] = [
                'name'     => (string)($item->product->name ?? 'Sản phẩm #' . $item->product_id),
                'quantity' => (int)$item->quantity,
                'price'    => (int)$item->price,
                'weight'   => 1000,
            ];
        }

        $serviceId = $this->getAvailableServiceId($baseUrl, $token, $shopId, $fromDistrictId, (int)$order->to_district_id);

        $ghnPayload = [
            'payment_type_id'  => 2, // 1: Shop trả phí, 2: Khách trả phí
            'note'             => 'Đơn hàng từ XFAN Store',
            'required_note'    => 'KHONGCHOXEMHANG',
            'from_district_id' => $fromDistrictId,
            'to_name'          => (string)$order->name,
            'to_phone'         => (string)$order->phone,
            'to_address'       => (string)$order->address,
            'to_ward_code'     => (string)$order->to_ward_code,
            'to_district_id'   => (int)$order->to_district_id,
            'weight'           => max(500, $totalWeight),
            'length'           => 30,
            'width'            => 20,
            'height'           => 10,
            'insurance_value'  => min((int)$order->total_price, 5000000),
            'items'            => $ghnItems,
        ];

        if ($serviceId) {
            $ghnPayload['service_id'] = $serviceId;
        } else {
            $ghnPayload['service_type_id'] = 2;
        }

        // Gọi API tạo đơn GHN
        $ghnResponse = Http::withoutVerifying()
            ->withHeaders([
                'Token'  => $token,
                'ShopId' => (int)$shopId,
            ])
            ->post($baseUrl . '/v2/shipping-order/create', $ghnPayload);

        $ghnResult = $ghnResponse->json();

        if (isset($ghnResult['code']) && $ghnResult['code'] === 200) {
            $ghnOrderCode = $ghnResult['data']['order_code'] ?? null;
            if ($ghnOrderCode && Schema::hasColumn('orders', 'ghn_order_code')) {
                $order->update(['ghn_order_code' => $ghnOrderCode]);
            }
            return $ghnOrderCode;
        }

        Log::error('GHN Create Order Error: ', $ghnResult ?? []);
        return null;
    }

    // ==========================================
    // API GHN MASTER DATA & SHIPPING FEE
    // ==========================================

    private function getGhnConfig($key)
    {
        return config("services.ghn.{$key}", env("GHN_" . strtoupper($key)));
    }

    private function getAvailableServiceId($baseUrl, $token, $shopId, $fromDistrict, $toDistrict)
    {
        try {
            $res = Http::withoutVerifying()
                ->withHeaders(['Token' => $token])
                ->post($baseUrl . '/v2/shipping-order/available-services', [
                    'shop_id'       => (int)$shopId,
                    'from_district' => (int)$fromDistrict,
                    'to_district'   => (int)$toDistrict,
                ]);

            $data = $res->json();
            if (isset($data['data'][0]['service_id'])) {
                return (int)$data['data'][0]['service_id'];
            }
        } catch (\Exception $e) {
            Log::error('GHN Available Services Exception: ' . $e->getMessage());
        }
        return null;
    }

    public function getProvinces()
    {
        $baseUrl = $this->getGhnConfig('base_url');
        $token   = $this->getGhnConfig('token');

        $response = Http::withoutVerifying()
            ->withHeaders(['Token' => $token])
            ->get($baseUrl . '/master-data/province');

        return response()->json($response->json());
    }

    public function getDistricts($provinceId)
    {
        $baseUrl = $this->getGhnConfig('base_url');
        $token   = $this->getGhnConfig('token');

        $response = Http::withoutVerifying()
            ->withHeaders(['Token' => $token])
            ->get($baseUrl . '/master-data/district', [
                'province_id' => (int)$provinceId
            ]);

        return response()->json($response->json());
    }

    public function getWards($districtId)
    {
        $baseUrl = $this->getGhnConfig('base_url');
        $token   = $this->getGhnConfig('token');

        $response = Http::withoutVerifying()
            ->withHeaders(['Token' => $token])
            ->get($baseUrl . '/master-data/ward', [
                'district_id' => (int)$districtId
            ]);

        return response()->json($response->json());
    }

    public function getShippingFee(Request $request)
    {
        $request->validate([
            'to_district_id' => 'required',
            'to_ward_code'   => 'required'
        ]);

        $baseUrl        = $this->getGhnConfig('base_url');
        $token          = $this->getGhnConfig('token');
        $shopId         = $this->getGhnConfig('shop_id');
        $fromDistrictId = (int)($this->getGhnConfig('from_district_id') ?? 1485);

        $serviceId = $this->getAvailableServiceId($baseUrl, $token, $shopId, $fromDistrictId, (int)$request->to_district_id);

        $payload = [
            'to_district_id'   => (int)$request->to_district_id,
            'to_ward_code'     => (string)$request->to_ward_code,
            'height'           => 10,
            'length'           => 10,
            'width'            => 10,
            'weight'           => 1000,
            'insurance_value'  => 0,
            'from_district_id' => $fromDistrictId
        ];

        if ($serviceId) {
            $payload['service_id'] = $serviceId;
        } else {
            $payload['service_type_id'] = 2;
        }

        $response = Http::withoutVerifying()
            ->withHeaders([
                'Token'  => $token,
                'ShopId' => (int)$shopId
            ])
            ->post($baseUrl . '/v2/shipping-order/fee', $payload);

        return response()->json($response->json());
    }
}