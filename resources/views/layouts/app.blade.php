<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'XFAN Store'))</title>

    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --sidebar-width: 260px;
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

        /* ADMIN SIDEBAR */
        body.admin-body { padding-left: var(--sidebar-width) !important; }
        .admin-sidebar {
            width: var(--sidebar-width) !important;
            height: 100vh;
            position: fixed !important;
            top: 0; left: 0;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-color);
            z-index: 1040;
            display: flex; flex-direction: column; justify-content: space-between;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
        }
        .sidebar-brand {
            padding: 1.25rem 1.5rem; font-size: 1.25rem; font-weight: 700; color: #ffffff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center;
        }
        .sidebar-user { padding: 1.25rem 1.5rem; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-user .avatar {
            width: 48px; height: 48px; background-color: #374151; color: #fff;
            border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem; margin: 0 auto 8px auto;
        }
        .sidebar-menu { list-style: none; padding: 1rem 0; margin: 0; flex-grow: 1; overflow-y: auto; }
        .sidebar-menu .nav-link {
            padding: 0.75rem 1.5rem; color: var(--sidebar-color);
            display: flex; align-items: center; gap: 12px; font-size: 0.95rem; text-decoration: none;
            transition: all 0.2s ease;
        }
        .sidebar-menu .nav-link:hover, .sidebar-menu .nav-link.active {
            color: var(--sidebar-active-color); background-color: var(--sidebar-active-bg); border-left: 4px solid #3b82f6;
        }
        .sidebar-footer { padding: 1rem 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        .admin-main-content { width: 100% !important; min-height: 100vh; padding: 1.5rem 2rem; }

        /* USER NAVBAR */
        .user-navbar { background-color: #ffffff; border-bottom: 1px solid #e5e7eb; }
        .user-nav-link {
            color: #4b5563; text-decoration: none; font-weight: 500; padding: 0.5rem 0.75rem;
            border-radius: 8px; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px;
        }
        .user-nav-link:hover { color: #2563eb; background-color: #f1f5f9; }
        .user-nav-link.active { color: #2563eb; font-weight: 600; background-color: #eff6ff; }

        /* POPUP CHAT UI */
        .floating-chat-btn {
            position: fixed !important; bottom: 24px !important; right: 24px !important;
            z-index: 99999 !important; cursor: pointer;
        }

        .custom-chat-panel {
            position: fixed !important; bottom: 85px !important; right: 24px !important;
            width: 380px !important; max-width: 92vw !important; height: 560px !important;
            background: #f8fafc !important; border-radius: 16px !important;
            z-index: 100000 !important; box-shadow: 0 12px 35px rgba(0,0,0,0.18) !important;
            display: none; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0;
        }
        .custom-chat-panel.active { display: flex !important; }

        .chat-header-dark {
            background-color: #0f172a; color: white; padding: 14px 18px;
            display: flex; justify-content: space-between; align-items: center;
        }

        .chat-option-btn {
            background: #ffffff; color: #334155; border: 1px solid #e2e8f0;
            border-radius: 20px; padding: 10px 16px; font-size: 0.88rem; font-weight: 500;
            text-align: center; margin-bottom: 8px; width: 100%; transition: all 0.2s ease;
            cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .chat-option-btn:hover {
            background-color: #f1f5f9; border-color: #cbd5e1; color: #0284c7; transform: translateY(-1px);
        }

        .chat-date-badge {
            text-align: center; font-size: 0.75rem; color: #64748b; margin: 10px 0; font-weight: 500;
        }

        .msg-bubble-sent {
            background-color: #2563eb; color: white; border-radius: 14px 14px 0px 14px;
            max-width: 82%; margin-left: auto; padding: 10px 14px; margin-bottom: 10px; font-size: 0.9rem; word-break: break-word;
        }
        .msg-bubble-received {
            background-color: #ffffff; color: #1e293b; border: 1px solid #e2e8f0;
            border-radius: 14px 14px 14px 0px; max-width: 85%; padding: 10px 14px; margin-bottom: 10px; font-size: 0.9rem; word-break: break-word;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .chat-user-item { cursor: pointer; transition: background 0.2s; }
        .chat-user-item:hover, .chat-user-item.active { background-color: #e2e8f0 !important; }
    </style>
</head>

@php
    // Cho phép hiển thị Sidebar nếu tài khoản là Admin hoặc Staff
    $isAdminOrStaff = auth()->check() && (
        (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()) ||
        (method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff()) ||
        in_array(auth()->user()->role ?? '', ['admin', 'staff'])
    );
    
    $isAuthPage = request()->is('login') || request()->is('register');
    $isAdminManagementPage = request()->is('admin*') || request()->routeIs('admin.*');

    // DỮ LIỆU CỬA HÀNG CHO BOT
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

    {{-- SIDEBAR ADMIN / NHÂN VIÊN --}}
    @if ($isAdminOrStaff)
        <aside class="admin-sidebar">
            <div>
                <div class="sidebar-brand">
                    <i class="fas fa-fan text-primary fs-4 me-2"></i>
                    <span class="fw-bold">XFAN STORE</span>
                </div>
                
                {{-- KHU VỰC AVATAR, TÊN VÀ VAI TRÒ --}}
                <div class="sidebar-user">
                    <div class="avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</div>
                    <div class="fw-bold text-white small">{{ Auth::user()->name ?? 'Tài khoản' }}</div>
                    
                    <div class="text-info extra-small mt-1" style="font-size: 0.8rem; font-weight: 600;">
                        <i class="bi bi-person-badge me-1"></i>
                        @if((method_exists(Auth::user(), 'isStaff') && Auth::user()->isStaff()) || Auth::user()->role === 'staff')
                            Nhân viên
                        @elseif((method_exists(Auth::user(), 'isAdmin') && Auth::user()->isAdmin()) || Auth::user()->role === 'admin')
                            Quản trị viên
                        @else
                            {{ ucfirst(Auth::user()->role ?? 'Admin') }}
                        @endif
                    </div>
                </div>

                <ul class="sidebar-menu">
                    <li class="nav-item">
                        <a href="{{ Route::has('admin.portal') ? route('admin.portal') : url('/admin') }}" class="nav-link {{ request()->routeIs('admin.portal') ? 'active' : '' }}">
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

                    {{-- MỤC QUẢN LÝ GÓI DỊCH VỤ ADMIN --}}
                    <li class="nav-item">
                        <a href="{{ Route::has('admin.services.index') ? route('admin.services.index') : (Route::has('services.index') ? route('services.index') : url('/dich-vu')) }}" class="nav-link {{ request()->routeIs('*services*') ? 'active' : '' }}">
                            <i class="bi bi-wrench-adjustable-circle"></i><span>Gói dịch vụ</span>
                        </a>
                    </li>

                    {{-- CHỈ HIỂN THỊ MỤC NGƯỜI DÙNG DÀNH CHO ADMIN --}}
                    @if((method_exists(Auth::user(), 'isAdmin') && Auth::user()->isAdmin()) || Auth::user()->role === 'admin')
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
            </div>
            <div class="sidebar-footer">
                <form action="{{ Route::has('logout') ? route('logout') : url('/logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-light w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-box-arrow-right"></i><span>Đăng xuất</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- NÚT & KHUNG CHAT QUẢN TRỊ -->
        <button type="button" id="btnAdminChatToggle" class="btn btn-dark rounded-pill shadow-lg px-3 py-2 floating-chat-btn position-relative">
            <i class="bi bi-chat-dots-fill text-warning me-1"></i>
            <span class="fw-bold">Chat Khách hàng</span>
            <span id="adminChatBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger p-2 border border-light d-none"></span>
        </button>

        <div id="adminChatPanel" class="custom-chat-panel">
            <div class="chat-header-dark">
                <h6 class="mb-0 fw-bold"><i class="bi bi-chat-dots-fill text-warning me-2"></i> Chat Khách Hàng</h6>
                <button type="button" id="btnAdminChatClose" class="btn-close btn-close-white"></button>
            </div>
            <div class="p-2 border-bottom bg-light overflow-auto" id="adminChatUsersList" style="max-height: 140px;">
                <div class="text-center text-muted small py-2">Đang tải danh sách...</div>
            </div>
            <div class="flex-grow-1 p-3 overflow-auto" id="adminChatMessagesBox">
                <div class="text-center text-muted small py-4">Chọn một khách hàng để xem tin nhắn.</div>
            </div>
            <div class="p-3 bg-white border-top">
                <form id="adminSendChatForm" class="input-group">
                    <input type="text" id="adminChatInput" class="form-control" placeholder="Nhập tin nhắn..." disabled required>
                    <button class="btn btn-primary" type="submit" id="btnAdminSend" disabled><i class="bi bi-send-fill"></i></button>
                </form>
            </div>
        </div>
    @endif

    {{-- NAVBAR TOPBAR CỬA HÀNG --}}
    @if (!$isAdminManagementPage)
        <nav class="navbar navbar-expand-lg navbar-light user-navbar py-2 shadow-sm mb-4">
            <div class="{{ $isAdminOrStaff ? 'container-fluid px-4' : 'container' }} d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <a class="navbar-brand fw-bold text-primary fs-4 m-0 d-flex align-items-center" href="{{ Route::has('storefront') ? route('storefront') : url('/') }}">
                        <i class="fas fa-fan me-2"></i>XFAN STORE
                    </a>
                    <div class="d-flex align-items-center gap-1 ms-2 border-start ps-3" style="border-color: #e5e7eb !important;">
                        <a href="{{ Route::has('storefront') ? route('storefront') : url('/') }}" class="user-nav-link {{ request()->routeIs('storefront*') || request()->is('/') ? 'active' : '' }}">
                            <i class="bi bi-shop"></i><span>Cửa hàng</span>
                        </a>
                        {{-- NÚT DỊCH VỤ VỆ SINH & LẮP ĐẶT --}}
                        <a href="{{ Route::has('services.index') ? route('services.index') : url('/dich-vu') }}" class="user-nav-link {{ request()->routeIs('*services*') ? 'active' : '' }}">
                            <i class="bi bi-tools"></i><span>Dịch vụ & Lắp đặt</span>
                        </a>
                        <a href="{{ auth()->check() ? (Route::has('cart.index') ? route('cart.index') : url('/cart')) : (Route::has('login') ? route('login') : url('/login')) }}" class="user-nav-link {{ request()->routeIs('*cart*') ? 'active' : '' }}">
                            <i class="bi bi-cart3"></i><span>Giỏ hàng</span>
                        </a>
                        <a href="{{ auth()->check() ? (Route::has('orders.index') ? route('orders.index') : url('/orders')) : (Route::has('login') ? route('login') : url('/login')) }}" class="user-nav-link {{ request()->routeIs('*order*') ? 'active' : '' }}">
                            <i class="bi bi-clock-history"></i><span>Lịch sử đơn hàng</span>
                        </a>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @auth
                        @if (!$isAdminOrStaff)
                            <span class="text-secondary small me-2">Xin chào, <strong>{{ Auth::user()->name }}</strong></span>
                            <form action="{{ Route::has('logout') ? route('logout') : url('/logout') }}" method="POST" class="d-inline m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i> Đăng xuất</button>
                            </form>
                        @endif
                    @else
                        <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="btn btn-sm {{ request()->is('login') ? 'btn-primary' : 'btn-outline-primary' }} px-3">Đăng nhập</a>
                        <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="btn btn-sm {{ request()->is('register') ? 'btn-primary' : 'btn-outline-primary' }} px-3">Đăng ký</a>
                    @endauth
                </div>
            </div>
        </nav>
    @endif

    {{-- KHUNG CHAT KHÁCH HÀNG --}}
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
                <button type="button" id="btnUserChatClose" class="btn-close btn-close-white"></button>
            </div>

            <div class="flex-grow-1 p-3 overflow-auto" id="userChatMessagesBox">
                <div class="chat-date-badge" id="chatTodayDate"></div>

                <div class="msg-bubble-received">
                    <div class="fw-bold mb-1 text-primary">XFAN Bot</div>
                    <div>Xin chào, tôi có thể giúp gì cho bạn? Vui lòng chọn một trong các yêu cầu bên dưới:</div>
                </div>

                <div id="quickOptionsGroup" class="mt-2">
                    <button type="button" class="chat-option-btn" onclick="handleOptionSelect('buy_product', 'Mua sản phẩm & Máy hút mùi')">Mua sản phẩm & Máy hút mùi</button>
                    <button type="button" class="chat-option-btn" onclick="handleOptionSelect('track_order', 'Hỗ trợ & Kiểm tra đơn hàng')">Hỗ trợ & Kiểm tra đơn hàng</button>
                    <button type="button" class="chat-option-btn" onclick="handleOptionSelect('promotion', 'Báo giá & Khuyến mãi mới nhất')">Báo giá & Khuyến mãi mới nhất</button>
                    <button type="button" class="chat-option-btn" onclick="handleOptionSelect('connect_admin', '🎧 Kết nối trực tiếp với Tư vấn viên')">🎧 Kết nối trực tiếp với Tư vấn viên</button>
                </div>
            </div>

            <div class="p-3 bg-white border-top">
                <form id="userSendChatForm" class="input-group">
                    <input type="text" id="userChatInput" class="form-control" placeholder="Gửi tin nhắn..." required>
                    <button class="btn btn-primary px-3" type="submit"><i class="bi bi-send-fill"></i></button>
                </form>
            </div>
        </div>
    @endif

    <!-- KHUNG NỘI DUNG CHÍNH -->
    <div class="{{ $isAdminOrStaff ? 'admin-main-content' : ($isAuthPage ? 'container py-4 d-flex justify-content-center align-items-center' : 'container pb-5') }}">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JAVASCRIPT CHATBOT & ADMIN CHAT -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const isUserLoggedIn = @json(auth()->check());
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

            // --- ADMIN CHAT LOGIC ---
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
                fetch("{{ Route::has('admin.chat.unread') ? route('admin.chat.unread') : '#' }}")
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.unread_count > 0) adminBadge.classList.remove('d-none');
                        else adminBadge.classList.add('d-none');
                    }).catch(() => {});
            }

            if (btnAdminToggle && adminPanel) {
                checkAdminUnread();
                setInterval(checkAdminUnread, 5000);
                btnAdminToggle.addEventListener('click', function () {
                    adminPanel.classList.toggle('active');
                    if (adminPanel.classList.contains('active')) loadAdminUsers();
                });
                if (btnAdminClose) {
                    btnAdminClose.addEventListener('click', function () { adminPanel.classList.remove('active'); });
                }
            }

            function loadAdminUsers() {
                fetch("{{ Route::has('admin.chat.users') ? route('admin.chat.users') : '#' }}")
                    .then(r => r.json())
                    .then(users => {
                        if (!users || users.length === 0) {
                            adminUsersList.innerHTML = '<div class="text-center text-muted small py-2">Chưa có khách nhắn.</div>';
                            return;
                        }
                        let html = '';
                        users.forEach(u => {
                            let isActive = (activeAdminUserId == u.id) ? 'active' : '';
                            let badgeHtml = (u.unread_count && u.unread_count > 0) ? `<span class="badge bg-danger ms-1">${u.unread_count} mới</span>` : '';
                            html += `
                                <div class="chat-user-item p-2 rounded mb-1 border d-flex justify-content-between align-items-center ${isActive}" onclick="openAdminUserChat(${u.id}, '${u.name}')">
                                    <div>
                                        <div class="fw-bold small text-dark">${u.name} ${badgeHtml}</div>
                                        <div class="text-muted extra-small" style="font-size:0.75rem">${u.email ?? ''}</div>
                                    </div>
                                    <span class="badge bg-primary">Chat</span>
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
                loadAdminUsers();
                checkAdminUnread();

                fetch(`/admin/chat/messages/${userId}`)
                    .then(r => r.json())
                    .then(msgs => {
                        let html = '';
                        if (!msgs || msgs.length === 0) {
                            html = `<div class="text-center text-muted small py-3">Bắt đầu trò chuyện với <b>${activeAdminUserName}</b></div>`;
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

                    fetch("{{ Route::has('admin.chat.send') ? route('admin.chat.send') : '#' }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ user_id: activeAdminUserId, receiver_id: activeAdminUserId, message: msg })
                    }).then(() => {
                        adminInput.value = '';
                        openAdminUserChat(activeAdminUserId, activeAdminUserName);
                    });
                });
            }

            // --- USER CHAT LOGIC ---
            const btnUserToggle = document.getElementById('btnUserChatToggle');
            const userPanel = document.getElementById('userChatPanel');
            const btnUserClose = document.getElementById('btnUserChatClose');
            const userMessagesBox = document.getElementById('userChatMessagesBox');
            const userSendForm = document.getElementById('userSendChatForm');
            const userInput = document.getElementById('userChatInput');
            const chatStatusSubtitle = document.getElementById('chatStatusSubtitle');

            let unhandledAttempts = 0;
            let isConnectedToAdmin = false;

            if (btnUserToggle && userPanel) {
                btnUserToggle.addEventListener('click', function () {
                    userPanel.classList.toggle('active');
                });
                if (btnUserClose) {
                    btnUserClose.addEventListener('click', function () {
                        userPanel.classList.remove('active');
                    });
                }
            }

            function appendBubble(text, type = 'received', senderName = 'XFAN Bot') {
                if (!userMessagesBox) return;
                const div = document.createElement('div');
                div.className = type === 'sent' ? 'msg-bubble-sent' : 'msg-bubble-received';
                if (type === 'received') {
                    div.innerHTML = `<div class="fw-bold mb-1 text-primary" style="font-size:0.8rem">${senderName}</div><div>${text}</div>`;
                } else {
                    div.innerHTML = `<div>${text}</div>`;
                }
                userMessagesBox.appendChild(div);
                userMessagesBox.scrollTop = userMessagesBox.scrollHeight;
            }

            function connectDirectToAdmin() {
                isConnectedToAdmin = true;
                if (chatStatusSubtitle) {
                    chatStatusSubtitle.innerText = '🟢 Đã kết nối với Tư vấn viên Admin';
                    chatStatusSubtitle.classList.replace('text-white-50', 'text-warning');
                }
                appendBubble('🟢 <b>Hệ thống đã kết nối bạn với Nhân viên Tư vấn!</b> Vui lòng để lại tin nhắn, Admin sẽ phản hồi bạn ngay lập tức.', 'received', 'Hệ thống');
            }

            window.handleOptionSelect = function(actionKey, labelText) {
                appendBubble(labelText, 'sent');

                setTimeout(() => {
                    if (actionKey === 'buy_product') {
                        appendBubble(`🔥 <b>Sản phẩm được mua nhiều nhất tại XFAN Store:</b><br>• <b>${bestSellerName}</b><br>• Giá bán: <span class="text-danger fw-bold">${bestSellerPrice}</span><br><br>👉 Bạn có thể truy cập mục <b>Cửa Hàng</b> trên thanh menu để xem chi tiết thông số và đặt mua nhé!`, 'received');
                    
                    } else if (actionKey === 'track_order') {
                        if (!isUserLoggedIn) {
                            appendBubble('Vui lòng <a href="/login" class="fw-bold">Đăng nhập</a> để tra cứu thông tin đơn hàng gần nhất của bạn.', 'received');
                        } else if (latestOrderInfo) {
                            appendBubble(`📦 <b>Thông tin đơn hàng gần nhất của bạn:</b><br>• Mã đơn: <b>#${latestOrderInfo.id}</b><br>• Thời gian đặt: <b>${latestOrderInfo.created_at}</b><br>• Trạng thái: <span class="badge bg-info text-dark">${latestOrderInfo.status}</span><br>• Tổng tiền: <b>${latestOrderInfo.total}</b><br><br><i>(Để xem danh sách toàn bộ lịch sử, vui lòng vào mục "Lịch sử đơn hàng" trên thanh menu)</i>`, 'received');
                        } else {
                            appendBubble('Bạn chưa có đơn hàng nào tại XFAN Store.', 'received');
                        }

                    } else if (actionKey === 'promotion') {
                        appendBubble(`💰 <b>Khoảng giá sản phẩm tại XFAN Store:</b><br>Các mẫu máy hút mùi tại cửa hàng hiện có mức giá dao động từ <b>${minPriceFormatted}</b> đến <b>${maxPriceFormatted}</b> tùy thuộc vào kiểu dáng và công suất.<br><br>📢 <b>Khuyến mại:</b> Cửa hàng hiện tại không có chương trình khuyến mại.`, 'received');
                    
                    } else if (actionKey === 'connect_admin') {
                        connectDirectToAdmin();
                    }
                }, 400);
            };

            if (userSendForm) {
                userSendForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    let msg = userInput.value.trim();
                    if (!msg) return;

                    appendBubble(msg, 'sent');
                    userInput.value = '';

                    if (isConnectedToAdmin) {
                        fetch("{{ Route::has('user.chat.send') ? route('user.chat.send') : '#' }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ message: msg })
                        });
                        return;
                    }

                    unhandledAttempts++;

                    if (unhandledAttempts >= 3) {
                        setTimeout(() => {
                            appendBubble('Tôi nhận thấy câu hỏi của bạn cần sự hỗ trợ chuyên sâu hơn từ kỹ thuật viên.', 'received');
                            connectDirectToAdmin();
                            
                            fetch("{{ Route::has('user.chat.send') ? route('user.chat.send') : '#' }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                                body: JSON.stringify({ message: msg })
                            });
                        }, 600);
                        return;
                    }

                    setTimeout(() => {
                        appendBubble(`Cảm ơn bạn. Bot chưa nhận diện rõ câu hỏi "${msg}". Vui lòng chọn menu bên trên hoặc nhập lại chi tiết hơn (Lần ${unhandledAttempts}/3 trước khi chuyển Admin).`, 'received');
                    }, 500);
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>