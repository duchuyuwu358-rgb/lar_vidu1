<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

// Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HoodController;
use App\Http\Controllers\MomoController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminPortalController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\ServicePackageController;
use App\Http\Controllers\Admin\AdminServicePackageController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\SupportController;

// Middlewares
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes - XFAN Store
|--------------------------------------------------------------------------
*/

// Redirect trang chủ về Storefront
Route::get('/', fn () => redirect()->route('storefront'));

// Trang Storefront công khai
Route::get('/storefront', [HoodController::class, 'storefront'])->name('storefront');
Route::get('/storefront/{id}', [HoodController::class, 'storefrontShow'])->name('storefront.show');

// CHỨC NĂNG GỬI THƯ HỖ TRỢ PHÍA KHÁCH HÀNG
Route::get('/ho-tro', [SupportController::class, 'showForm'])->name('user.support.form');
Route::post('/ho-tro/send', [SupportController::class, 'sendSupport'])->name('user.support.send');

// Dịch vụ Vệ sinh & Lắp đặt
Route::get('/dich-vu', [ServicePackageController::class, 'index'])->name('services.index');
Route::match(['get', 'post'], '/dich-vu/add-to-cart/{id}', [ServicePackageController::class, 'addToCart'])->name('services.addToCart');
Route::match(['get', 'post'], '/dich-vu/add-to-cart-alias/{id}', [ServicePackageController::class, 'addToCart'])->name('services.add_to_cart');

// Google OAuth
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// MoMo Callbacks & IPN
Route::get('/payment/momo/callback', [MomoController::class, 'callback'])->name('user.payment.momo.callback');
Route::post('/payment/momo/ipn', [MomoController::class, 'ipn'])->name('payment.momo.ipn');

// GHN Webhook
Route::post('/ghn/webhook', [OrderController::class, 'handleGhnWebhook'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
    ])
    ->name('ghn.webhook');

// ==========================================
// 1. GUEST ROUTES (Chưa đăng nhập)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');

    if (method_exists(AuthController::class, 'showForgotPassword')) {
        Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    }
});

