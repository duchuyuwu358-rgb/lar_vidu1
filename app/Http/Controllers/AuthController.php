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
     * Hiển thị trang đăng ký
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('storefront');
        }

        if (view()->exists('auth.register')) {
            return view('auth.register');
        }
        return view('register');
    }

    /**
     * Xử lý đăng ký tài khoản mới
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'name.required'      => 'Vui lòng nhập họ và tên.',
            'email.required'     => 'Vui lòng nhập địa chỉ email.',
            'email.email'        => 'Email không đúng định dạng.',
            'email.unique'       => 'Email này đã được đăng ký sử dụng.',
            'password.required'  => 'Vui lòng nhập mật khẩu.',
            'password.min'       => 'Mật khẩu phải chứa ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'customer',
            'status'   => 1,
        ]);

        // Gửi email xác minh
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('Lỗi gửi email xác minh đăng ký: ' . $e->getMessage());
        }

        // Đăng nhập tự động & tái tạo session
        Auth::login($user);
        $request->session()->regenerate();

        // CHUYỂN HƯỚNG TRỰC TIẾP SANG TRANG XÁC MINH GMAIL (/email/verify)
        return redirect()->route('verification.notice')->with('success', 'Đăng ký thành công! Vui lòng kiểm tra Gmail để xác minh tài khoản.');
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
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser || !$googleUser->getEmail()) {
                return redirect()->route('login')->with('error', 'Không lấy được thông tin email từ Google.');
            }

            $email    = $googleUser->getEmail();
            $googleId = $googleUser->getId();

            $user = User::where('email', $email)
                ->orWhere('google_id', $googleId)
                ->first();

            if (!$user) {
                $user = User::create([
                    'name'              => $googleUser->getName() ?? 'Khách hàng Google',
                    'email'             => $email,
                    'google_id'         => $googleId,
                    'password'          => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                    'role'              => 'customer',
                    'status'            => 1,
                ]);
            } else {
                $user->update([
                    'google_id'         => $googleId,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            if (method_exists($user, 'isBlocked') && $user->isBlocked()) {
                return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa.');
            }

            Auth::login($user, true);
            request()->session()->regenerate();

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