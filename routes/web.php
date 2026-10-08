<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

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

// Middlewares
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes - XFAN Store
|--------------------------------------------------------------------------
*/

// Redirect trang chủ về Storefront
Route::get('/', fn () => redirect()->route('storefront'));

// Trang Storefront công khai (Dành cho khách truy cập)
Route::get('/storefront', [HoodController::class, 'storefront'])->name('storefront');
Route::get('/storefront/{id}', [HoodController::class, 'storefrontShow'])->name('storefront.show');

// Dịch vụ Vệ sinh & Lắp đặt
Route::get('/dich-vu', [ServicePackageController::class, 'index'])->name('services.index');
Route::match(['get', 'post'], '/dich-vu/add-to-cart/{id}', [ServicePackageController::class, 'addToCart'])->name('services.addToCart');
Route::match(['get', 'post'], '/dich-vu/add-to-cart-alias/{id}', [ServicePackageController::class, 'addToCart'])->name('services.add_to_cart');

// MoMo Callbacks & IPN (Thanh toán trực tuyến)
Route::get('/payment/momo/callback', [MomoController::class, 'callback'])->name('user.payment.momo.callback');
Route::post('/payment/momo/ipn', [MomoController::class, 'ipn'])->name('payment.momo.ipn');

// GHN Webhook (Bỏ qua kiểm tra CSRF Token cho API từ GHN)
Route::post('/ghn/webhook', [OrderController::class, 'handleGhnWebhook'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
    ])
    ->name('ghn.webhook');

