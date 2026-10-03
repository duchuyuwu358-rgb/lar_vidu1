<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // 1. Hiển thị danh sách người dùng (có tìm kiếm & lọc)
    public function index(Request $request)
    {
        $query = User::latest();

        // Tìm theo tên hoặc email
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
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

    // 2. Thêm tài khoản mới
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,customer,user',
        ], [
            'email.unique' => 'Email này đã tồn tại trên hệ thống.',
            'password.min' => 'Mật khẩu phải từ 6 ký tự trở lên.',
        ]);

        User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'role'              => $request->role,
            'email_verified_at' => now(), // Tự động xác minh email khi Admin tạo
        ]);

        return back()->with('success', 'Thêm tài khoản thành công!');
    }

    // 3. Cập nhật vai trò người dùng
    public function update(Request $request, User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Bạn không thể tự thay đổi quyền của chính mình!');
        }

        $request->validate([
            'role' => 'required|in:admin,customer,user',
        ]);

        $user->update([
            'role' => $request->role,
        ]);

        return back()->with('success', 'Đã cập nhật quyền thành công!');
    }

    // 4. Xóa tài khoản
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Bạn không thể tự xóa tài khoản của chính mình!');
        }

        $user->delete();

        return back()->with('success', 'Đã xóa tài khoản thành công!');
    }
}