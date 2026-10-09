<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * 1. Hiển thị danh sách danh mục (có tìm kiếm, lọc & phân trang)
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');

        $query = Category::withCount('hoods');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($status !== null && $status !== '') {
            $isActive = in_array($status, ['active', '1', true], true);
            $query->where('is_active', $isActive);
        }

        $categories = $query->paginate(10)->appends($request->all());
        $totalCategories = Category::count();
        $activeCategories = Category::where('is_active', true)->count();

        return view('categories.index', compact('categories', 'search', 'status', 'totalCategories', 'activeCategories'));
    }

    /**
     * 2. Giao diện thêm danh mục mới
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * 3. Xử lý lưu danh mục mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'in:active,inactive,1,0'],
            'is_active'   => ['nullable', 'in:1,0,true,false,active,inactive'],
            'colors'      => ['nullable', 'array'],
            'colors.*'    => ['string', 'max:50'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.max'      => 'Tên danh mục không được vượt quá 255 ký tự.',
            'slug.unique'   => 'Đường dẫn (Slug) này đã tồn tại trên hệ thống.',
            'status.in'     => 'Trạng thái được chọn không hợp lệ.',
            'image.image'   => 'Tệp tải lên phải là hình ảnh.',
            'image.mimes'   => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif, webp.',
            'image.max'     => 'Dung lượng ảnh tối đa là 2MB.',
        ]);

        $statusInput = $request->input('status', $request->input('is_active', 'active'));
        $validated['is_active'] = in_array((string)$statusInput, ['active', '1', 'true'], true);
        unset($validated['status']);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['colors'] = $request->input('colors', []);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($validated);

        return redirect()->route('categories.index')->with('success', 'Thêm danh mục thành công!');
    }

    /**
     * 4. Hiển thị chi tiết danh mục
     */
    public function show(Category $category)
    {
        $hoods = $category->hoods()->paginate(10);
        return view('categories.show', compact('category', 'hoods'));
    }

    /**
     * 5. Giao diện chỉnh sửa danh mục
     */
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * 6. Cập nhật thông tin danh mục
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'in:active,inactive,1,0'],
            'is_active'   => ['nullable', 'in:1,0,true,false,active,inactive'],
            'colors'      => ['nullable', 'array'],
            'colors.*'    => ['string', 'max:50'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.max'      => 'Tên danh mục không được vượt quá 255 ký tự.',
            'slug.unique'   => 'Đường dẫn (Slug) này đã tồn tại trên hệ thống.',
            'status.in'     => 'Trạng thái được chọn không hợp lệ.',
            'image.image'   => 'Tệp tải lên phải là hình ảnh.',
            'image.mimes'   => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif, webp.',
            'image.max'     => 'Dung lượng ảnh tối đa là 2MB.',
        ]);

        $statusInput = $request->input('status', $request->input('is_active', 'active'));
        $validated['is_active'] = in_array((string)$statusInput, ['active', '1', 'true'], true);
        unset($validated['status']);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['colors'] = $request->input('colors', []);

        if ($request->hasFile('image')) {
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Cập nhật danh mục thành công!');
    }

    /**
     * 7. Xóa danh mục
     */
    public function destroy(Category $category)
    {
        if ($category->hoods()->exists()) {
            return redirect()->route('categories.index')->with('error', 'Không thể xóa danh mục này vì đang có sản phẩm thuộc danh mục!');
        }

        if ($category->image && Storage::disk('public')->exists($category->image)) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Xóa danh mục thành công!');
    }
}