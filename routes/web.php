<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\Verified;

use App\Models\Hood;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HoodController;
use App\Http\Controllers\MomoController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminPortalController;
use App\Http\Controllers\Admin\ChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect trang chủ về Storefront
Route::get('/', fn () => redirect()->route('storefront'));

// Trang Storefront công khai
Route::get('/storefront', function () {
    $hoods = Hood::with('category')
        ->where('is_active', true)
        ->where('stock_quantity', '>', 0)
        ->latest()
        ->get();

    return view('storefront', compact('hoods'));
})->name('storefront');

Route::get('/storefront/{hood}', function (Hood $hood) {
    $hood->load('category');
    return view('storefront_detail', compact('hood'));
})->name('storefront.show');

// MoMo Callbacks
Route::get('/payment/momo/callback', [MomoController::class, 'callback'])->name('user.payment.momo.callback');
Route::post('/payment/momo/ipn', [MomoController::class, 'ipn'])->name('payment.momo.ipn');

// GHN Webhook (Bỏ kiểm tra CSRF Token để Postman & GHN gửi request được)
Route::post('/ghn/webhook', [OrderController::class, 'handleGhnWebhook'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
    ])
    ->name('ghn.webhook');

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {

    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

    // Livechat User
    Route::get('/user/chat/messages', [ChatController::class, 'getUserMessages'])->name('user.chat.messages');
    Route::post('/user/chat/send', [ChatController::class, 'sendUserMessage'])->name('user.chat.send');
    Route::get('/user/chat/unread-count', [ChatController::class, 'checkUserUnread'])->name('user.chat.unread');
    Route::post('/user/chat/mark-as-read', [ChatController::class, 'markUserRead'])->name('user.chat.markRead');

    // Email Verification
    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->isAdmin() ? 'admin.portal' : 'storefront');
        }
        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }
        return redirect()->route($request->user()->isAdmin() ? 'admin.portal' : 'storefront');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('storefront');
        }
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'Đã gửi lại email xác minh.');
    })->middleware('throttle:6,1')->name('verification.send');

    // Verified User Routes
    Route::middleware('verified')->group(function () {
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
        Route::get('/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
        Route::post('/checkout', [CartController::class, 'processCheckout'])->name('cart.checkout.process');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

        Route::prefix('locations')->name('locations.')->group(function () {
            Route::get('/provinces', [CartController::class, 'getProvinces'])->name('provinces');
            Route::get('/districts/{provinceId}', [CartController::class, 'getDistricts'])->name('districts');
            Route::get('/wards/{districtId}', [CartController::class, 'getWards'])->name('wards');
            Route::post('/calculate-fee', [CartController::class, 'getShippingFee'])->name('fee');
        });

        Route::get('/payment/momo/start/{order}', [MomoController::class, 'startPayment'])->name('user.orders.momo.start');
    });

    // Admin Routes
    Route::middleware(['verified', \App\Http\Middleware\AdminMiddleware::class])->prefix('admin')->group(function () {
        Route::get('/portal', [AdminPortalController::class, 'index'])->name('admin.portal');
        Route::get('/portal/export-excel', [AdminPortalController::class, 'exportExcel'])->name('admin.portal.export');

        // Quản lý Đơn hàng Admin
        Route::get('/orders', [OrderController::class, 'adminIndex'])->name('admin.orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('admin.orders.show');
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.updateStatus');
        
        // GHN Routes
        Route::post('/orders/{id}/sync-ghn', [OrderController::class, 'syncGhnStatus'])->name('admin.orders.syncGhn');
        Route::post('/orders/{id}/push-ghn', [OrderController::class, 'pushToGhn'])->name('admin.orders.pushGhn');

        // Livechat Admin
        Route::get('/chat/users', [ChatController::class, 'getAdminUsers'])->name('admin.chat.users');
        Route::get('/chat/messages/{userId}', [ChatController::class, 'getAdminMessages'])->name('admin.chat.messages');
        Route::post('/chat/send', [ChatController::class, 'sendAdminMessage'])->name('admin.chat.send');
        Route::get('/chat/unread-count', [ChatController::class, 'checkAdminUnread'])->name('admin.chat.unread');

        // CRUD
        Route::resource('categories', CategoryController::class);
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('hoods', HoodController::class);
    });
});