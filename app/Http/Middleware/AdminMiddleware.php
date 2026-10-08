<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Xử lý kiểm tra quyền truy cập vào khu vực Admin
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Nếu chưa đăng nhập: Chuyển hướng đến trang Đăng nhập
        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập tài khoản quản trị.');
        }

        // 2. Kiểm tra quyền Admin / Staff (Hỗ trợ cả hàm helper trong User Model lẫn cột 'role' trực tiếp)
        $hasAccess = ($user->role && in_array($user->role, ['admin', 'staff']))
            || (method_exists($user, 'isAdmin') && $user->isAdmin())
            || (method_exists($user, 'isStaff') && $user->isStaff());

        // 3. Nếu là Khách hàng (không có quyền): Chuyển hướng về Trang chủ thay vì hiện 403
        if (!$hasAccess) {
            return redirect()->route('storefront')->with('error', 'Bạn không có quyền truy cập trang quản trị.');
        }

        return $next($request);
    }
}