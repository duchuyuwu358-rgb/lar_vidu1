<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 1. Khai báo Middleware Alias
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);

        // 2. Bỏ qua kiểm tra CSRF Token cho IPN MoMo & Webhook GHN
        $middleware->validateCsrfTokens(except: [
            'payment/momo/ipn',
            'payment/momo/*',
            'ghn/webhook',
        ]);

        // 3. Tin tưởng Proxy trên Render (Đảm bảo nhận đúng HTTPS và Cookie Session)
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();