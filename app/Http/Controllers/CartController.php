<?php

namespace App\Http\Controllers;

use App\Models\Hood;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\ServicePackage;
use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CartController extends Controller
{
    /**
     * 1. Hiển thị giỏ hàng
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
     * 2. Thêm sản phẩm vật lý vào giỏ
     */
    public function add(Request $request)
    {
        $request->validate([
            'hood_id'  => ['required', 'exists:hoods,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'color'    => ['nullable', 'string', 'max:50'],
        ]);

        $hood = Hood::with('category')->findOrFail($request->hood_id);
        $cart = session()->get('cart', []);
        $quantity = (int)($request->quantity ?? 1);
        $color = $request->input('color');

        $cartKey = $color ? 'product_' . $hood->id . '_' . md5($color) : 'product_' . $hood->id;

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'cart_key'    => $cartKey,
                'id'          => $hood->id,
                'hood_id'     => $hood->id,
                'category_id' => $hood->category_id ?? null,
                'name'        => $hood->name,
                'price'       => $hood->price,
                'quantity'    => $quantity,
                'color'       => $color,
                'category'    => $hood->category->name ?? 'Máy Hút Mùi',
                'model'       => $hood->model_code ?? $hood->model ?? ('Model ' . $hood->id),
                'image'       => $hood->image ?? $hood->image_path ?? $hood->image_url ?? null,
                'type'        => 'product',
            ];
        }

        session()->put('cart', $cart);

        if ($request->input('action') === 'buy_now') {
            return redirect()->route('cart.checkout', ['selected_items' => [$cartKey]]);
        }

        return redirect()->route('cart.index')->with('success', 'Đã thêm sản phẩm vào giỏ hàng!');
    }

    /**
     * 3. Áp dụng mã giảm giá bất kỳ từ CSDL
     */
    public function applyCoupon(Request $request)
    {
        $code = strtoupper(trim($request->input('coupon_code', '')));

        if (empty($code)) {
            return back()->with('error', 'Vui lòng nhập mã giảm giá.');
        }

        $coupon = Coupon::where('code', $code)->where('is_active', true)->first();

        if (!$coupon) {
            return back()->with('error', "Mã giảm giá '{$code}' không tồn tại hoặc đã bị ngừng áp dụng.");
        }

        // Kiểm tra Hạn sử dụng
        if ($coupon->expires_at && Carbon::parse($coupon->expires_at)->isPast()) {
            return back()->with('error', 'Mã giảm giá này đã hết hạn sử dụng.');
        }

        // Kiểm tra Số lượng mã còn lại
        if ($coupon->quantity !== null && $coupon->quantity <= 0) {
            return back()->with('error', 'Mã giảm giá này đã hết lượt sử dụng.');
        }

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return back()->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        $cartSubtotal = 0;
        $eligibleSubtotal = 0;
        $hasEligibleItem = false;

        foreach ($cart as $item) {
            $itemPrice = $item['price'] ?? 0;
            $itemQty   = $item['quantity'] ?? 1;
            $itemTotal = $itemPrice * $itemQty;
            $cartSubtotal += $itemTotal;

            $matchesHood = (!$coupon->hood_id || ($item['hood_id'] ?? $item['id'] ?? null) == $coupon->hood_id);
            $matchesCat  = (!$coupon->category_id || ($item['category_id'] ?? null) == $coupon->category_id);

            if ($matchesHood && $matchesCat) {
                $hasEligibleItem = true;
                $eligibleSubtotal += $itemTotal;
            }
        }

        if (!$hasEligibleItem) {
            return back()->with('error', 'Mã ưu đãi không áp dụng cho các sản phẩm hiện có trong giỏ hàng.');
        }

        if ($coupon->min_order_amount && $cartSubtotal < $coupon->min_order_amount) {
            return back()->with('error', 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($coupon->min_order_amount) . 'đ để dùng mã này.');
        }

        // Tính giá trị giảm giá
        $discountAmount = 0;
        if ($coupon->type === 'percent') {
            $discountAmount = ($eligibleSubtotal * $coupon->value) / 100;
        } else {
            $discountAmount = $coupon->value;
        }

        if ($discountAmount > $cartSubtotal) {
            $discountAmount = $cartSubtotal;
        }

        session()->put('applied_coupon', [
            'coupon_id' => $coupon->id,
            'code'      => $coupon->code,
            'discount'  => $discountAmount,
            'type'      => $coupon->type,
            'value'     => $coupon->value,
        ]);

        return back()->with('success', "Áp dụng mã '{$coupon->code}' thành công! Được giảm " . number_format($discountAmount) . "đ.");
    }

    /**
     * 4. Hủy mã giảm giá đã áp dụng
     */
    public function removeCoupon()
    {
        session()->forget('applied_coupon');
        return back()->with('success', 'Đã hủy bỏ mã giảm giá.');
    }

    /**
     * 5. Trang thanh toán
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

        // Tính lại số tiền giảm từ Coupon nếu có
        $discountAmount = 0;
        if (session()->has('applied_coupon')) {
            $applied = session('applied_coupon');
            $discountAmount = $applied['discount'] ?? 0;
            if ($discountAmount > $totalAmount) {
                $discountAmount = $totalAmount;
            }
        }

        return view('cart.checkout', compact('checkoutCart', 'totalAmount', 'discountAmount'));
    }

    /**
     * 6. Xóa khỏi giỏ
     */
    public function remove($key)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put('cart', $cart);
        }
        return redirect()->route('cart.index')->with('success', 'Đã xóa khỏi giỏ hàng!');
    }

    /**
     * 7. XỬ LÝ ĐẶT HÀNG (CÓ LƯU MÃ GIẢM GIÁ VÀ SỐ TIỀN GIẢM)
     */
    public function processCheckout(Request $request)
    {
        $request->validate([
            'name'           => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL\s]+$/u'],
            'phone'          => ['required', 'string', 'regex:/^(0|\+84)[35789][0-9]{8}$/'],
            'province_id'    => ['required', 'numeric'],
            'to_district_id' => ['required', 'numeric'],
            'to_ward_code'   => ['required', 'string', 'max:20'],
            'address'        => ['required', 'string', 'min:5', 'max:255'],
            'payment_method' => ['required', 'in:cod,momo'],
        ], [
            'name.required'           => 'Vui lòng nhập họ và tên người nhận.',
            'name.min'                => 'Họ và tên phải chứa ít nhất 2 ký tự.',
            'name.max'                => 'Họ và tên không được quá 50 ký tự.',
            'name.regex'              => 'Họ và tên chỉ chứa chữ cái và khoảng trắng.',
            'phone.required'          => 'Vui lòng nhập số điện thoại.',
            'phone.regex'             => 'Số điện thoại không hợp lệ (phải từ 10 - 11 chữ số, không chứa chữ hoặc ký tự đặc biệt).',
            'province_id.required'    => 'Vui lòng chọn Tỉnh/Thành phố.',
            'to_district_id.required' => 'Vui lòng chọn Quận/Huyện.',
            'to_ward_code.required'   => 'Vui lòng chọn Phường/Xã.',
            'address.required'        => 'Vui lòng nhập địa chỉ giao hàng cụ thể.',
            'address.min'             => 'Địa chỉ quá ngắn (tối thiểu 5 ký tự).',
            'address.max'             => 'Địa chỉ giao hàng quá dài (tối đa 255 ký tự).',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
        ]);

        $checkoutCart = session()->get('checkout_cart', []);
        if (empty($checkoutCart)) {
            return redirect()->route('cart.index')->with('error', 'Phiên thanh toán đã hết hạn hoặc giỏ hàng trống.');
        }

        $subtotal          = collect($checkoutCart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $discountAmount    = 0;
        $appliedCouponCode = null;

        if (session()->has('applied_coupon')) {
            $applied           = session('applied_coupon');
            $discountAmount    = $applied['discount'] ?? 0;
            $appliedCouponCode = $applied['code'] ?? null;
        }

        $subtotalAfterDiscount = max(0, $subtotal - $discountAmount);
        $shippingFee           = (int)$request->input('shipping_fee', 0);
        $finalTotal            = $subtotalAfterDiscount + $shippingFee;

        try {
            $order = DB::transaction(function () use ($request, $shippingFee, $finalTotal, $checkoutCart, $appliedCouponCode, $discountAmount) {
                foreach ($checkoutCart as $item) {
                    $isService = ($item['type'] ?? '') === 'service';
                    if (!$isService) {
                        $hood = Hood::where('id', $item['id'])->lockForUpdate()->first();
                        if (!$hood || ($hood->stock_quantity ?? 0) < $item['quantity']) {
                            throw new \Exception("Sản phẩm '{$item['name']}' không đủ số lượng trong kho!");
                        }
                    }
                }

                $order = Order::create([
                    'user_id'         => Auth::id(),
                    'name'            => trim($request->name),
                    'address'         => trim($request->address),
                    'phone'           => trim($request->phone),
                    'total_price'     => $finalTotal,
                    'coupon_code'     => $appliedCouponCode, // <-- LƯU MÃ GIẢM GIÁ
                    'discount_amount' => $discountAmount,    // <-- LƯU SỐ TIỀN GIẢM
                    'status'          => 'pending',
                    'to_district_id'  => (int)$request->to_district_id,
                    'to_ward_code'    => (string)$request->to_ward_code,
                    'ghn_total_fee'   => $shippingFee,
                    'shipping_status' => 'pending',
                ]);

                foreach ($checkoutCart as $item) {
                    $isService = ($item['type'] ?? '') === 'service';
                    $productId = $isService ? null : $item['id'];

                    try {
                        OrderItem::create([
                            'order_id'   => $order->id,
                            'product_id' => $productId,
                            'quantity'   => $item['quantity'],
                            'price'      => $item['price'],
                            'color'      => $item['color'] ?? null,
                        ]);
                    } catch (\Exception $ex) {
                        OrderItem::create([
                            'order_id'   => $order->id,
                            'product_id' => $item['id'],
                            'quantity'   => $item['quantity'],
                            'price'      => $item['price'],
                            'color'      => $item['color'] ?? null,
                        ]);
                    }

                    if (!$isService) {
                        Hood::where('id', $item['id'])->decrement('stock_quantity', $item['quantity']);
                    }
                }

                // Trừ số lượng sử dụng mã ưu đãi nếu có
                if (session()->has('applied_coupon')) {
                    $applied = session('applied_coupon');
                    $coupon = Coupon::find($applied['coupon_id'] ?? null) ?? Coupon::where('code', $applied['code'] ?? '')->first();
                    if ($coupon && $coupon->quantity !== null && $coupon->quantity > 0) {
                        $coupon->decrement('quantity');
                    }
                }

                return $order;
            });
        } catch (\Exception $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        // Xóa giỏ hàng & session coupon
        $cart = session()->get('cart', []);
        foreach (array_keys($checkoutCart) as $key) {
            unset($cart[$key]);
        }
        session()->put('cart', $cart);
        session()->forget('checkout_cart');
        session()->forget('applied_coupon');

        // MoMo
        if ($request->payment_method === 'momo') {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'gateway'  => 'momo',
                'amount'   => $order->total_price,
                'status'   => 'pending',
            ]);

            return redirect()->route('user.orders.momo.start', $order);
        }

        // COD
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
                'name'     => (string)($item->product->name ?? 'Mặt hàng #' . $item->id),
                'quantity' => (int)$item->quantity,
                'price'    => (int)$item->price,
                'weight'   => 1000,
            ];
        }

        $serviceId = $this->getAvailableServiceId($baseUrl, $token, $shopId, $fromDistrictId, (int)$order->to_district_id);

        $ghnPayload = [
            'payment_type_id'  => 2,
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

        try {
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
        } catch (\Exception $e) {
            Log::error('GHN Create Order Exception: ' . $e->getMessage());
        }

        return null;
    }

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

        try {
            $response = Http::withoutVerifying()
                ->withHeaders(['Token' => $token])
                ->get($baseUrl . '/master-data/province');

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDistricts($provinceId)
    {
        if (!is_numeric($provinceId)) {
            return response()->json(['code' => 400, 'message' => 'Province ID không hợp lệ'], 400);
        }

        $baseUrl = $this->getGhnConfig('base_url');
        $token   = $this->getGhnConfig('token');

        try {
            $response = Http::withoutVerifying()
                ->withHeaders(['Token' => $token])
                ->get($baseUrl . '/master-data/district', [
                    'province_id' => (int)$provinceId
                ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function getWards($districtId)
    {
        if (!is_numeric($districtId)) {
            return response()->json(['code' => 400, 'message' => 'District ID không hợp lệ'], 400);
        }

        $baseUrl = $this->getGhnConfig('base_url');
        $token   = $this->getGhnConfig('token');

        try {
            $response = Http::withoutVerifying()
                ->withHeaders(['Token' => $token])
                ->get($baseUrl . '/master-data/ward', [
                    'district_id' => (int)$districtId
                ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function getShippingFee(Request $request)
    {
        $request->validate([
            'to_district_id' => ['required', 'numeric'],
            'to_ward_code'   => ['required', 'string', 'max:20']
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

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Token'  => $token,
                    'ShopId' => (int)$shopId
                ])
                ->post($baseUrl . '/v2/shipping-order/fee', $payload);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}