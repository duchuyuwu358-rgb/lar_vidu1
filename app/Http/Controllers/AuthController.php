<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        // Nếu đã đăng nhập rồi thì chuyển hướng thẳng, không hiển thị lại form login
        if (Auth::check()) {
            return redirect()->route(Auth::user()->isAdmin() ? 'admin.portal' : 'storefront');
        }

        // Trả về view login và CẤM Trình duyệt Cache trang này (Sửa lỗi 419 khi Logout/Login lại)
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->isAdmin() ? 'admin.portal' : 'storefront');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email:rfc,dns', 'lowercase', 'ends_with:@gmail.com', 'unique:users,email'],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        // Xóa URL intended để tránh bị chuyển hướng vào các route cũ trong session
        session()->forget('url.intended');

        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Điều hướng trực tiếp theo vai trò
        return redirect()->route($request->user()->isAdmin() ? 'admin.portal' : 'storefront');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Chuyển hướng về login kèm Header xóa triệt để cache trình duyệt
        return redirect()->route('login')->withHeaders([
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => 'Sat, 01 Jan 1990 00:00:00 GMT',
        ]);
    }
}