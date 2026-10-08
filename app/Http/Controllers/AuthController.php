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
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Xử lý đăng nhập thông thường (Email & Mật khẩu)
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/')->with('status', 'Đăng nhập thành công!');
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ])->onlyInput('email');
    }

    /**
     * Chuyển hướng người dùng sang trang xác thực của Google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Xử lý dữ liệu callback trả về từ Google
     */
    public function handleGoogleCallback()
    {
        try {
            // stateless() giúp khắc phục triệt để lỗi Session State / HTTPS Proxy trên Render
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser || !$googleUser->getEmail()) {
                return redirect()->route('login')->with('error', 'Không lấy được thông tin email từ Google.');
            }

            // Tìm tài khoản theo email hoặc google_id
            $user = User::where('email', $googleUser->getEmail())
                ->orWhere('google_id', $googleUser->getId())
                ->first();

            if (!$user) {
                // Tạo tài khoản mới nếu chưa tồn tại
                $user = User::create([
                    'name'              => $googleUser->getName() ?? 'Khách hàng Google',
                    'email'             => $googleUser->getEmail(),
                    'google_id'         => $googleUser->getId(),
                    'password'          => Hash::make(Str::random(16)), // Mật khẩu ngẫu nhiên tránh lỗi NOT NULL trong CSDL
                    'email_verified_at' => now(),                        // Tự động xác minh email
                    'role'              => 'customer',
                    'status'            => 1,
                ]);
            } else {
                // Cập nhật google_id và email_verified_at nếu người dùng đã có tài khoản từ trước
                $user->update([
                    'google_id'         => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            // Kiểm tra nếu tài khoản bị khóa
            if ($user->isBlocked()) {
                return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa.');
            }

            // Đăng nhập người dùng vào phiên làm việc
            Auth::login($user, true);

            return redirect()->intended('/')->with('status', 'Đăng nhập bằng Google thành công!');

        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Không thể đăng nhập bằng Google. Vui lòng thử lại.');
        }
    }

    /**
     * Đăng xuất người dùng
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Đã đăng xuất thành công!');
    }
}