// ==========================================
// 2. AUTHENTICATED ROUTES (Đã đăng nhập)
// ==========================================
Route::middleware('auth')->group(function () {

    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

    // Livechat Khách hàng
    Route::prefix('user/chat')->name('user.chat.')->group(function () {
        Route::get('/messages', [ChatController::class, 'getUserMessages'])->name('messages');
        Route::post('/send', [ChatController::class, 'sendUserMessage'])->name('send');
        Route::get('/unread-count', [ChatController::class, 'checkUserUnread'])->name('unread');
        Route::post('/mark-as-read', [ChatController::class, 'markUserRead'])->name('markRead');
    });

    // Email Verification
    Route::get('/email/verify', function (Request $request) {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('storefront')
            : view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        $user = $request->user();
        $isAdminOrStaff = in_array($user->role ?? '', ['admin', 'staff']);

        return redirect()->route($isAdminOrStaff ? 'admin.portal' : 'storefront')
            ->with('success', 'Xác minh Gmail thành công!');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('storefront');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
            return back()->with('success', 'Đã gửi lại email xác minh! Vui lòng kiểm tra hòm thư.');
        } catch (\Throwable $e) {
            Log::error('Lỗi gửi mail xác minh: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    })->middleware('throttle:6,1')->name('verification.send');

    // CHỨC NĂNG NGƯỜI DÙNG CÓ XÁC MINH EMAIL
    Route::middleware('verified')->group(function () {
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::get('/cart-alias', [CartController::class, 'index'])->name('cart');
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::match(['get', 'post', 'delete'], '/cart/remove/{key}', [CartController::class, 'remove'])->name('cart.remove');
        
        // ROUTE ÁP DỤNG & HỦY MÃ GIẢM GIÁ
        Route::post('/cart/apply-coupon', [CartController::class, 'applyCoupon'])->name('cart.applyCoupon');
        Route::post('/cart/remove-coupon', [CartController::class, 'removeCoupon'])->name('cart.removeCoupon');

        Route::get('/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
        Route::post('/checkout', [CartController::class, 'processCheckout'])->name('cart.checkout.process');
        Route::post('/checkout-alias', [CartController::class, 'processCheckout'])->name('cart.processCheckout');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

        Route::prefix('locations')->name('locations.')->group(function () {
            Route::get('/provinces', [CartController::class, 'getProvinces'])->name('provinces');
            Route::get('/districts/{provinceId}', [CartController::class, 'getDistricts'])->name('districts');
            Route::get('/wards/{districtId}', [CartController::class, 'getWards'])->name('wards');
            Route::post('/calculate-fee', [CartController::class, 'getShippingFee'])->name('fee');
        });

        Route::get('/cart/api/provinces', [CartController::class, 'getProvinces']);
        Route::get('/cart/api/districts/{provinceId}', [CartController::class, 'getDistricts']);
        Route::get('/cart/api/wards/{districtId}', [CartController::class, 'getWards']);
        Route::post('/cart/api/shipping-fee', [CartController::class, 'getShippingFee']);

        Route::get('/payment/momo/start/{order}', [MomoController::class, 'startPayment'])->name('user.orders.momo.start');
    });

    // ==========================================
    // 3. ADMIN & STAFF ROUTES (Quản trị viên & Nhân viên)
    // ==========================================
    Route::middleware([AdminMiddleware::class])
        ->prefix('admin')
        ->group(function () {

            // Dashboard & Thống kê
            Route::get('/portal', [AdminPortalController::class, 'index'])->name('admin.portal');
            Route::get('/dashboard', fn() => redirect()->route('admin.portal'))->name('admin.dashboard');
            Route::get('/portal/export-excel', [AdminPortalController::class, 'exportExcel'])->name('admin.portal.export');

            // Quản lý Hòm Thư Hỗ Trợ (Khách hàng gửi đến)
            Route::get('/support-requests', [SupportController::class, 'adminIndex'])->name('admin.support.index');
            Route::post('/support-requests/{id}/reply', [SupportController::class, 'adminReply'])->name('admin.support.reply');
            Route::delete('/support-requests/{id}', [SupportController::class, 'destroy'])->name('admin.support.destroy');

            // Quản lý Đơn hàng
            Route::get('/orders', [OrderController::class, 'adminIndex'])->name('admin.orders.index');
            Route::get('/orders/{id}', [OrderController::class, 'show'])->name('admin.orders.show');
            Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.updateStatus');
            Route::post('/orders/{id}/sync-ghn', [OrderController::class, 'syncGhnStatus'])->name('admin.orders.syncGhn');
            Route::post('/orders/{id}/push-ghn', [OrderController::class, 'pushToGhn'])->name('admin.orders.pushGhn');

            // Livechat
            Route::get('/chat', [ChatController::class, 'index'])->name('admin.chat');
            Route::get('/chat/users', [ChatController::class, 'getAdminUsers'])->name('admin.chat.users');
            Route::get('/chat/messages/{userId}', [ChatController::class, 'getAdminMessages'])->name('admin.chat.messages');
            Route::post('/chat/send', [ChatController::class, 'sendAdminMessage'])->name('admin.chat.send');
            Route::get('/chat/unread-count', [ChatController::class, 'checkAdminUnread'])->name('admin.chat.unread');

            // Khai báo trọn bộ Resource hỗ trợ 100% cả 2 kiểu đặt tên route
            Route::resource('categories', CategoryController::class)->names('admin.categories');
            Route::resource('categories-short', CategoryController::class)->names('categories');

            Route::resource('hoods', HoodController::class)->names('admin.hoods');
            Route::resource('hoods-short', HoodController::class)->names('hoods');

            Route::resource('services', AdminServicePackageController::class)->names('admin.services');
            Route::resource('services-short', AdminServicePackageController::class)->names('services');

            Route::resource('coupons', CouponController::class)->names('admin.coupons');
            Route::resource('coupons-short', CouponController::class)->names('coupons');

            // Thư Hỗ trợ Bán hàng & Khuyến mại Email
            Route::get('/send-promotion-mail', [AdminPortalController::class, 'showPromotionForm'])->name('admin.promotion.form');
            Route::post('/send-promotion-mail', [AdminPortalController::class, 'sendPromotionMail'])->name('admin.promotion.send');

            // Quản lý Người dùng
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy'])->names('admin.users');

            Route::name('users.')->group(function () {
                Route::get('/users-alias', [UserController::class, 'index'])->name('index');
                Route::post('/users-alias', [UserController::class, 'store'])->name('store');
                Route::put('/users-alias/{user}', [UserController::class, 'update'])->name('update');
                Route::delete('/users-alias/{user}', [UserController::class, 'destroy'])->name('destroy');
            });

            Route::get('/v2/users', [UserController::class, 'index'])->name('admin.users.v2.index');
        });
});

// ========================================================
// 4. STORAGE IMAGE ROUTE
// ========================================================
Route::get('/storage/{path}', function ($path) {
    if (str_contains($path, '..')) {
        abort(403, 'Forbidden');
    }

    $filePath = storage_path('app/public/' . $path);

    if (!File::exists($filePath)) {
        abort(404);
    }

    $file = File::get($filePath);
    $type = File::mimeType($filePath);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
})->where('path', '.*');