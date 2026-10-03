<?php

namespace App\Http\Controllers;

use App\Models\Hood;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HoodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $type = $request->get('type');
        $status = $request->get('status');

        $query = Hood::with('category');

        // Tìm kiếm theo tên hoặc model (Gom nhóm điều kiện WHERE OR)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('model', 'like', '%' . $search . '%');
            });
        }

        // Lọc theo danh mục
        if ($category) {
            $query->where('category_id', $category);
        }

        // Lọc theo loại
        if ($type) {
            $query->where('type', $type);
        }

        // Lọc theo trạng thái: đang bán, đang nhập, hết hàng
        if ($status !== null && $status !== '') {
            $query->byStatus($status);
        }

        // Phân trang và giữ query string trên URL
        $hoods = $query->paginate(10)->withQueryString();

        $totalHoods = Hood::count();
        $sellingHoods = Hood::where('is_active', true)->where('stock_quantity', '>', 0)->count();
        $soldOutHoods = Hood::where(function ($query) {
            $query->where('is_active', false)->orWhere('stock_quantity', '<=', 0);
        })->count();

        $categories = Category::all();
        $types = [
            'wall-mounted' => 'Treo tường',
            'under-cabinet' => 'Gắn dưới tủ',
            'island' => 'Đảo',
            'cooktop' => 'Bếp điện từ',
        ];

        return view('hoods.index', compact('hoods', 'totalHoods', 'sellingHoods', 'soldOutHoods', 'categories', 'types', 'search', 'category', 'type', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        $types = [
            'wall-mounted' => 'Treo tường',
            'under-cabinet' => 'Gắn dưới tủ',
            'island' => 'Đảo',
            'cooktop' => 'Bếp điện từ',
        ];
        
        return view('hoods.create', compact('categories', 'types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'nullable|numeric|min:0',
            'power' => 'nullable|string|max:100',
            'dimensions' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'material' => 'nullable|string|max:255',
            'warranty_months' => 'nullable|integer|min:0|max:240',
            'type' => 'required|in:wall-mounted,under-cabinet,island,cooktop',
            'status' => 'required|in:selling,importing,sold_out',
            'stock_quantity' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

        // Xử lý upload ảnh
        if ($request->hasFile('image')) {
            $folder = 'hoods';
            Storage::disk('public')->makeDirectory($folder);

            $image = $request->file('image');
            $imageName = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs($folder, $imageName, 'public');
            $validated['image'] = $path;
        }

        Hood::create($validated);

        return redirect()->route('hoods.index')->with('success', 'Thêm máy hút mùi thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Hood $hood)
    {
        $hood->load('category');
        return view('hoods.show', compact('hood'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hood $hood)
    {
        $categories = Category::all();
        $types = [
            'wall-mounted' => 'Treo tường',
            'under-cabinet' => 'Gắn dưới tủ',
            'island' => 'Đảo',
            'cooktop' => 'Bếp điện từ',
        ];
        
        return view('hoods.edit', compact('hood', 'categories', 'types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Hood $hood)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'nullable|numeric|min:0',
            'power' => 'nullable|string|max:100',
            'dimensions' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'material' => 'nullable|string|max:255',
            'warranty_months' => 'nullable|integer|min:0|max:240',
            'type' => 'required|in:wall-mounted,under-cabinet,island,cooktop',
            'status' => 'required|in:selling,importing,sold_out',
            'stock_quantity' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

        // Xử lý upload ảnh
        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu có
            if ($hood->image && Storage::disk('public')->exists($hood->image)) {
                Storage::disk('public')->delete($hood->image);
            }

            $folder = 'hoods';
            Storage::disk('public')->makeDirectory($folder);

            $image = $request->file('image');
            $imageName = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs($folder, $imageName, 'public');
            $validated['image'] = $path;
        }

        $hood->update($validated);

        return redirect()->route('hoods.index')->with('success', 'Cập nhật máy hút mùi thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hood $hood)
    {
        if ($hood->image && Storage::disk('public')->exists($hood->image)) {
            Storage::disk('public')->delete($hood->image);
        }

        $hood->delete();
        return redirect()->route('hoods.index')->with('success', 'Xóa máy hút mùi thành công!');
    }
}