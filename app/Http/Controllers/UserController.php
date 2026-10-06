<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * 1. Hiển thị danh sách người dùng (có tìm kiếm & lọc phân trang)
     */
    public function index(Request $request)
    {
        $query = User::latest();

        // Tìm theo tên hoặc email
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Lọc theo vai trò (role)
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(10)->appends($request->all());

        return view('admin.users.index', compact('users'));
    }

    /**
     * 2. Thêm tài khoản mới (Tự động xác minh email để truy cập ngay)
     */
    public function store(Request $request)
    {
        // Kiểm tra quyền Admin an toàn
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return back()->with('error', 'Bạn không có quyền thêm tài khoản!');
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL\s]+$/u'],
            'email'    => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', Rule::in(['admin', 'staff', 'customer', 'user'])],
        ], [
            'name.required'     => 'Vui lòng nhập họ và tên.',
            'name.min'          => 'Họ và tên phải có ít nhất 2 ký tự.',
            'name.max'          => 'Họ và tên không được vượt quá 50 ký tự.',
            'name.regex'        => 'Họ và tên chỉ được chứa chữ cái và khoảng trắng.',
            'email.required'    => 'Vui lòng nhập địa chỉ email.',
            'email.email'       => 'Địa chỉ email không đúng định dạng.',
            'email.unique'      => 'Địa chỉ email này đã tồn tại trên hệ thống.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min'      => 'Mật khẩu phải từ 8 ký tự trở lên.',
            'role.required'     => 'Vui lòng chọn vai trò cho người dùng.',
            'role.in'           => 'Vai trò được chọn không hợp lệ.',
        ]);

        User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'role'              => $validated['role'],
            'email_verified_at' => now(), // Tự động xác minh ngay
        ]);

        return back()->with('success', 'Thêm tài khoản thành công! Tài khoản đã được tự động xác minh.');
    }

    /**
     * 3. Cập nhật thông tin / vai trò người dùng
     */
    public function update(Request $request, User $user)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return back()->with('error', 'Bạn không có quyền chỉnh sửa tài khoản!');
        }

        if (auth()->id() === $user->id && $request->filled('role') && $request->role !== $user->role) {
            return back()->with('error', 'Bạn không thể tự thay đổi quyền của chính mình!');
        }

        $rules = [
            'role' => ['required', Rule::in(['admin', 'staff', 'customer', 'user'])],
        ];

        if ($request->has('name')) {
            $rules['name'] = ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL\s]+$/u'];
        }
        if ($request->has('email')) {
            $rules['email'] = ['required', 'string', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
        }
        if ($request->filled('password')) {
            $rules['password'] = ['nullable', 'string', 'min:8'];
        }

        $validated = $request->validate($rules, [
            'name.required'  => 'Vui lòng nhập họ và tên.',
            'name.regex'     => 'Họ và tên chỉ chứa chữ cái và khoảng trắng.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email'    => 'Địa chỉ email không đúng định dạng.',
            'email.unique'   => 'Email này đã thuộc về tài khoản khác.',
            'password.min'   => 'Mật khẩu mới phải từ 8 ký tự trở lên.',
            'role.required'  => 'Vui lòng chọn vai trò.',
            'role.in'        => 'Vai trò được chọn không hợp lệ.',
        ]);

        $dataToUpdate = ['role' => $validated['role']];
        if (isset($validated['name'])) $dataToUpdate['name'] = $validated['name'];
        if (isset($validated['email'])) $dataToUpdate['email'] = $validated['email'];
        if (!empty($validated['password'])) $dataToUpdate['password'] = Hash::make($validated['password']);

        $user->update($dataToUpdate);

        return back()->with('success', 'Đã cập nhật thông tin người dùng thành công!');
    }

    /**
     * 4. Xóa tài khoản
     */
    public function destroy(User $user)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return back()->with('error', 'Bạn không có quyền xóa tài khoản!');
        }

        if (auth()->id() === $user->id) {
            return back()->with('error', 'Bạn không thể tự xóa tài khoản của chính mình!');
        }

        $user->delete();

        return back()->with('success', 'Đã xóa tài khoản thành công!');
    }
}