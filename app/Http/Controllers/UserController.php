<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Danh sách tài khoản
     */
    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $role   = $request->get('role', 'all');

        $query = User::query();

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role !== 'all' && !empty($role)) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'role'));
    }

    /**
     * Thêm tài khoản mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,staff,user',
        ], [
            'name.required'     => 'Vui lòng nhập tên người dùng.',
            'email.required'    => 'Vui lòng nhập email.',
            'email.unique'      => 'Email này đã tồn tại trên hệ thống.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min'      => 'Mật khẩu phải chứa ít nhất 6 ký tự.',
        ]);

        User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'role'              => $validated['role'],
            'email_verified_at' => now(), // Tự động xác minh tài khoản do Admin tạo
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Thêm tài khoản mới thành công!');
    }

    /**
     * Cập nhật thông tin / Đặt lại mật khẩu mới
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'     => 'required|in:admin,staff,user',
            'password' => 'nullable|string|min:6',
        ], [
            'name.required' => 'Vui lòng nhập tên người dùng.',
            'email.required'=> 'Vui lòng nhập email.',
            'email.unique'  => 'Email này đã thuộc về tài khoản khác.',
            'password.min'  => 'Mật khẩu mới phải từ 6 ký tự trở lên.',
        ]);

        $updateData = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => $validated['role'],
        ];

        // Nếu Admin nhập mật khẩu mới thì tiến hành cập nhật mã hóa mới
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('admin.users.index')->with('success', "Cập nhật tài khoản {$user->email} thành công!");
    }

    /**
     * Xóa tài khoản
     */
    public function destroy(User $user)
    {
        // Ràng buộc bảo vệ: Không cho phép Admin tự xóa tài khoản đang đăng nhập
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'Bạn không thể tự xóa tài khoản đang đăng nhập của chính mình!');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "Đã xóa tài khoản {$user->email} thành công!");
    }
}