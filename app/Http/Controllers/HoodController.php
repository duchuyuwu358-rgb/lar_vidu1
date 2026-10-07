<?php

namespace App\Http\Controllers;

use App\Models\Hood;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HoodController extends Controller
{
    /**
     * --------------------------------------------------------------------------
     * 1. GIAO DIỆN BÁN HÀNG (STOREFRONT - Dành cho người mua)
     * --------------------------------------------------------------------------
     */

    /**
     * Trang danh sách sản phẩm bán hàng (/storefront)
     */
    public function storefront(Request $request)
    {
        $search     = $request->get('search');
        $category   = $request->get('category_id') ?? $request->get('category');
        $priceRange = $request->get('price_range');

        // Chỉ lấy sản phẩm đang kích hoạt
        $query = Hood::with('category')->where('is_active', true);

        // 1. Tìm kiếm theo Tên hoặc Model
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('model', 'like', '%' . $search . '%');
            });
        }

        // 2. Lọc theo Danh mục
        if ($category) {
            $query->where('category_id', $category);
        }

        // 3. Lọc theo Khoảng giá
        if ($priceRange) {
            switch ($priceRange) {
                case 'under_3m':
                    $query->where('price', '<', 3000000);
                    break;
                case '3m_5m':
                    $query->whereBetween('price', [3000000, 5000000]);
                    break;
                case 'over_5m':
                    $query->where('price', '>', 5000000);
                    break;
            }
        }

        $products   = $query->latest()->paginate(9)->withQueryString();
        $hoods      = $products; // Đồng bộ biến cho View storefront.blade.php
        $categories = Category::where('is_active', true)->get();

        return view('storefront', compact('products', 'hoods', 'categories', 'search', 'category', 'priceRange'));
    }

    /**
     * Trang chi tiết sản phẩm bán hàng (/storefront/{id})
     */
    public function storefrontShow($id)
    {
        $hood = $id instanceof Hood ? $id : Hood::with('category')->findOrFail($id);
        $product = $hood;

        return view('storefront_detail', compact('hood', 'product'));
    }

    /**
     * --------------------------------------------------------------------------
     * 2. TRANG QUẢN TRỊ (ADMIN CRUD - Dành cho Quản trị viên)
     * --------------------------------------------------------------------------
     */

    /**
     * Hiển thị danh sách sản phẩm quản trị
     */
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $category = $request->get('category');
        $type     = $request->get('type');
        $status   = $request->get('status');

        $query = Hood::with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('model', 'like', '%' . $search . '%');
            });
        }

        if ($category) {
            $query->where('category_id', $category);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status !== null && $status !== '') {
            if (method_exists(Hood::class, 'scopeByStatus')) {
                $query->byStatus($status);
            }
        }

        $hoods = $query->latest()->paginate(10)->withQueryString();

        $totalHoods   = Hood::count();
        $sellingHoods = Hood::where('is_active', true)->where('stock_quantity', '>', 0)->count();
        $soldOutHoods = Hood::where(function ($q) {
            $q->where('is_active', false)->orWhere('stock_quantity', '<=', 0);
        })->count();

        $categories = Category::all();
        $types = [
            'wall-mounted'  => 'Treo tường',
            'under-cabinet' => 'Gắn dưới tủ',
            'island'        => 'Đảo',
            'cooktop'       => 'Bếp điện từ',
        ];

        return view('hoods.index', compact('hoods', 'totalHoods', 'sellingHoods', 'soldOutHoods', 'categories', 'types', 'search', 'category', 'type', 'status'));
    }

    /**
     * Giao diện thêm sản phẩm mới
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $types = [
            'wall-mounted'  => 'Treo tường',
            'under-cabinet' => 'Gắn dưới tủ',
            'island'        => 'Đảo',
            'cooktop'       => 'Bếp điện từ',
        ];

        return view('hoods.create', compact('categories', 'types'));
    }

    /**
     * Xử lý lưu sản phẩm mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'model'           => ['nullable', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'category_id'     => ['required', 'exists:categories,id'],
            'price'           => ['nullable', 'numeric', 'min:0'],
            'power'           => ['nullable', 'string', 'max:100'],
            'dimensions'      => ['nullable', 'string', 'max:255'],
            'color'           => ['nullable', 'string', 'max:100'],
            'manufacturer'    => ['nullable', 'string', 'max:255'],
            'material'        => ['nullable', 'string', 'max:255'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:240'],
            'type'            => ['required', 'in:wall-mounted,under-cabinet,island,cooktop'],
            'status'          => ['required', 'in:selling,importing,sold_out'],
            'stock_quantity'  => ['nullable', 'integer', 'min:0'],
            'image'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ], [
            'name.required'        => 'Vui lòng nhập tên sản phẩm.',
            'category_id.required' => 'Vui lòng chọn danh mục sản phẩm.',
            'category_id.exists'   => 'Danh mục được chọn không tồn tại.',
            'type.required'        => 'Vui lòng chọn loại máy hút mùi.',
            'type.in'              => 'Loại máy hút mùi không hợp lệ.',
            'status.required'      => 'Vui lòng chọn trạng thái sản phẩm.',
            'status.in'            => 'Trạng thái sản phẩm không hợp lệ.',
            'price.numeric'        => 'Giá sản phẩm phải là số.',
            'price.min'            => 'Giá sản phẩm không được nhỏ hơn 0.',
            'image.image'          => 'Tệp tải lên phải là hình ảnh.',
            'image.mimes'          => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif, webp.',
            'image.max'            => 'Dung lượng ảnh tối đa là 2MB.',
        ]);

        $status = $validated['status'];
        unset($validated['status']);

        if ($status === 'selling') {
            $validated['is_active'] = true;
            $validated['stock_quantity'] = max((int) ($validated['stock_quantity'] ?? 0), 1);
        } elseif ($status === 'importing') {
            $validated['is_active'] = true;
            $validated['stock_quantity'] = 0;
        } else {
            $validated['is_active'] = false;
            $validated['stock_quantity'] = 0;
        }

        // Lưu ảnh trực tiếp vào public/uploads/hoods
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            $image->move(public_path('uploads/hoods'), $imageName);
            $validated['image'] = 'uploads/hoods/' . $imageName;
        }

        Hood::create($validated);

        return redirect()->route('hoods.index')->with('success', 'Thêm máy hút mùi thành công!');
    }

    /**
     * Xem chi tiết sản phẩm trong quản trị
     */
    public function show(Hood $hood)
    {
        $hood->load('category');
        return view('hoods.show', compact('hood'));
    }

    /**
     * Giao diện chỉnh sửa sản phẩm
     */
    public function edit(Hood $hood)
    {
        $categories = Category::all();
        $types = [
            'wall-mounted'  => 'Treo tường',
            'under-cabinet' => 'Gắn dưới tủ',
            'island'        => 'Đảo',
            'cooktop'       => 'Bếp điện từ',
        ];

        return view('hoods.edit', compact('hood', 'categories', 'types'));
    }

    /**
     * Cập nhật thông tin sản phẩm
     */
    public function update(Request $request, Hood $hood)
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'model'           => ['nullable', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'category_id'     => ['required', 'exists:categories,id'],
            'price'           => ['nullable', 'numeric', 'min:0'],
            'power'           => ['nullable', 'string', 'max:100'],
            'dimensions'      => ['nullable', 'string', 'max:255'],
            'color'           => ['nullable', 'string', 'max:100'],
            'manufacturer'    => ['nullable', 'string', 'max:255'],
            'material'        => ['nullable', 'string', 'max:255'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:240'],
            'type'            => ['required', 'in:wall-mounted,under-cabinet,island,cooktop'],
            'status'          => ['required', 'in:selling,importing,sold_out'],
            'stock_quantity'  => ['nullable', 'integer', 'min:0'],
            'image'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ], [
            'name.required'        => 'Vui lòng nhập tên sản phẩm.',
            'category_id.required' => 'Vui lòng chọn danh mục sản phẩm.',
            'category_id.exists'   => 'Danh mục được chọn không tồn tại.',
            'type.required'        => 'Vui lòng chọn loại máy hút mùi.',
            'type.in'              => 'Loại máy hút mùi không hợp lệ.',
            'status.required'      => 'Vui lòng chọn trạng thái sản phẩm.',
            'status.in'            => 'Trạng thái sản phẩm không hợp lệ.',
            'price.numeric'        => 'Giá sản phẩm phải là số.',
            'price.min'            => 'Giá sản phẩm không được nhỏ hơn 0.',
            'image.image'          => 'Tệp tải lên phải là hình ảnh.',
            'image.mimes'          => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif, webp.',
            'image.max'            => 'Dung lượng ảnh tối đa là 2MB.',
        ]);

        $status = $validated['status'];
        unset($validated['status']);

        if ($status === 'selling') {
            $validated['is_active'] = true;
            $validated['stock_quantity'] = max((int) ($validated['stock_quantity'] ?? 0), 1);
        } elseif ($status === 'importing') {
            $validated['is_active'] = true;
            $validated['stock_quantity'] = 0;
        } else {
            $validated['is_active'] = false;
            $validated['stock_quantity'] = 0;
        }

        // Cập nhật và lưu ảnh vào public/uploads/hoods
        if ($request->hasFile('image')) {
            if ($hood->image && file_exists(public_path($hood->image))) {
                @unlink(public_path($hood->image));
            } elseif ($hood->image && Storage::disk('public')->exists($hood->image)) {
                Storage::disk('public')->delete($hood->image);
            }

            $image = $request->file('image');
            $imageName = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            $image->move(public_path('uploads/hoods'), $imageName);
            $validated['image'] = 'uploads/hoods/' . $imageName;
        }

        $hood->update($validated);

        return redirect()->route('hoods.index')->with('success', 'Cập nhật máy hút mùi thành công!');
    }

    /**
     * Xóa sản phẩm
     */
    public function destroy(Hood $hood)
    {
        if ($hood->image && file_exists(public_path($hood->image))) {
            @unlink(public_path($hood->image));
        } elseif ($hood->image && Storage::disk('public')->exists($hood->image)) {
            Storage::disk('public')->delete($hood->image);
        }

        $hood->delete();
        return redirect()->route('hoods.index')->with('success', 'Xóa máy hút mùi thành công!');
    }
}