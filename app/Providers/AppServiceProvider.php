<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

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
        // 1. Ép toàn bộ link/API/ảnh sinh ra phải chạy qua HTTPS trên server Render
        if ($this->app->environment('production') || request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        // 2. Tự động kiểm tra và xử lý symlink hỏng, kết nối public/storage với storage/app/public
        $publicStoragePath = public_path('storage');
        $targetStoragePath = storage_path('app/public');

        if (file_exists($targetStoragePath)) {
            if (is_link($publicStoragePath)) {
                @unlink($publicStoragePath);
            }

            if (!file_exists($publicStoragePath)) {
                app('files')->link($targetStoragePath, $publicStoragePath);
            }
        }

        // Cấu hình phân trang Bootstrap 5
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