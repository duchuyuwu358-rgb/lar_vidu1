<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Category;
use App\Models\Hood;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $status = $request->get('status', 'all');
        $type   = $request->get('type', 'all');

        $query = Coupon::with(['category', 'hood']);

        if (!empty($search)) {
            $query->where('code', 'like', "%{$search}%");
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $coupons = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return view('admin.coupons.index', compact('coupons', 'search', 'status', 'type'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $hoods      = Hood::orderBy('name')->get();

        return view('admin.coupons.create', compact('categories', 'hoods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code',
            'type'             => 'required|in:percent,fixed',
            'value'            => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'quantity'         => 'nullable|integer|min:1',
            'category_id'      => 'nullable|exists:categories,id',
            'hood_id'          => 'nullable|exists:hoods,id',
            'start_date'       => 'nullable|date',
            'expires_at'       => 'nullable|date|after_or_equal:start_date',
            'description'      => 'nullable|string|max:500',
            'is_active'        => 'nullable|boolean',
        ], [
            'code.required'     => 'Vui lòng nhập mã giảm giá.',
            'code.unique'       => 'Mã giảm giá này đã tồn tại.',
            'value.required'    => 'Vui lòng nhập giá trị giảm.',
            'expires_at.after_or_equal' => 'Ngày hết hạn phải lớn hơn hoặc bằng ngày bắt đầu.',
        ]);

        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Thêm mã khuyến mại mới thành công!');
    }

    public function edit(Coupon $coupon)
    {
        $categories = Category::orderBy('name')->get();
        $hoods      = Hood::orderBy('name')->get();

        return view('admin.coupons.edit', compact('coupon', 'categories', 'hoods'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type'             => 'required|in:percent,fixed',
            'value'            => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'quantity'         => 'nullable|integer|min:1',
            'category_id'      => 'nullable|exists:categories,id',
            'hood_id'          => 'nullable|exists:hoods,id',
            'start_date'       => 'nullable|date',
            'expires_at'       => 'nullable|date|after_or_equal:start_date',
            'description'      => 'nullable|string|max:500',
            'is_active'        => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Cập nhật mã khuyến mại thành công!');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('success', 'Xóa mã khuyến mại thành công!');
    }
}