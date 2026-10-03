<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator; // Thêm Paginator

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cấu hình phân trang Bootstrap 5 (Sửa lỗi vỡ icon/mũi tên)
        Paginator::useBootstrapFive();

        // Cấu hình Mail xác thực Email
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Xác Nhận Địa Chỉ Email - XFAN Store')
                ->greeting('Xin chào!')
                ->line('Vui lòng bấm vào nút bên dưới để hoàn tất xác minh tài khoản của bạn.')
                ->action('Xác Minh Tài Khoản', $url)
                ->line('Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!')
                ->salutation('Trân trọng, XFAN Store');
        });
    }
}