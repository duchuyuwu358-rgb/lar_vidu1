<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Cho phép cả Admin lẫn Nhân viên (staff) truy cập vào trang admin
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        return $next($request);
    }
}