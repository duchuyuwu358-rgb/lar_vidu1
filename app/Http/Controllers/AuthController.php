<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

            // 2. Phân luồng chuyển hướng theo Role để tránh lỗi 403
            if (in_array($user->role, ['admin', 'staff'])) {
                // Nếu là Admin/Nhân viên: chuyển hướng vào trang Admin
                return redirect()->intended(route('admin.dashboard')); 
            }

            // 3. Nếu là Khách hàng: Xóa url.intended nếu URL đó thuộc trang admin
            $intendedUrl = session()->get('url.intended');
            if ($intendedUrl && str_contains($intendedUrl, '/admin')) {
                session()->forget('url.intended');
            }

            // Chuyển hướng Khách hàng về trang chủ
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
                'regex:/^[\pL\s]+$/u', // Chấp nhận chữ cái có dấu, không dấu và khoảng trắng
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', // Định dạng @gmail.com
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed', // Khớp với password_confirmation
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
            'role'     => 'customer', // Mặc định tài khoản mới là Khách hàng
            'status'   => 1,          // Mặc định trạng thái Hoạt động
        ]);

        Auth::login($user);

        return redirect()->route('storefront')->with('success', 'Đăng ký tài khoản thành công!');
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