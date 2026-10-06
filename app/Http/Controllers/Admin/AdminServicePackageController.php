<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServicePackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminServicePackageController extends Controller
{
    /**
     * Danh sách dịch vụ + Tìm kiếm & Lọc trạng thái
     */
    public function index(Request $request)
    {
        $query = ServicePackage::query();

        // 1. Tìm kiếm theo tên
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . trim($request->search) . '%');
        }

        // 2. Lọc theo trạng thái (1: Còn hàng, 0: Hết hàng)
        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $services = $query->latest()->paginate(10)->withQueryString();

        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:service_packages,name',
            'price' => 'required|numeric|min:0|max:1000000000',
            'is_active' => 'required|in:0,1',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên gói dịch vụ.',
            'name.max' => 'Tên gói dịch vụ không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên gói dịch vụ này đã tồn tại trên hệ thống.',
            'price.required' => 'Vui lòng nhập giá dịch vụ.',
            'price.numeric' => 'Giá dịch vụ phải là định dạng số.',
            'price.min' => 'Giá dịch vụ không được nhỏ hơn 0đ.',
            'price.max' => 'Giá dịch vụ vượt quá giới hạn cho phép.',
            'is_active.required' => 'Vui lòng chọn trạng thái dịch vụ.',
            'is_active.in' => 'Trạng thái được chọn không hợp lệ.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
            'image.image' => 'Tệp tải lên phải là tệp hình ảnh.',
            'image.mimes' => 'Hình ảnh chỉ chấp nhận định dạng: jpeg, png, jpg, webp.',
            'image.max' => 'Dung lượng ảnh không được vượt quá 2MB.',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('services', 'public');
        }

        ServicePackage::create($data);

        return redirect()->route('admin.services.index')->with('status', 'Thêm gói dịch vụ thành công!');
    }

    public function edit($id)
    {
        $service = ServicePackage::findOrFail($id);
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, $id)
    {
        $service = ServicePackage::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:service_packages,name,' . $id,
            'price' => 'required|numeric|min:0|max:1000000000',
            'is_active' => 'required|in:0,1',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên gói dịch vụ.',
            'name.max' => 'Tên gói dịch vụ không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên gói dịch vụ này đã trùng với một gói khác.',
            'price.required' => 'Vui lòng nhập giá dịch vụ.',
            'price.numeric' => 'Giá dịch vụ phải là định dạng số.',
            'price.min' => 'Giá dịch vụ không được nhỏ hơn 0đ.',
            'price.max' => 'Giá dịch vụ vượt quá giới hạn cho phép.',
            'is_active.required' => 'Vui lòng chọn trạng thái dịch vụ.',
            'is_active.in' => 'Trạng thái được chọn không hợp lệ.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
            'image.image' => 'Tệp tải lên phải là tệp hình ảnh.',
            'image.mimes' => 'Hình ảnh chỉ chấp nhận định dạng: jpeg, png, jpg, webp.',
            'image.max' => 'Dung lượng ảnh không được vượt quá 2MB.',
        ]);

        if ($request->hasFile('image')) {
            if ($service->image && Storage::disk('public')->exists($service->image)) {
                Storage::disk('public')->delete($service->image);
            }
            $data['image'] = $request->file('image')->store('services', 'public');
        }

        $service->update($data);

        return redirect()->route('admin.services.index')->with('status', 'Cập nhật dịch vụ thành công!');
    }

    public function destroy($id)
    {
        $service = ServicePackage::findOrFail($id);

        if ($service->image && Storage::disk('public')->exists($service->image)) {
            Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        return redirect()->back()->with('status', 'Đã xóa dịch vụ thành công!');
    }
}