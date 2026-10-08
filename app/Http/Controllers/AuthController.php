<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Vui lòng nhập địa chỉ email.',
            'email.email'       => 'Email không đúng định dạng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Kiểm tra trạng thái khóa tài khoản nếu CSDL có cột status
            if (Schema::hasColumn('users', 'status') && isset($user->status) && $user->status == 0) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            if (in_array($user->role, ['admin', 'staff'])) {
                return redirect()->intended(route('admin.portal')); 
            }

            $intendedUrl = session()->get('url.intended');
            if ($intendedUrl && str_contains($intendedUrl, '/admin')) {
                session()->forget('url.intended');
            }

            return redirect()->intended(route('storefront'));
        }

        return back()->withErrors([
            'email' => 'Địa chỉ email hoặc mật khẩu không chính xác.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ], [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'email.required' => 'Vui lòng nhập địa chỉ Gmail.',
            'email.unique' => 'Địa chỉ Gmail này đã được đăng ký tài khoản.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Mật khẩu xác nhận không trùng khớp.',
        ]);

        $userData = [
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'customer',
        ];

        if (Schema::hasColumn('users', 'status')) {
            $userData['status'] = 1;
        }

        $user = User::create($userData);

        // Tự động đăng nhập
        Auth::login($user);

        // Gửi email xác thực
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::error('Lỗi gửi email xác thực khi đăng ký: ' . $e->getMessage());
        }

        // Chuyển hướng trực tiếp đến trang xác minh email
        return redirect()->route('verification.notice')->with('success', 'Đăng ký tài khoản thành công! Vui lòng kiểm tra Gmail để xác minh tài khoản.');
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $userData = [
                    'name'              => $googleUser->getName(),
                    'email'             => $googleUser->getEmail(),
                    'google_id'         => $googleUser->getId(),
                    'email_verified_at' => now(),
                    'password'          => bcrypt(Str::random(16)),
                    'role'              => 'customer',
                ];

                if (Schema::hasColumn('users', 'status')) {
                    $userData['status'] = 1;
                }

                $user = User::create($userData);
            } else {
                if (Schema::hasColumn('users', 'google_id')) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
                if (!$user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
            }

            Auth::login($user);
            return redirect()->route('storefront')->with('success', 'Đăng nhập thành công bằng tài khoản Google!');
        } catch (\Throwable $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'Không thể đăng nhập bằng Google. Vui lòng thử lại.']);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}