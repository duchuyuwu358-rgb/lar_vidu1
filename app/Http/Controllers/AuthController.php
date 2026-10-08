<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Hiển thị trang đăng nhập
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('storefront');
        }
        return view('auth.login');
    }

    public function showLoginForm()
    {
        return $this->showLogin();
    }

    /**
     * Xử lý đăng nhập bằng Email & Mật khẩu
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->route('storefront')->with('status', 'Đăng nhập thành công!');
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ])->onlyInput('email');
    }

    /**
     * Chuyển hướng sang Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Xử lý dữ liệu Google Callback trả về
     */
    public function handleGoogleCallback()
    {
        try {
            // Lấy thông tin user từ Google qua stateless()
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser || !$googleUser->getEmail()) {
                return redirect()->route('login')->with('error', 'Không lấy được thông tin email từ Google.');
            }

            $email    = $googleUser->getEmail();
            $googleId = $googleUser->getId();

            // Tìm user theo email hoặc google_id
            $user = User::where('email', $email)
                ->orWhere('google_id', $googleId)
                ->first();

            if (!$user) {
                // Tạo mới nếu chưa có tài khoản
                $user = User::create([
                    'name'              => $googleUser->getName() ?? 'Khách hàng Google',
                    'email'             => $email,
                    'google_id'         => $googleId,
                    'password'          => Hash::make(Str::random(16)), // Mật khẩu ngẫu nhiên tránh lỗi NOT NULL CSDL
                    'email_verified_at' => now(),                        // Tự động xác minh email
                    'role'              => 'customer',
                    'status'            => 1,
                ]);
            } else {
                // Cập nhật google_id vào CSDL Aiven nếu tài khoản đã tồn tại
                $user->update([
                    'google_id'         => $googleId,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            if ($user->isBlocked()) {
                return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa.');
            }

            // Đăng nhập người dùng & Tái tạo Session
            Auth::login($user, true);
            request()->session()->regenerate();

            // Đẩy thẳng về trang Storefront thay vì dùng intended() tránh bị dính lại trang /login
            return redirect()->route('storefront')->with('status', 'Đăng nhập bằng Google thành công!');

        } catch (\Throwable $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Lỗi Google Auth: ' . $e->getMessage());
        }
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront')->with('status', 'Đã đăng xuất thành công!');
    }
}