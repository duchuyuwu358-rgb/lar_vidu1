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

        /* 1. ĐỊNH DẠNG ADMIN SIDEBAR */
        body.admin-body {
            padding-left: var(--sidebar-width) !important;
        }
        .admin-sidebar {
            width: var(--sidebar-width) !important;
            height: 100vh;
            position: fixed !important;
            top: 0; left: 0;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-color);
            z-index: 1040;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
        }
        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            font-size: 1.25rem; font-weight: 700; color: #ffffff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-user {
            padding: 1.25rem 1.5rem; text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-user .avatar {
            width: 48px; height: 48px;
            background-color: #374151; color: #fff;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem; margin: 0 auto 8px auto;
        }
        .sidebar-menu { list-style: none; padding: 1rem 0; margin: 0; flex-grow: 1; overflow-y: auto; }
        .sidebar-menu .nav-item { margin-bottom: 2px; }
        .sidebar-menu .nav-link {
            padding: 0.75rem 1.5rem; color: var(--sidebar-color);
            display: flex; align-items: center; gap: 12px; font-size: 0.95rem; text-decoration: none;
            transition: all 0.2s ease;
        }
        .sidebar-menu .nav-link:hover, .sidebar-menu .nav-link.active {
            color: var(--sidebar-active-color); background-color: var(--sidebar-active-bg);
            border-left: 4px solid #3b82f6;
        }
        .sidebar-footer { padding: 1rem 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        .admin-main-content { width: 100% !important; min-height: 100vh; padding: 1.5rem 2rem; }

        /* 2. ĐỊNH DẠNG TRANG KHÁCH HÀNG (USER NAVBAR) */
        .user-navbar { background-color: #ffffff; border-bottom: 1px solid #e5e7eb; }
        .user-nav-link {
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .user-nav-link:hover {
            color: #2563eb;
            background-color: #f1f5f9;
        }
        .user-nav-link.active {
            color: #2563eb;
            font-weight: 600;
            background-color: #eff6ff;
        }

        /* 3. NÚT CHAT NỔI VÀ KHUNG CHAT POPUP (GỌN GÀNG GÓC DƯỚI PHẢI) */
        .floating-chat-btn {
            position: fixed !important;
            bottom: 24px !important;
            right: 24px !important;
            z-index: 99999 !important;
            cursor: pointer;
        }

        .custom-chat-panel {
            position: fixed !important;
            bottom: 85px !important;
            right: 24px !important;
            width: 360px !important;
            max-width: 90vw !important;
            height: 520px !important;
            background: #ffffff !important;
            border-radius: 14px !important;
            z-index: 100000 !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .custom-chat-panel.active {
            display: flex !important;
        }

        .chat-user-item { cursor: pointer; transition: background 0.2s; }
        .chat-user-item:hover, .chat-user-item.active { background-color: #e2e8f0 !important; }

        .msg-bubble-sent {
            background-color: #3b82f6; color: white;
            border-radius: 12px 12px 0px 12px;
            max-width: 80%; margin-left: auto; padding: 8px 12px; margin-bottom: 8px;
            word-break: break-word;
        }
        .msg-bubble-received {
            background-color: #f1f5f9; color: #1f2937;
            border: 1px solid #e2e8f0;
            border-radius: 12px 12px 12px 0px;
            max-width: 80%; padding: 8px 12px; margin-bottom: 8px;
            word-break: break-word;
        }
    </style>
</head>
@php
    $isAdmin = auth()->check() && auth()->user()->isAdmin();$isAuthPage = request()->is('login') || request()->is('register');
@endphp

<body class="{{ $isAdmin ? 'admin-body' : ($isAuthPage ? 'd-flex align-items-center justify-content-center min-vh-100' : 'user-body') }}">

    {{-- TRƯỜNG HỢP 1: TÀI KHOẢN LÀ ADMIN --}}
    @if ($isAdmin)
        <!-- SIDEBAR ADMIN -->
        <aside class="admin-sidebar">
            <div>
                <div class="sidebar-brand">
                    <i class="bi bi-fan text-primary fs-3"></i>
                    <span>SHOP ADMIN</span>
                </div>
                <div class="sidebar-user">
                    <div class="avatar">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}</div>
                    <div class="fw-bold text-white small">{{ Auth::user()->name ?? 'Admin' }}</div>
                </div>
                <ul class="sidebar-menu">
                    <li class="nav-item">
                        <a href="{{ route('admin.portal') }}" class="nav-link {{ request()->routeIs('admin.portal') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('storefront') }}" class="nav-link {{ request()->routeIs('storefront*') ? 'active' : '' }}">
                            <i class="bi bi-shop"></i><span>Xem Cửa Hàng</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('hoods.index') }}" class="nav-link {{ request()->routeIs('hoods.*') ? 'active' : '' }}">
                            <i class="bi bi-box-seam"></i><span>Sản phẩm</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                            <i class="bi bi-tags"></i><span>Danh mục</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i><span>Người dùng</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                            <i class="bi bi-receipt"></i><span>Đơn hàng</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-light w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-box-arrow-right"></i><span>Đăng xuất</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- NÚT CHAT DÀNH CHO ADMIN (CÓ CHẤM ĐỎ BÁO TIN MỚI) -->
        <button type="button" id="btnAdminChatToggle" class="btn btn-dark rounded-pill shadow-lg px-3 py-2 floating-chat-btn position-relative">
            <i class="bi bi-chat-dots-fill text-warning me-1"></i>
            <span class="fw-bold">Chat Khách hàng</span>
            <span id="adminChatBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger p-2 border border-light d-none">
                <span class="visually-hidden">Tin nhắn mới</span>
            </span>
        </button>

        <!-- KHUNG CHAT POPUP ADMIN -->
        <div id="adminChatPanel" class="custom-chat-panel">
            <div class="p-3 bg-dark text-white d-flex justify-content-between align-items-center">
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

    {{-- TRƯỜNG HỢP 2: TÀI KHOẢN KHÁCH HÀNG HOẶC KHÁCH VÔ DANH --}}
    @elseif (!$isAuthPage)
        <nav class="navbar navbar-expand-lg navbar-light user-navbar py-2 shadow-sm mb-4">
            <div class="container d-flex align-items-center justify-content-between">
                
                <!-- BÊN TRÁI: LOGO XFAN STORE + MENU ĐIỀU HƯỚNG CÂN ĐỐI -->
                <div class="d-flex align-items-center gap-3">
                    <a class="navbar-brand fw-bold text-primary fs-4 m-0 d-flex align-items-center" href="{{ route('storefront') }}">
                        <i class="fas fa-fan me-2"></i>XFAN STORE
                    </a>

                    <!-- DẢI MENU BÊN CẠNH LOGO -->
                    <div class="d-flex align-items-center gap-1 ms-2 border-start ps-3" style="border-color: #e5e7eb !important;">
                        <a href="{{ route('storefront') }}" class="user-nav-link {{ request()->routeIs('storefront*') ? 'active' : '' }}">
                            <i class="bi bi-shop"></i>
                            <span>Cửa hàng</span>
                        </a>

                        <a href="{{ Route::has('cart.index') ? route('cart.index') : (Route::has('cart') ? route('cart') : '#') }}" 
                           class="user-nav-link {{ request()->routeIs('*cart*') ? 'active' : '' }}">
                            <i class="bi bi-cart3"></i>
                            <span>Giỏ hàng</span>
                        </a>

                        <a href="{{ Route::has('orders.index') ? route('orders.index') : (Route::has('orders.history') ? route('orders.history') : (Route::has('user.orders') ? route('user.orders') : '#')) }}" 
                           class="user-nav-link {{ request()->routeIs('*order*') ? 'active' : '' }}">
                            <i class="bi bi-clock-history"></i>
                            <span>Lịch sử đơn hàng</span>
                        </a>
                    </div>
                </div>

                <!-- BÊN PHẢI: XIN CHÀO USER + NÚT ĐĂNG XUẤT -->
                <div class="d-flex align-items-center gap-3">
                    @auth
                        <span class="text-secondary small">Xin chào, <strong>{{ Auth::user()->name }}</strong></span>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i> Đăng xuất</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-primary px-3">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="btn btn-sm btn-outline-primary px-3">Đăng ký</a>
                    @endauth
                </div>

            </div>
        </nav>

        <!-- NÚT CHAT DÀNH CHO KHÁCH HÀNG (CÓ CHẤM ĐỎ) -->
        <button type="button" id="btnUserChatToggle" class="btn btn-primary rounded-pill shadow-lg px-3 py-2 floating-chat-btn position-relative">
            <i class="bi bi-chat-dots-fill me-1"></i>
            <span class="fw-bold">Chat với Shop</span>
            <span id="userChatBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger p-2 border border-light d-none">
                <span class="visually-hidden">Tin nhắn mới</span>
            </span>
        </button>

        <!-- KHUNG CHAT POPUP USER -->
        <div id="userChatPanel" class="custom-chat-panel">
            <div class="p-3 bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-chat-dots-fill me-2"></i> Hỗ Trợ Trực Tuyến</h6>
                <button type="button" id="btnUserChatClose" class="btn-close btn-close-white"></button>
            </div>
            <div class="flex-grow-1 p-3 overflow-auto" id="userChatMessagesBox">
                <div class="text-center text-muted small py-4">Đang tải tin nhắn...</div>
            </div>
            <div class="p-3 bg-white border-top">
                <form id="userSendChatForm" class="input-group">
                    <input type="text" id="userChatInput" class="form-control" placeholder="Hỏi câu hỏi..." required>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-send-fill"></i></button>
                </form>
            </div>
        </div>
    @endif

    <!-- KHUNG NỘI DUNG CHÍNH -->
    <div class="{{ $isAdmin ? 'admin-main-content' : ($isAuthPage ? 'w-100' : 'container pb-5') }}">
        @yield('content')
    </div>

    <!-- JAVASCRIPT XỬ LÝ CHAT -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            function getMessageText(m) {
                if (!m) return '';
                if (typeof m.content !== 'undefined' && m.content !== null) return m.content;
                if (typeof m.message !== 'undefined' && m.message !== null) return m.message;
                return '';
            }

            // --- 1. XỬ LÝ CHAT ADMIN ---
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
                fetch("{{ route('admin.chat.unread') }}")
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.unread_count > 0) {
                            adminBadge.classList.remove('d-none');
                        } else {
                            adminBadge.classList.add('d-none');
                        }
                    }).catch(() => {});
            }

            if (btnAdminToggle && adminPanel) {
                checkAdminUnread();
                setInterval(checkAdminUnread, 5000);

                btnAdminToggle.addEventListener('click', function () {
                    adminPanel.classList.toggle('active');
                    if (adminPanel.classList.contains('active')) {
                        loadAdminUsers();
                    }
                });
                btnAdminClose.addEventListener('click', function () {
                    adminPanel.classList.remove('active');
                });
            }

            function loadAdminUsers() {
                fetch("{{ route('admin.chat.users') }}")
                    .then(r => r.json())
                    .then(users => {
                        if (!users || users.length === 0) {
                            adminUsersList.innerHTML = '<div class="text-center text-muted small py-2">Chưa có khách nhắn.</div>';
                            return;
                        }
                        let html = '';
                        users.forEach(u => {
                            let isActive = (activeAdminUserId == u.id) ? 'active' : '';
                            let badgeHtml = (u.unread_count && u.unread_count > 0) 
                                ? `<span class="badge bg-danger ms-1">${u.unread_count} mới</span>` 
                                : '';
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

                adminInput.disabled = false;
                btnAdminSend.disabled = false;
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
                                let text = getMessageText(m);
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

                    fetch("{{ route('admin.chat.send') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ user_id: activeAdminUserId, receiver_id: activeAdminUserId, message: msg })
                    }).then(() => {
                        adminInput.value = '';
                        openAdminUserChat(activeAdminUserId, activeAdminUserName);
                    });
                });
            }

            // --- 2. XỬ LÝ CHAT USER ---
            const btnUserToggle = document.getElementById('btnUserChatToggle');
            const userPanel = document.getElementById('userChatPanel');
            const btnUserClose = document.getElementById('btnUserChatClose');
            const userMessagesBox = document.getElementById('userChatMessagesBox');
            const userSendForm = document.getElementById('userSendChatForm');
            const userInput = document.getElementById('userChatInput');

            if (btnUserToggle && userPanel) {
                btnUserToggle.addEventListener('click', function () {
                    userPanel.classList.toggle('active');
                    if (userPanel.classList.contains('active')) {
                        loadUserMessages();
                    }
                });
                btnUserClose.addEventListener('click', function () {
                    userPanel.classList.remove('active');
                });
            }

            function loadUserMessages() {
                fetch("{{ route('user.chat.messages') }}")
                    .then(r => r.json())
                    .then(msgs => {
                        let html = '';
                        if (!msgs || msgs.length === 0) {
                            html = `<div class="text-center text-muted small py-3">Xin chào! Bạn cần shop tư vấn gì ạ?</div>`;
                        } else {
                            msgs.forEach(m => {
                                let isMe = (m.sender_id == {{ auth()->id() ?? 0 }});
                                let text = getMessageText(m);
                                html += `<div class="${isMe ? 'msg-bubble-sent' : 'msg-bubble-received'}"><div class="small">${text}</div></div>`;
                            });
                        }
                        userMessagesBox.innerHTML = html;
                        userMessagesBox.scrollTop = userMessagesBox.scrollHeight;
                    }).catch(() => {});
            }

            if (userSendForm) {
                userSendForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    let msg = userInput.value.trim();
                    if (!msg) return;

                    fetch("{{ route('user.chat.send') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ message: msg })
                    }).then(() => {
                        userInput.value = '';
                        loadUserMessages();
                    });
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>