// ==========================================
// 1. GUEST ROUTES (Dành cho khách chưa đăng nhập)
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

    // Email Verification (Xác minh Email)
    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        $user = $request->user();
        $isAdminOrStaff = in_array($user->role, ['admin', 'staff']);

        return redirect()->route($isAdminOrStaff ? 'admin.portal' : 'storefront');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('storefront');
        }
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'Đã gửi lại email xác minh.');
    })->middleware('throttle:6,1')->name('verification.send');

    // CHỨC NĂNG NGƯỜI DÙNG (Yêu cầu xác minh Email)
    Route::middleware('verified')->group(function () {
        // Giỏ hàng & Thanh toán
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::get('/cart-alias', [CartController::class, 'index'])->name('cart');
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        
        Route::match(['get', 'post', 'delete'], '/cart/remove/{key}', [CartController::class, 'remove'])->name('cart.remove');
        
        Route::get('/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
        Route::post('/checkout', [CartController::class, 'processCheckout'])->name('cart.checkout.process');
        Route::post('/checkout-alias', [CartController::class, 'processCheckout'])->name('cart.processCheckout');

        // Quản lý Đơn hàng
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

        // API Địa giới hành chính (GHN)
        Route::prefix('locations')->name('locations.')->group(function () {
            Route::get('/provinces', [CartController::class, 'getProvinces'])->name('provinces');
            Route::get('/districts/{provinceId}', [CartController::class, 'getDistricts'])->name('districts');
            Route::get('/wards/{districtId}', [CartController::class, 'getWards'])->name('wards');
            Route::post('/calculate-fee', [CartController::class, 'getShippingFee'])->name('fee');
        });

        // Alias đường dẫn API GHN hỗ trợ Javascript
        Route::get('/cart/api/provinces', [CartController::class, 'getProvinces']);
        Route::get('/cart/api/districts/{provinceId}', [CartController::class, 'getDistricts']);
        Route::get('/cart/api/wards/{districtId}', [CartController::class, 'getWards']);
        Route::post('/cart/api/shipping-fee', [CartController::class, 'getShippingFee']);

        // Thanh toán MoMo
        Route::get('/payment/momo/start/{order}', [MomoController::class, 'startPayment'])->name('user.orders.momo.start');
    });

    // ==========================================
    // 3. ADMIN ROUTES (Quản trị viên & Nhân viên)
    // ==========================================
    Route::middleware([AdminMiddleware::class])
        ->prefix('admin')
        ->group(function () {

            // Admin Dashboard & Export
            Route::get('/portal', [AdminPortalController::class, 'index'])->name('admin.portal');
            
            // Bổ sung Alias admin.dashboard tương thích với AuthController
            Route::get('/dashboard', fn() => redirect()->route('admin.portal'))->name('admin.dashboard');
            
            Route::get('/portal/export-excel', [AdminPortalController::class, 'exportExcel'])->name('admin.portal.export');

            // Quản lý đơn hàng Admin
            Route::get('/orders', [OrderController::class, 'adminIndex'])->name('admin.orders.index');
            Route::get('/orders/{id}', [OrderController::class, 'show'])->name('admin.orders.show');
            Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.updateStatus');
            Route::post('/orders/{id}/sync-ghn', [OrderController::class, 'syncGhnStatus'])->name('admin.orders.syncGhn');
            Route::post('/orders/{id}/push-ghn', [OrderController::class, 'pushToGhn'])->name('admin.orders.pushGhn');

            // Admin Livechat
            Route::get('/chat', [ChatController::class, 'index'])->name('admin.chat');
            Route::get('/chat/users', [ChatController::class, 'getAdminUsers'])->name('admin.chat.users');
            Route::get('/chat/messages/{userId}', [ChatController::class, 'getAdminMessages'])->name('admin.chat.messages');
            Route::post('/chat/send', [ChatController::class, 'sendAdminMessage'])->name('admin.chat.send');
            Route::get('/chat/unread-count', [ChatController::class, 'checkAdminUnread'])->name('admin.chat.unread');

            // Resource Routes CRUD
            Route::resource('categories', CategoryController::class)->names('admin.categories');
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy'])->names('admin.users');
            Route::resource('hoods', HoodController::class)->names('admin.hoods');
            Route::resource('services', AdminServicePackageController::class)->names('admin.services');

            // ROUTE ALIASES (Tên route rút gọn)
            Route::name('categories.')->group(function () {
                Route::get('/categories-alias', [CategoryController::class, 'index'])->name('index');
                Route::get('/categories-alias/create', [CategoryController::class, 'create'])->name('create');
                Route::post('/categories-alias', [CategoryController::class, 'store'])->name('store');
                Route::get('/categories-alias/{category}', [CategoryController::class, 'show'])->name('show');
                Route::get('/categories-alias/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
                Route::put('/categories-alias/{category}', [CategoryController::class, 'update'])->name('update');
                Route::delete('/categories-alias/{category}', [CategoryController::class, 'destroy'])->name('destroy');
            });

            Route::name('hoods.')->group(function () {
                Route::get('/hoods-alias', [HoodController::class, 'index'])->name('index');
                Route::get('/hoods-alias/create', [HoodController::class, 'create'])->name('create');
                Route::post('/hoods-alias', [HoodController::class, 'store'])->name('store');
                Route::get('/hoods-alias/{hood}', [HoodController::class, 'show'])->name('show');
                Route::get('/hoods-alias/{hood}/edit', [HoodController::class, 'edit'])->name('edit');
                Route::put('/hoods-alias/{hood}', [HoodController::class, 'update'])->name('update');
                Route::delete('/hoods-alias/{hood}', [HoodController::class, 'destroy'])->name('destroy');
            });

            Route::name('users.')->group(function () {
                Route::get('/users-alias', [UserController::class, 'index'])->name('index');
                Route::post('/users-alias', [UserController::class, 'store'])->name('store');
                Route::put('/users-alias/{user}', [UserController::class, 'update'])->name('update');
                Route::delete('/users-alias/{user}', [UserController::class, 'destroy'])->name('destroy');
            });

            // Alias Routes v2
            Route::get('/v2/categories', [CategoryController::class, 'index'])->name('admin.categories.v2.index');
            Route::get('/v2/categories/create', [CategoryController::class, 'create'])->name('admin.categories.v2.create');
            Route::get('/v2/hoods', [HoodController::class, 'index'])->name('admin.hoods.v2.index');
            Route::get('/v2/hoods/create', [HoodController::class, 'create'])->name('admin.hoods.v2.create');
            Route::get('/v2/users', [UserController::class, 'index'])->name('admin.users.v2.index');
            Route::get('/v2/services', [AdminServicePackageController::class, 'index'])->name('admin.services.v2.index');
        });
});

// ========================================================
// 4. ROUTE ĐỌC ẢNH TRỰC TIẾP TỪ STORAGE (ĐÃ NÂNG CẤP BẢO MẬT)
// ========================================================
Route::get('/storage/{path}', function ($path) {
    // Chặn nguy cơ Directory Traversal tấn công lấy file hệ thống
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