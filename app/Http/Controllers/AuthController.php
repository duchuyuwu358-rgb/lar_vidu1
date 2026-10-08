<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Hiển thị giao diện đăng nhập
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Xử lý đăng nhập
     */
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

            // 1. Kiểm tra nếu tài khoản bị khóa (status = 0)
            if (isset($user->status) && $user->status == 0) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            // 2. Phân luồng chuyển hướng theo Role
            if (in_array($user->role, ['admin', 'staff'])) {
                return redirect()->intended(route('admin.dashboard')); 
            }

            // 3. Xóa url.intended nếu thuộc trang admin
            $intendedUrl = session()->get('url.intended');
            if ($intendedUrl && str_contains($intendedUrl, '/admin')) {
                session()->forget('url.intended');
            }

            // Chuyển hướng Khách hàng
            return redirect()->intended(route('storefront'));
        }

        return back()->withErrors([
            'email' => 'Địa chỉ email hoặc mật khẩu không chính xác.',
        ])->onlyInput('email');
    }

    /**
     * Hiển thị giao diện đăng ký
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Xử lý đăng ký tài khoản
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[\pL\s]+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'password_confirmation' => [
                'required',
            ],
        ], [
            'name.required'                  => 'Vui lòng nhập họ và tên.',
            'name.min'                       => 'Họ và tên phải có ít nhất 2 ký tự.',
            'name.max'                       => 'Họ và tên không được vượt quá 50 ký tự.',
            'name.regex'                     => 'Họ và tên chỉ được chứa chữ cái và khoảng trắng.',

            'email.required'                 => 'Vui lòng nhập địa chỉ Gmail.',
            'email.email'                    => 'Địa chỉ email không đúng định dạng.',
            'email.unique'                   => 'Địa chỉ Gmail này đã được đăng ký tài khoản.',
            'email.regex'                    => 'Vui lòng nhập đúng định dạng Gmail (ví dụ: example@gmail.com).',

            'password.required'              => 'Vui lòng nhập mật khẩu.',
            'password.min'                   => 'Mật khẩu phải có tối thiểu 8 ký tự.',
            'password.confirmed'             => 'Mật khẩu xác nhận không trùng khớp.',

            'password_confirmation.required' => 'Vui lòng xác nhận lại mật khẩu.',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'customer',
            'status'   => 1,
        ]);

        // Xử lý gửi email xác thực an toàn bằng try-catch
        $mailSent = true;
        $errorMessage = null;

        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::error('Lỗi gửi email xác thực khi đăng ký: ' . $e->getMessage());
            $mailSent = false;
            $errorMessage = $e->getMessage();
        }

        // Tự động đăng nhập phiên làm việc cho user
        Auth::login($user);

        // Thông báo tùy theo trạng thái gửi thư
        if ($mailSent) {
            return redirect()->route('verification.notice')->with('success', 'Đăng ký tài khoản thành công! Vui lòng kiểm tra hòm thư Gmail để xác minh.');
        }

        return redirect()->route('verification.notice')->with('error', $errorMessage ?? 'Hệ thống không thể kết nối tới máy chủ gửi mail lúc này.');
    }

    /**
     * Xử lý đăng xuất
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withHeaders([
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => 'Sat, 01 Jan 1990 00:00:00 GMT',
        ]);
    }
}