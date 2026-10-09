<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'XFAN Store'))</title>

    <!-- Bootstrap 5 CSS & FontAwesome & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-bg: #111827;
            --sidebar-color: #9ca3af;
            --sidebar-active-bg: #1f2937;
            --sidebar-active-color: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            background-color: #f8fafc;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* ADMIN & STAFF SIDEBAR */
        body.admin-body { padding-left: var(--sidebar-width) !important; }
        .admin-sidebar {
            width: var(--sidebar-width) !important;
            height: 100vh;
            position: fixed !important;
            top: 0; left: 0;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-color);
            z-index: 1040;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
            overflow: hidden !important;
        }
        .sidebar-brand {
            padding: 0.85rem 1.25rem; font-size: 1.15rem; font-weight: 700; color: #ffffff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center;
            flex-shrink: 0;
        }
        .sidebar-user { 
            padding: 0.65rem 1rem; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.1); 
            flex-shrink: 0;
        }
        .sidebar-user .avatar {
            width: 42px; height: 42px; background-color: #374151; color: #fff;
            border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.05rem; margin: 0 auto 4px auto;
        }
        .sidebar-menu { 
            list-style: none; padding: 0.35rem 0; margin: 0; 
            flex-grow: 1 !important; 
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-evenly !important;
            overflow-y: auto !important; 
            min-height: 0 !important;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        .sidebar-menu::-webkit-scrollbar { display: none !important; }
        .sidebar-menu .nav-link {
            padding: 0.5rem 1.25rem; color: var(--sidebar-color);
            display: flex; align-items: center; gap: 12px; font-size: 0.92rem; text-decoration: none;
            transition: all 0.2s ease; font-weight: 500;
        }
        .sidebar-menu .nav-link i { font-size: 1.05rem; }
        .sidebar-menu .nav-link:hover, .sidebar-menu .nav-link.active {
            color: var(--sidebar-active-color); background-color: var(--sidebar-active-bg); border-left: 4px solid #3b82f6;
        }
        .sidebar-footer { 
            padding: 0.75rem 1.25rem; border-top: 1px solid rgba(255, 255, 255, 0.1); 
            flex-shrink: 0 !important; background-color: var(--sidebar-bg);
        }
        .sidebar-footer .btn { padding: 0.45rem 0.75rem; font-size: 0.875rem; font-weight: 600; }
        .admin-main-content { width: 100% !important; min-height: 100vh; padding: 1.25rem 1.75rem; }

        /* USER NAVBAR HEADER */
        .user-navbar { 
            background-color: #ffffff; 
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .user-nav-link {
            color: #4b5563; 
            text-decoration: none; 
            font-weight: 500; 
            padding: 0.45rem 0.65rem;
            font-size: 0.88rem;
            border-radius: 8px; 
            transition: all 0.2s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px;
            white-space: nowrap !important;
            line-height: 1.2;
        }
        .user-nav-link i { font-size: 0.95rem; }
        .user-nav-link:hover { color: #2563eb; background-color: #f1f5f9; }
        .user-nav-link.active { color: #2563eb; font-weight: 600; background-color: #eff6ff; }

        /* NÚT TỔNG: ĐẶT Ở GÓC DƯỚI BÊN PHẢI (RIGHT: 24px) */
        .floating-chat-btn {
            position: fixed !important;
            bottom: 24px !important;
            right: 24px !important;
            z-index: 99999 !important;
            cursor: pointer;
        }

        /* KHUNG CHAT KHÁCH HÀNG */
        .custom-chat-panel {
            position: fixed !important;
            bottom: 85px !important;
            right: 24px !important;
            width: 380px !important;
            max-width: 92vw !important;
            height: 560px !important;
            background: #f8fafc !important;
            border-radius: 16px !important;
            z-index: 100000 !important;
            box-shadow: 0 12px 35px rgba(0,0,0,0.18) !important;
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .custom-chat-panel.active { display: flex !important; }

        /* KHUNG CHAT NHÂN VIÊN: CẤU TRÚC 2 CỘT Ở GÓC DƯỚI BÊN PHẢI */
        .custom-chat-panel-staff {
            position: fixed !important;
            bottom: 85px !important;
            right: 24px !important;
            width: 620px !important;
            max-width: 92vw !important;
            height: 540px !important;
            background: #ffffff !important;
            border-radius: 16px !important;
            z-index: 100000 !important;
            box-shadow: 0 12px 35px rgba(0,0,0,0.2) !important;
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #cbd5e1;
        }
        .custom-chat-panel-staff.active { display: flex !important; }
        
        .chat-header-dark {
            background-color: #0f172a; color: white; padding: 14px 18px;
            display: flex; justify-content: space-between; align-items: center;
        }

        /* NÚT TỰ ĐỘNG BOT */
        .chat-option-btn {
            background: #ffffff; color: #334155; border: 1px solid #e2e8f0;
            border-radius: 20px; padding: 10px 16px; font-size: 0.88rem; font-weight: 500;
            text-align: center; margin-bottom: 8px; width: 100%; transition: all 0.2s ease;
            cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .chat-option-btn:hover { background-color: #f1f5f9; border-color: #cbd5e1; color: #0284c7; transform: translateY(-1px); }
        .btn-resume-chat { background: #eff6ff !important; color: #2563eb !important; border: 1.5px solid #3b82f6 !important; font-weight: 700 !important; }
        .btn-resume-chat:hover { background: #dbeafe !important; }

        .chat-date-badge { text-align: center; font-size: 0.75rem; color: #64748b; margin: 10px 0; font-weight: 500; }
        .msg-bubble-sent {
            background-color: #2563eb; color: white; border-radius: 14px 14px 0px 14px;
            max-width: 82%; margin-left: auto; padding: 10px 14px; margin-bottom: 10px; font-size: 0.9rem; word-break: break-word;
        }
        .msg-bubble-received {
            background-color: #ffffff; color: #1e293b; border: 1px solid #e2e8f0;
            border-radius: 14px 14px 14px 0px; max-width: 85%; padding: 10px 14px; margin-bottom: 10px; font-size: 0.9rem; word-break: break-word;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .chat-user-item { cursor: pointer; transition: all 0.2s ease; }
        .chat-user-item:hover, .chat-user-item.active { background-color: #0d6efd !important; color: #ffffff !important; }
        .chat-user-item:hover .text-muted, .chat-user-item.active .text-muted { color: #e2e8f0 !important; }
    </style>
</head>

@php
    $user = auth()->user();
    
    // PHÂN QUYỀN: ADMIN VÀ NHÂN VIÊN
    $isAdmin = $user && (
        (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
        ($user->role ?? '') === 'admin'
    );

    $isStaff = $user && (
        (method_exists($user, 'isStaff') && $user->isStaff()) ||
        ($user->role ?? '') === 'staff'
    );

    $isAdminOrStaff = $isAdmin || $isStaff;
    $isAuthPage = request()->is('login') || request()->is('register');
    $isAdminManagementPage = request()->is('admin*') || request()->routeIs('admin.*');

    // LẤY DỮ LIỆU CỦA BOT
    $bestSeller = \App\Models\Hood::first();
    $minPrice = \App\Models\Hood::min('price') ?? 0;
    $maxPrice = \App\Models\Hood::max('price') ?? 0;

    $bestSellerName = $bestSeller->name ?? 'Máy Hút Mùi Cao Cấp XFAN';
    $bestSellerPrice = isset($bestSeller->price) ? number_format($bestSeller->price, 0, ',', '.') . 'đ' : 'Liên hệ';
    $minPriceFormatted = number_format($minPrice, 0, ',', '.') . 'đ';
    $maxPriceFormatted = number_format($maxPrice, 0, ',', '.') . 'đ';

    $latestOrderInfo = null;
    if (auth()->check()) {
        $latestOrder = \App\Models\Order::where('user_id', auth()->id())->latest()->first();
        if ($latestOrder) {
            $latestOrderInfo = [
                'id' => $latestOrder->id,
                'status' => $latestOrder->status ?? 'Đang xử lý',
                'created_at' => $latestOrder->created_at ? $latestOrder->created_at->format('H:i - d/m/Y') : 'Mới đây',
                'total' => number_format($latestOrder->total_price ?? $latestOrder->total ?? 0, 0, ',', '.') . 'đ'
            ];
        }
    }
@endphp

<body class="{{ $isAdminOrStaff ? 'admin-body' : 'user-body' }}">

    {{-- SIDEBAR CHO ADMIN VÀ NHÂN VIÊN --}}
    @if ($isAdminOrStaff)
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-fan text-primary fs-5 me-2"></i>
                <span>XFAN STORE</span>
            </div>
            
            <div class="sidebar-user">
                <div class="avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</div>
                <div class="fw-bold text-white small" style="font-size:0.88rem;">{{ Auth::user()->name ?? 'Tài khoản' }}</div>
                
                <div class="text-info mt-1" style="font-size: 0.78rem; font-weight: 600;">
                    <i class="bi bi-person-badge me-1"></i>
                    @if($isStaff)
                        Nhân viên tư vấn
                    @elseif($isAdmin)
                        Quản trị viên (Admin)
                    @else
                        {{ ucfirst(Auth::user()->role ?? 'Admin') }}
                    @endif
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="nav-item">
                    <a href="{{ Route::has('admin.portal') ? route('admin.portal') : url('/admin/portal') }}" class="nav-link {{ request()->routeIs('admin.portal') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('storefront') ? route('storefront') : url('/') }}" class="nav-link {{ request()->routeIs('storefront*') ? 'active' : '' }}">
                        <i class="bi bi-shop"></i><span>Xem Cửa Hàng</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('admin.hoods.index') ? route('admin.hoods.index') : (Route::has('hoods.index') ? route('hoods.index') : url('/admin/hoods')) }}" class="nav-link {{ request()->routeIs('*hoods*') ? 'active' : '' }}">
                        <i class="bi bi-box-seam"></i><span>Sản phẩm</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('admin.categories.index') ? route('admin.categories.index') : (Route::has('categories.index') ? route('categories.index') : url('/admin/categories')) }}" class="nav-link {{ request()->routeIs('*categories*') ? 'active' : '' }}">
                        <i class="bi bi-tags"></i><span>Danh mục</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('admin.services.index') ? route('admin.services.index') : (Route::has('services.index') ? route('services.index') : url('/admin/services')) }}" class="nav-link {{ request()->routeIs('*services*') ? 'active' : '' }}">
                        <i class="bi bi-wrench-adjustable-circle"></i><span>Gói dịch vụ</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('admin.coupons.index') ? route('admin.coupons.index') : (Route::has('coupons.index') ? route('coupons.index') : url('/admin/coupons')) }}" class="nav-link {{ request()->routeIs('*coupons*') ? 'active' : '' }}">
                        <i class="bi bi-ticket-perforated"></i><span>Khuyến mại</span>
                    </a>
                </li>

                {{-- CHỈ ADMIN MỚI HIỂN THỊ HÒM THƯ HỖ TRỢ VÀ GỬI THƯ HỖ TRỢ --}}
                @if($isAdmin)
                    <li class="nav-item">
                        <a href="{{ Route::has('admin.support.index') ? route('admin.support.index') : url('/admin/support-requests') }}" class="nav-link {{ request()->routeIs('*support-requests*') ? 'active' : '' }}">
                            <i class="bi bi-inbox-fill text-warning"></i><span>Hòm Thư Hỗ Trợ</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ Route::has('admin.promotion.form') ? route('admin.promotion.form') : url('/admin/send-promotion-mail') }}" class="nav-link {{ request()->routeIs('*promotion*') ? 'active' : '' }}">
                            <i class="bi bi-envelope-paper"></i><span>Gửi Thư Hỗ Trợ</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ Route::has('admin.users.index') ? route('admin.users.index') : (Route::has('users.index') ? route('users.index') : url('/admin/users')) }}" class="nav-link {{ request()->routeIs('*users*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i><span>Người dùng</span>
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a href="{{ Route::has('admin.orders.index') ? route('admin.orders.index') : (Route::has('orders.index') ? route('orders.index') : url('/admin/orders')) }}" class="nav-link {{ request()->routeIs('*orders*') ? 'active' : '' }}">
                        <i class="bi bi-receipt"></i><span>Đơn hàng</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <form action="{{ Route::has('logout') ? route('logout') : url('/logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-light w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-box-arrow-right"></i><span>Đăng xuất</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- KHUNG CHAT CỦA NHÂN VIÊN: GIỮ NGUYÊN BÓNG CHAT BÊN PHẢI + CỘT DANH SÁCH BÊN TRÁI --}}
        @if ($isStaff)
            <button type="button" id="btnAdminChatToggle" class="btn btn-dark rounded-pill shadow-lg px-3 py-2 floating-chat-btn position-relative">
                <i class="bi bi-chat-dots-fill text-warning me-1"></i>
                <span class="fw-bold">Chat Khách hàng</span>
                <span id="adminChatBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger p-2 border border-light d-none"></span>
            </button>

            <div id="adminChatPanel" class="custom-chat-panel-staff">
                <div class="chat-header-dark">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-headset text-warning me-2"></i> Trò Chuyện Khách Hàng</h6>
                    <button type="button" id="btnAdminChatClose" class="btn-close btn-close-white"></button>
                </div>

                <div class="d-flex flex-grow-1 overflow-hidden">
                    <!-- CỘT BÊN TRÁI: DANH SÁCH KHÁCH HÀNG -->
                    <div class="bg-light border-end p-2 overflow-auto" id="adminChatUsersList" style="width: 200px; min-width: 180px; flex-shrink: 0;">
                        <div class="text-center text-muted small py-3">Đang tải danh sách...</div>
                    </div>

                    <!-- CỘT BÊN PHẢI: MÀN HÌNH NHẮN TIN VÀ Ô NHẬP TIN -->
                    <div class="d-flex flex-column flex-grow-1 bg-white overflow-hidden">
                        <div class="flex-grow-1 p-3 overflow-auto bg-light" id="adminChatMessagesBox">
                            <div class="text-center text-muted small py-5">
                                <i class="bi bi-chat-square-dots fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                Chọn một khách hàng ở cột bên trái để xem tin nhắn.
                            </div>
                        </div>

                        <div class="p-2 border-top bg-white">
                            <form id="adminSendChatForm" class="input-group">
                                <input type="text" id="adminChatInput" class="form-control" placeholder="Nhập câu trả lời..." disabled required>
                                <button class="btn btn-primary px-3" type="submit" id="btnAdminSend" disabled><i class="bi bi-send-fill me-1"></i>Gửi</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- NAVBAR TOPBAR CỬA HÀNG KHÁCH HÀNG --}}
    @if (!$isAdminManagementPage)
        <nav class="navbar navbar-expand-xl navbar-light user-navbar py-2 mb-4">
            <div class="container-fluid px-3 px-lg-4 d-flex align-items-center justify-content-between flex-nowrap">
                
                {{-- LOGO + DANH SÁCH MENU --}}
                <div class="d-flex align-items-center gap-2 me-2">
                    <a class="navbar-brand fw-bold text-primary fs-5 m-0 d-flex align-items-center me-2 me-lg-3 text-nowrap" href="{{ Route::has('storefront') ? route('storefront') : url('/') }}">
                        <i class="fas fa-fan me-2"></i>XFAN STORE
                    </a>
                    <div class="d-flex align-items-center gap-1 border-start ps-2 ps-lg-3" style="border-color: #e5e7eb !important;">
                        <a href="{{ Route::has('storefront') ? route('storefront') : url('/') }}" class="user-nav-link {{ request()->routeIs('storefront*') || request()->is('/') ? 'active' : '' }}">
                            <i class="bi bi-shop"></i><span>Cửa hàng</span>
                        </a>
                        <a href="{{ Route::has('services.index') ? route('services.index') : url('/dich-vu') }}" class="user-nav-link {{ request()->routeIs('*services*') ? 'active' : '' }}">
                            <i class="bi bi-tools"></i><span>Dịch vụ & Lắp đặt</span>
                        </a>
                        
                        <!-- MỤC "THƯ HỖ TRỢ" DUY NHẤT -->
                        <a href="{{ Route::has('user.support.history') ? route('user.support.history') : url('/ho-tro/lich-su') }}" class="user-nav-link {{ request()->is('ho-tro*') ? 'active' : '' }}">
                            <i class="bi bi-headset"></i><span>Thư hỗ trợ</span>
                        </a>

                        <a href="{{ auth()->check() ? (Route::has('cart.index') ? route('cart.index') : url('/cart')) : (Route::has('login') ? route('login') : url('/login')) }}" class="user-nav-link {{ request()->routeIs('*cart*') ? 'active' : '' }}">
                            <i class="bi bi-cart3"></i><span>Giỏ hàng</span>
                        </a>
                        <a href="{{ auth()->check() ? (Route::has('orders.index') ? route('orders.index') : url('/orders')) : (Route::has('login') ? route('login') : url('/login')) }}" class="user-nav-link {{ request()->routeIs('*order*') ? 'active' : '' }}">
                            <i class="bi bi-clock-history"></i><span>Lịch sử đơn hàng</span>
                        </a>
                    </div>
                </div>

                {{-- THÔNG TIN TÀI KHOẢN & ĐĂNG XUẤT --}}
                <div class="d-flex align-items-center gap-2 text-nowrap flex-shrink-0 ms-auto">
                    @auth
                        @if (!$isAdminOrStaff)
                            <span class="text-secondary small me-1">Xin chào, <strong>{{ Auth::user()->name }}</strong></span>
                            
                            @if(!Auth::user()->hasVerifiedEmail())
                                <a href="{{ Route::has('verification.notice') ? route('verification.notice') : url('/email/verify') }}" 
                                   class="badge bg-warning text-dark border border-warning-subtle px-2 py-1 me-1 text-decoration-none" 
                                   style="font-size: 0.75rem;" 
                                   title="Bấm để mở trang xác minh email">
                                    ⚠️ Chưa xác minh
                                </a>
                            @endif

                            <form action="{{ Route::has('logout') ? route('logout') : url('/logout') }}" method="POST" class="d-inline m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger px-3 rounded-pill"><i class="bi bi-box-arrow-right me-1"></i> Đăng xuất</button>
                            </form>
                        @endif
                    @else
                        <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="btn btn-sm {{ request()->is('login') ? 'btn-primary' : 'btn-outline-primary' }} px-3 rounded-pill">Đăng nhập</a>
                        <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="btn btn-sm {{ request()->is('register') ? 'btn-primary' : 'btn-outline-primary' }} px-3 rounded-pill">Đăng ký</a>
                    @endauth
                </div>
            </div>
        </nav>
    @endif

    {{-- KHUNG CHAT BONG BÓNG KHÁCH HÀNG (BOT TỰ ĐỘNG + KẾT NỐI NHÂN VIÊN TƯ VẤN) --}}
    @if (!$isAdminOrStaff && !$isAuthPage)
        <button type="button" id="btnUserChatToggle" class="btn btn-primary rounded-pill shadow-lg px-3 py-2 floating-chat-btn position-relative">
            <i class="bi bi-chat-dots-fill me-1"></i>
            <span class="fw-bold">Nhắn tin cho chúng tôi</span>
        </button>

        <div id="userChatPanel" class="custom-chat-panel">
            <div class="chat-header-dark">
                <div>
                    <h6 class="mb-0 fw-bold fs-6">Nhắn tin cho chúng tôi</h6>
                    <span id="chatStatusSubtitle" class="text-white-50 extra-small" style="font-size: 0.75rem;">Hệ thống tư vấn tự động</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="btnExitStaffChat" class="btn btn-sm btn-outline-warning text-warning border-warning d-none px-2 py-1" style="font-size: 0.75rem; font-weight: 600;" onclick="exitStaffChat()">
                        <i class="bi bi-box-arrow-left me-1"></i>Thoát Nhân viên
                    </button>
                    <button type="button" id="btnUserChatClose" class="btn-close btn-close-white"></button>
                </div>
            </div>

            <div class="flex-grow-1 p-3 overflow-auto" id="userChatMessagesBox">
                <div class="chat-date-badge" id="chatTodayDate"></div>

                <!-- KHUNG BOT CÂU HỎI CHỌN NHANH -->
                <div id="botWelcomeContainer">
                    <div class="msg-bubble-received">
                        <div class="fw-bold mb-1 text-primary" style="font-size:0.8rem">XFAN Bot</div>
                        <div>Xin chào, tôi có thể giúp gì cho bạn? Vui lòng chọn một trong các yêu cầu bên dưới:</div>
                    </div>

                    <div id="quickOptionsGroup" class="mt-2">
                        <button type="button" id="btnContinueChat" class="chat-option-btn btn-resume-chat d-none" onclick="connectStaffChat(true)">
                            🔄 Tiếp tục cuộc trò chuyện với Nhân viên tư vấn
                        </button>

                        <button type="button" class="chat-option-btn" onclick="handleOptionSelect('buy_product', 'Mua sản phẩm & Máy hút mùi')">Mua sản phẩm & Máy hút mùi</button>
                        <button type="button" class="chat-option-btn" onclick="handleOptionSelect('track_order', 'Hỗ trợ & Kiểm tra đơn hàng')">Hỗ trợ & Kiểm tra đơn hàng</button>
                        <button type="button" class="chat-option-btn" onclick="handleOptionSelect('promotion', 'Báo giá & Khuyến mãi mới nhất')">Báo giá & Khuyến mãi mới nhất</button>
                        <button type="button" class="chat-option-btn" onclick="handleOptionSelect('connect_staff', '🎧 Kết nối trực tiếp với Nhân viên tư vấn')">🎧 Kết nối trực tiếp với Nhân viên tư vấn</button>
                    </div>
                </div>

                <!-- KHUNG CHÁT TRỰC TIẾP VỚI NHÂN VIÊN TƯ VẤN -->
                <div id="chatHistoryContainer"></div>
            </div>

            <div class="p-3 bg-white border-top">
                <form id="userSendChatForm" class="input-group">
                    <input type="text" id="userChatInput" class="form-control" placeholder="Nhập tin nhắn..." required>
                    <button class="btn btn-primary px-3" type="submit"><i class="bi bi-send-fill"></i></button>
                </form>
            </div>
        </div>
    @endif

    <div class="{{ $isAdminOrStaff ? 'admin-main-content' : ($isAuthPage ? 'container py-4 d-flex justify-content-center align-items-center' : 'container pb-5') }}">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const isUserLoggedIn = @json(auth()->check());
            const currentUserId = @json(auth()->id());

            const bestSellerName = @json($bestSellerName);
            const bestSellerPrice = @json($bestSellerPrice);
            const minPriceFormatted = @json($minPriceFormatted);
            const maxPriceFormatted = @json($maxPriceFormatted);
            const latestOrderInfo = @json($latestOrderInfo);

            const dateElem = document.getElementById('chatTodayDate');
            if (dateElem) {
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                dateElem.innerText = now.toLocaleDateString('vi-VN', options);
            }

            /* LOGIC CHAT PHÍA NHÂN VIÊN TƯ VẤN */
            const btnAdminToggle = document.getElementById('btnAdminChatToggle');
            const adminPanel = document.getElementById('adminChatPanel');
            const btnAdminClose = document.getElementById('btnAdminChatClose');
            const adminUsersList = document.getElementById('adminChatUsersList');
            const adminMessagesBox = document.getElementById('adminChatMessagesBox');
            const adminSendForm = document.getElementById('adminSendChatForm');
            const adminInput = document.getElementById('adminChatInput');
            const btnAdminSend = document.getElementById('btnAdminSend');
            const adminBadge = document.getElementById('adminChatBadge');
            
            let activeAdminUserId = null;
            let activeAdminUserName = '';

            function checkAdminUnread() {
                if (!adminBadge) return;
                const unreadUrl = "{{ Route::has('admin.chat.unread') ? route('admin.chat.unread') : url('/admin/chat/unread') }}";
                fetch(unreadUrl)
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.unread_count > 0) adminBadge.classList.remove('d-none');
                        else adminBadge.classList.add('d-none');
                    }).catch(() => {});
            }

            if (btnAdminToggle && adminPanel) {
                checkAdminUnread();
                setInterval(checkAdminUnread, 4000);

                btnAdminToggle.addEventListener('click', function () {
                    adminPanel.classList.toggle('active');
                    if (adminPanel.classList.contains('active')) loadAdminUsers();
                });

                if (btnAdminClose) {
                    btnAdminClose.addEventListener('click', function () { adminPanel.classList.remove('active'); });
                }
            }

            function loadAdminUsers() {
                if (!adminUsersList) return;
                const usersUrl = "{{ Route::has('admin.chat.users') ? route('admin.chat.users') : url('/admin/chat/users') }}";
                fetch(usersUrl)
                    .then(r => r.json())
                    .then(users => {
                        if (!users || users.length === 0) {
                            adminUsersList.innerHTML = '<div class="text-center text-muted small py-3">Chưa có tin nhắn mới.</div>';
                            return;
                        }
                        let html = '';
                        users.forEach(u => {
                            let isActive = (activeAdminUserId == u.id) ? 'active' : '';
                            let badgeHtml = (u.unread_count && u.unread_count > 0) ? `<span class="badge bg-danger ms-auto" style="font-size:0.7rem">${u.unread_count}</span>` : '';
                            html += `
                                <div class="chat-user-item p-2 rounded-3 mb-2 border shadow-sm d-flex align-items-center justify-content-between ${isActive}" onclick="openAdminUserChat(${u.id}, '${u.name}')">
                                    <div class="overflow-hidden me-1">
                                        <div class="fw-bold small text-truncate" style="max-width: 120px;">${u.name}</div>
                                        <div class="text-muted extra-small text-truncate" style="font-size:0.72rem; max-width: 120px;">${u.email ?? ''}</div>
                                    </div>
                                    ${badgeHtml}
                                </div>`;
                        });
                        
                        adminUsersList.innerHTML = html;
                    }).catch(() => {});
            }

            window.openAdminUserChat = function(userId, userName) {
                activeAdminUserId = userId;
                if (userName) activeAdminUserName = userName;
                if (adminInput) adminInput.disabled = false;
                if (btnAdminSend) btnAdminSend.disabled = false;
                checkAdminUnread();
                loadAdminUsers();

                fetch(`/admin/chat/messages/${userId}`)
                    .then(r => r.json())
                    .then(msgs => {
                        let html = '';
                        if (!msgs || msgs.length === 0) {
                            html = `<div class="text-center text-muted small py-4">Bắt đầu trò chuyện với <b>${activeAdminUserName}</b></div>`;
                        } else {
                            msgs.forEach(m => {
                                let isMe = (m.sender_id != userId);
                                let text = m.content || m.message || '';
                                html += `<div class="${isMe ? 'msg-bubble-sent' : 'msg-bubble-received'}"><div class="small">${text}</div></div>`;
                            });
                        }
                        adminMessagesBox.innerHTML = html;
                        adminMessagesBox.scrollTop = adminMessagesBox.scrollHeight;
                    }).catch(() => {});
            };

            if (adminSendForm) {
                adminSendForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    let msg = adminInput.value.trim();
                    if (!msg || !activeAdminUserId) return;

                    const sendAdminUrl = "{{ Route::has('admin.chat.send') ? route('admin.chat.send') : url('/admin/chat/send') }}";
                    fetch(sendAdminUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ user_id: activeAdminUserId, receiver_id: activeAdminUserId, message: msg })
                    }).then(() => {
                        adminInput.value = '';
                        openAdminUserChat(activeAdminUserId, activeAdminUserName);
                    });
                });
            }

            /* LOGIC CHAT PHÍA KHÁCH HÀNG */
            const btnUserToggle = document.getElementById('btnUserChatToggle');
            const userPanel = document.getElementById('userChatPanel');
            const btnUserClose = document.getElementById('btnUserChatClose');
            const userMessagesBox = document.getElementById('userChatMessagesBox');
            const botWelcomeContainer = document.getElementById('botWelcomeContainer');
            const chatHistoryContainer = document.getElementById('chatHistoryContainer');
            const btnContinueChat = document.getElementById('btnContinueChat');
            const btnExitStaffChat = document.getElementById('btnExitStaffChat');
            const userSendForm = document.getElementById('userSendChatForm');
            const userInput = document.getElementById('userChatInput');
            const chatStatusSubtitle = document.getElementById('chatStatusSubtitle');

            let isConnectedToStaff = false;
            let cachedHistory = [];

            function resetToBotMenu() {
                isConnectedToStaff = false;
                if (chatStatusSubtitle) {
                    chatStatusSubtitle.innerText = 'Hệ thống tư vấn tự động';
                    chatStatusSubtitle.className = 'text-white-50 extra-small';
                }
                if (btnExitStaffChat) {
                    btnExitStaffChat.classList.add('d-none');
                }
                if (userInput) {
                    userInput.placeholder = "Nhập tin nhắn...";
                }
                if (botWelcomeContainer) botWelcomeContainer.classList.remove('d-none');
                if (chatHistoryContainer) chatHistoryContainer.innerHTML = '';
            }

            function fetchUserChatHistory() {
                if (!isUserLoggedIn) return;
                const userMsgUrl = "{{ Route::has('user.chat.messages') ? route('user.chat.messages') : url('/user/chat/messages') }}";

                fetch(userMsgUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data) return;
                    const msgs = Array.isArray(data) ? data : (data.messages || []);
                    cachedHistory = msgs;

                    if (data.is_admin || data.is_staff) {
                        connectStaffChat(false);
                    } else if (msgs && msgs.length > 0) {
                        if (btnContinueChat) btnContinueChat.classList.remove('d-none');
                    } else {
                        if (btnContinueChat) btnContinueChat.classList.add('d-none');
                    }
                })
                .catch(() => {});
            }

            window.connectStaffChat = function(triggerServer = true) {
                if (!isUserLoggedIn) {
                    appendBubbleToBox(botWelcomeContainer, 'Vui lòng <a href="/login" class="fw-bold">Đăng nhập</a> để kết nối trực tiếp với Nhân viên tư vấn.', 'received', 'XFAN Bot');
                    return;
                }

                isConnectedToStaff = true;
                
                if (chatStatusSubtitle) {
                    chatStatusSubtitle.innerText = '🟢 Đã kết nối với Nhân viên tư vấn';
                    chatStatusSubtitle.className = 'text-warning extra-small fw-bold';
                }

                if (btnExitStaffChat) {
                    btnExitStaffChat.classList.remove('d-none');
                }

                if (userInput) {
                    userInput.placeholder = "Nhập tin nhắn cho Nhân viên tư vấn...";
                }

                if (botWelcomeContainer) botWelcomeContainer.classList.add('d-none');

                if (triggerServer) {
                    const userSendUrl = "{{ Route::has('user.chat.send') ? route('user.chat.send') : url('/user/chat/send') }}";
                    fetch(userSendUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ message: 'connect_staff' })
                    });
                }

                if (chatHistoryContainer) {
                    chatHistoryContainer.innerHTML = '';
                    if (cachedHistory.length === 0) {
                        appendBubbleToBox(chatHistoryContainer, 'Xin chào! Bạn cần hỗ trợ thông tin gì, hãy gửi tin nhắn bên dưới cho Nhân viên tư vấn nhé.', 'received', 'Nhân viên tư vấn');
                    } else {
                        cachedHistory.forEach(m => {
                            let text = m.content || m.message || '';
                            let isMe = (m.sender_id == currentUserId || m.type === 'user' || m.sender === 'user');
                            appendBubbleToBox(chatHistoryContainer, text, isMe ? 'sent' : 'received', isMe ? 'Bạn' : 'Nhân viên tư vấn');
                        });
                    }
                }
                userMessagesBox.scrollTop = userMessagesBox.scrollHeight;
            };

            window.exitStaffChat = function() {
                const userSendUrl = "{{ Route::has('user.chat.send') ? route('user.chat.send') : url('/user/chat/send') }}";

                fetch(userSendUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ message: 'exit' })
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    resetToBotMenu();
                    if (data && data.reply) {
                        appendBubbleToBox(botWelcomeContainer, data.reply, 'received', 'XFAN Bot');
                    }
                })
                .catch(() => {
                    resetToBotMenu();
                });
            };

            function appendBubbleToBox(targetBox, text, type = 'received', senderName = 'XFAN Bot') {
                if (!targetBox || !text) return;
                const div = document.createElement('div');
                div.className = type === 'sent' ? 'msg-bubble-sent' : 'msg-bubble-received';
                if (type === 'received') {
                    div.innerHTML = `<div class="fw-bold mb-1 text-primary" style="font-size:0.8rem">${senderName}</div><div>${text}</div>`;
                } else {
                    div.innerHTML = `<div>${text}</div>`;
                }
                targetBox.appendChild(div);
                userMessagesBox.scrollTop = userMessagesBox.scrollHeight;
            }

            if (btnUserToggle && userPanel) {
                btnUserToggle.addEventListener('click', function () {
                    userPanel.classList.toggle('active');
                    if (userPanel.classList.contains('active')) {
                        fetchUserChatHistory();
                    }
                });

                if (btnUserClose) {
                    btnUserClose.addEventListener('click', function () {
                        userPanel.classList.remove('active');
                    });
                }
            }

            window.handleOptionSelect = function(actionKey, labelText) {
                appendBubbleToBox(botWelcomeContainer, labelText, 'sent');

                setTimeout(() => {
                    if (actionKey === 'buy_product') {
                        appendBubbleToBox(botWelcomeContainer, `🔥 <b>Sản phẩm bán chạy tại XFAN Store:</b><br>• <b>${bestSellerName}</b><br>• Giá bán: <span class="text-danger fw-bold">${bestSellerPrice}</span><br><br>👉 Bạn có thể truy cập mục <b>Cửa Hàng</b> trên thanh menu để xem chi tiết và đặt mua!`, 'received', 'XFAN Bot');
                    
                    } else if (actionKey === 'track_order') {
                        if (!isUserLoggedIn) {
                            appendBubbleToBox(botWelcomeContainer, 'Vui lòng <a href="/login" class="fw-bold">Đăng nhập</a> để tra cứu thông tin đơn hàng gần nhất của bạn.', 'received', 'XFAN Bot');
                        } else if (latestOrderInfo) {
                            appendBubbleToBox(botWelcomeContainer, `📦 <b>Thông tin đơn hàng gần nhất của bạn:</b><br>• Mã đơn: <b>#${latestOrderInfo.id}</b><br>• Thời gian đặt: <b>${latestOrderInfo.created_at}</b><br>• Trạng thái: <span class="badge bg-info text-dark">${latestOrderInfo.status}</span><br>• Tổng tiền: <b>${latestOrderInfo.total}</b><br><br><i>(Để xem danh sách toàn bộ đơn hàng, vui lòng vào "Lịch sử đơn hàng" trên thanh menu)</i>`, 'received', 'XFAN Bot');
                        } else {
                            appendBubbleToBox(botWelcomeContainer, 'Bạn chưa có đơn hàng nào tại XFAN Store.', 'received', 'XFAN Bot');
                        }

                    } else if (actionKey === 'promotion') {
                        appendBubbleToBox(botWelcomeContainer, `💰 <b>Mức giá sản phẩm tại XFAN Store:</b><br>Các mẫu máy hút mùi tại cửa hàng hiện có mức giá dao động từ <b>${minPriceFormatted}</b> đến <b>${maxPriceFormatted}</b> tùy thuộc vào kiểu dáng và công suất.`, 'received', 'XFAN Bot');
                    
                    } else if (actionKey === 'connect_staff') {
                        connectStaffChat(true);
                    }
                }, 300);
            };

            if (userSendForm) {
                userSendForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    let msg = userInput.value.trim();
                    if (!msg) return;

                    userInput.value = '';

                    if (isConnectedToStaff) {
                        appendBubbleToBox(chatHistoryContainer, msg, 'sent');

                        const userSendUrl = "{{ Route::has('user.chat.send') ? route('user.chat.send') : url('/user/chat/send') }}";
                        fetch(userSendUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ message: msg })
                        })
                        .then(r => r.ok ? r.json() : null)
                        .then(data => {
                            if (!data) return;
                            if (data.mode === 'bot') {
                                resetToBotMenu();
                                if (data.reply) {
                                    appendBubbleToBox(botWelcomeContainer, data.reply, 'received', 'XFAN Bot');
                                }
                            }
                        });
                    } else {
                        appendBubbleToBox(botWelcomeContainer, msg, 'sent');
                        connectStaffChat(true);
                    }
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>