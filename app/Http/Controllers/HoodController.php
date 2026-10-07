<?php

namespace App\Http\Controllers;

use App\Models\Hood;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HoodController extends Controller
{
    /**
     * Tự động xóa ảnh cũ trong thư mục storage/app/public/hoods/
     */
    private function deleteOldImage(?string $path): void
    {
        if (!$path) {
            return;
        }

        // Xóa file trong storage/app/public/
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        // Xóa file nếu tồn tại trực tiếp trong public/
        if (file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }

    /**
     * Tự động xử lý và lưu ảnh duy nhất vào thư mục storage/app/public/hoods/
     */
    private function handleImageUpload(Request $request, ?string $oldImagePath = null): ?string
    {
        // 1. Trường hợp người dùng chọn Tải file ảnh trực tiếp
        if ($request->hasFile('image')) {
            $this->deleteOldImage($oldImagePath);
            return $request->file('image')->store('hoods', 'public');
        }

        // 2. Trường hợp người dùng Copy-Paste ảnh Base64 vào ô nhập
        $imageInput = $request->input('image');
        if (is_string($imageInput) && Str::startsWith($imageInput, 'data:image')) {
            $this->deleteOldImage($oldImagePath);

            preg_match('/data:image\/(\w+);base64,/', $imageInput, $type);
            $extension = strtolower($type[1] ?? 'png');
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            $imageData = base64_decode(substr($imageInput, strpos($imageInput, ',') + 1));
            $fileName = 'hoods/hood_' . uniqid() . '.' . $extension;

            // Lưu trực tiếp vào storage/app/public/hoods/
            Storage::disk('public')->put($fileName, $imageData);

            return $fileName;
        }

        // 3. Giữ nguyên đường dẫn ảnh cũ nếu không có thay đổi
        if (is_string($imageInput) && !empty($imageInput) && !Str::startsWith($imageInput, 'data:image')) {
            return $imageInput;
        }

        return $oldImagePath;
    }

    /**
     * 1. GIAO DIỆN BÁN HÀNG (STOREFRONT)
     */
    public function storefront(Request $request)
    {
        $search     = $request->get('search');
        $category   = $request->get('category_id') ?? $request->get('category');
        $priceRange = $request->get('price_range');

        $query = Hood::with('category')->where('is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('model', 'like', '%' . $search . '%');
            });
        }

        if ($category) {
            $query->where('category_id', $category);
        }

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
        $hoods      = $products;
        $categories = Category::where('is_active', true)->get();

        return view('storefront', compact('products', 'hoods', 'categories', 'search', 'category', 'priceRange'));
    }

    public function storefrontShow($id)
    {
        $hood = $id instanceof Hood ? $id : Hood::with('category')->findOrFail($id);
        $product = $hood;

        return view('storefront_detail', compact('hood', 'product'));
    }

    /**
     * 2. TRANG QUẢN TRỊ (ADMIN CRUD)
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
            'image'           => ['nullable'],
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

        // Lưu ảnh vào hoods
        $validated['image'] = $this->handleImageUpload($request);

        Hood::create($validated);

        return redirect()->route('hoods.index')->with('success', 'Thêm máy hút mùi thành công!');
    }

    public function show(Hood $hood)
    {
        $hood->load('category');
        return view('hoods.show', compact('hood'));
    }

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
            'image'           => ['nullable'],
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

        // Cập nhật ảnh vào hoods
        if ($request->hasFile('image') || $request->filled('image')) {
            $validated['image'] = $this->handleImageUpload($request, $hood->image);
        }

        $hood->update($validated);

        return redirect()->route('hoods.index')->with('success', 'Cập nhật máy hút mùi thành công!');
    }

    public function destroy(Hood $hood)
    {
        $this->deleteOldImage($hood->image);

        $hood->delete();
        return redirect()->route('hoods.index')->with('success', 'Xóa máy hút mùi thành công!');
    }
}