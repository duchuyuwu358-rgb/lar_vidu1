@extends('layouts.app')

@section('title', 'Cửa Hàng Máy Hút Mùi')

@section('content')
<!-- Hero Banner -->
<div class="p-4 p-md-5 mb-4 bg-white rounded-3 shadow-sm border">
    <div class="container-fluid py-2">
        <h1 class="display-6 fw-bold text-primary">
            <i class="fas fa-store me-2"></i>Cửa Hàng Máy Hút Mùi
        </h1>
        <p class="lead mb-0 text-secondary">
            Xin chào <strong>{{ auth()->user()->name ?? 'Quý khách' }}</strong>. Khám phá các sản phẩm chính hãng đang bán.
        </p>
    </div>
</div>

<!-- Thanh Tìm Kiếm & Bộ Lọc -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3 p-md-4">
        <form action="{{ route('storefront') }}" method="GET" class="row g-3">
            <!-- Ô tìm kiếm -->
            <div class="col-md-4">
                <label class="form-label fw-bold text-secondary small">Tìm kiếm</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0 bg-light" 
                           placeholder="Tên, mã SP hoặc model..." 
                           value="{{ request('search') }}">
                </div>
            </div>

            <!-- Dropdown Danh mục -->
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary small">Danh mục</label>
                <select name="category" class="form-select bg-light">
    <option value="">-- Tất cả danh mục --</option>
    @if(isset($categories))
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ (request('category') == $cat->id || request('category_id') == $cat->id) ? 'selected' : '' }}>
                {{ $cat->name }}
            </option>
        @endforeach
    @endif
</select>
                </select>
            </div>

            <!-- Dropdown Khoảng giá -->
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary small">Khoảng giá</label>
                <select name="price_range" class="form-select bg-light">
                    <option value="">-- Tất cả mức giá --</option>
                    <option value="under_3m" {{ request('price_range') == 'under_3m' ? 'selected' : '' }}>Dưới 3 triệu</option>
                    <option value="3m_5m" {{ request('price_range') == '3m_5m' ? 'selected' : '' }}>3 - 5 triệu</option>
                    <option value="over_5m" {{ request('price_range') == 'over_5m' ? 'selected' : '' }}>Trên 5 triệu</option>
                </select>
            </div>

            <!-- Nút Lọc & Reset -->
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-filter me-1"></i> Lọc
                </button>
                @if(request()->hasAny(['search', 'category', 'category_id', 'price_range']))
                    <a href="{{ route('storefront') }}" class="btn btn-outline-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Product Grid -->
<div class="row g-4 mb-4">
    @forelse($hoods as $hood)
        <div class="col-md-4 col-sm-6">
            <div class="card h-100 shadow-sm border-0">
                <!-- Product Image -->
                <div class="text-center pt-3 px-3">
                    @php
                        $imagePath =$hood->image ?? $hood->image_url ?? $hood->category?->image;
                        if ($imagePath) {
                            $imagePath = ltrim($imagePath, '/');
                        }
                    @endphp
                    @if($imagePath)
                        @php
                            $imageUrl = \Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://', 'uploads/']) 
                                ? asset($imagePath) 
                                : asset('storage/' . $imagePath);
                        @endphp
                        <img src="{{ $imageUrl }}" 
                             alt="{{ $hood->name }}" 
                             class="card-img-top rounded" 
                             style="height: 200px; object-fit: cover;"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'bg-light d-flex align-items-center justify-content-center rounded\' style=\'height: 200px;\'><i class=\'fas fa-fan fa-4x text-secondary\'></i></div>';">
                    @else
                        <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 200px;">
                            <i class="fas fa-fan fa-4x text-secondary"></i>
                        </div>
                    @endif
                </div>

                <!-- Card Body -->
                <div class="card-body d-flex flex-column">
                    <div class="mb-2">
                        <span class="badge bg-secondary">{{ $hood->category->name ?? 'Gia dụng' }}</span>
                    </div>
                    <h5 class="card-title fw-bold text-dark">{{ $hood->name }}</h5>
                    <p class="card-text text-muted small mb-3">
                        Mã SP: <code>{{ $hood->code ?? $hood->model ?? 'N/A' }}</code>
                    </p>
                    
                    <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
                        <span class="text-primary fw-bold fs-5">
                            {{ $hood->price ? number_format($hood->price, 0, ',', '.') . ' đ' : 'Liên hệ' }}
                        </span>
                        <a href="{{ route('storefront.show', $hood->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i> Chi Tiết
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5 bg-white rounded-3 shadow-sm border">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <p class="text-muted fs-5 mb-0">Không tìm thấy sản phẩm nào phù hợp với bộ lọc.</p>
                @if(request()->hasAny(['search', 'category', 'category_id', 'price_range']))
                    <a href="{{ route('storefront') }}" class="btn btn-sm btn-outline-primary mt-3">
                        <i class="fas fa-sync-alt me-1"></i> Xem tất cả sản phẩm
                    </a>
                @endif
            </div>
        </div>
    @endforelse
</div>

<!-- Phân trang -->
@if(method_exists($hoods, 'hasPages') &&$hoods->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $hoods->appends(request()->query())->links() }}
    </div>
@endif

<!-- KHUNG CHAT WIDGET HỖ TRỢ KHÁCH HÀNG -->
<div id="chat-widget-container" class="position-fixed bottom-0 end-0 m-3" style="z-index: 1050;">
    <button id="btn-toggle-chat" class="btn btn-primary rounded-pill shadow-lg px-3 py-2 d-flex align-items-center gap-2">
        <i class="fas fa-comments fs-5"></i>
        <span class="fw-bold">Nhắn tin cho chúng tôi</span>
    </button>

    <div id="chat-box-popup" class="card shadow-lg border-0 rounded-3 mt-2 d-none" style="width: 360px; height: 480px;">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2 px-3">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-success rounded-circle" style="width: 10px; height: 10px;"></div>
                <strong class="small" id="chat-title">Nhắn tin cho chúng tôi</strong>
            </div>
            <button type="button" class="btn-close btn-close-white btn-sm" id="btn-close-chat"></button>
        </div>

        <div class="card-body p-3 overflow-auto" id="chat-body-content" style="height: 360px; background-color: #f8f9fa;">
            <div class="text-center my-4 text-muted small">
                <div class="spinner-border spinner-border-sm me-1" role="status"></div>
                Đang tải dữ liệu chat...
            </div>
        </div>

        <div class="card-footer bg-white border-top p-2" id="chat-footer">
            <form id="chat-form" class="input-group">
                <input type="text" id="chat-input" class="form-control form-control-sm" placeholder="Nhập tin nhắn..." required autocomplete="off">
                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnToggle = document.getElementById('btn-toggle-chat');
    const btnClose = document.getElementById('btn-close-chat');
    const chatPopup = document.getElementById('chat-box-popup');
    const chatBody = document.getElementById('chat-body-content');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const currentUserId = {{ auth()->id() ?? 'null' }};

    let loadedMessages = [];

    btnToggle.addEventListener('click', () => {
        chatPopup.classList.toggle('d-none');
        if (!chatPopup.classList.contains('d-none')) {
            loadChatData();
        }
    });

    btnClose.addEventListener('click', () => {
        chatPopup.classList.add('d-none');
    });

    async function loadChatData() {
        try {
            const response = await fetch('/user/chat/messages', {
                headers: { 
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) throw new Error('Không thể tải tin nhắn');
            const data = await response.json();
            loadedMessages = data.messages || data || [];

            const hasHistory = Array.isArray(loadedMessages) && loadedMessages.length > 0;
            renderBotMenu(hasHistory, loadedMessages);
        } catch (error) {
            console.error('Lỗi tải tin nhắn:', error);
            renderBotMenu(false, []);
        }
    }

    function renderBotMenu(hasHistory, messages) {
        let html = `
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-primary mb-1">XFAN Bot</h6>
                    <p class="small text-muted mb-0">Xin chào, tôi có thể giúp gì cho bạn? Vui lòng chọn một trong các yêu cầu bên dưới:</p>
                </div>
            </div>
            <div class="d-grid gap-2">
        `;

        if (hasHistory) {
            html += `
                <button type="button" id="btn-continue-chat" class="btn btn-success btn-sm text-start fw-bold py-2 shadow-sm">
                    💬 Tiếp tục cuộc trò chuyện với Admin
                </button>
            `;
        }

        html += `
                <button type="button" class="btn btn-outline-secondary btn-sm text-start bg-white" onclick="sendQuickMessage('Tôi muốn tư vấn Mua sản phẩm & Máy hút mùi')">Mua sản phẩm & Máy hút mùi</button>
                <button type="button" class="btn btn-outline-secondary btn-sm text-start bg-white" onclick="sendQuickMessage('Tôi cần Hỗ trợ & Kiểm tra đơn hàng')">Hỗ trợ & Kiểm tra đơn hàng</button>
                <button type="button" class="btn btn-outline-secondary btn-sm text-start bg-white" onclick="sendQuickMessage('Tôi muốn xem Báo giá & Khuyến mãi mới nhất')">Báo giá & Khuyến mãi mới nhất</button>
                <button type="button" class="btn btn-outline-primary btn-sm text-start bg-white fw-bold" onclick="sendQuickMessage('Kết nối trực tiếp với Tư vấn viên')">🎧 Kết nối trực tiếp với Tư vấn viên</button>
            </div>
        `;

        chatBody.innerHTML = html;

        if (hasHistory) {
            const btnContinue = document.getElementById('btn-continue-chat');
            if (btnContinue) {
                btnContinue.addEventListener('click', function() {
                    renderHistory(messages);
                });
            }
        }
    }

    function renderHistory(messages) {
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                <span class="badge bg-secondary">Lịch sử trò chuyện</span>
                <button type="button" id="btn-back-to-bot" class="btn btn-link btn-sm text-muted p-0 text-decoration-none" style="font-size: 0.75rem;">
                    <i class="fas fa-robot me-1"></i> Menu bot
                </button>
            </div>
            <div class="d-flex flex-column gap-2">
        `;

        messages.forEach(msg => {
            const isUser = (msg.sender_id == currentUserId || msg.is_user);
            const msgTime = msg.created_at ? new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';

            html += `
                <div class="d-flex flex-column ${isUser ? 'align-items-end' : 'align-items-start'}">
                    <div class="p-2 rounded-3 ${isUser ? 'bg-primary text-white' : 'bg-white text-dark shadow-sm'}" style="max-width: 85%; font-size: 0.9rem;">
                        ${msg.content || msg.message}
                    </div>
                    <span class="text-muted px-1" style="font-size: 0.68rem;">${msgTime}</span>
                </div>
            `;
        });

        html += `</div>`;
        chatBody.innerHTML = html;
        chatBody.scrollTop = chatBody.scrollHeight;

        const btnBack = document.getElementById('btn-back-to-bot');
        if (btnBack) {
            btnBack.addEventListener('click', () => {
                renderBotMenu(true, messages);
            });
        }
    }

    window.sendQuickMessage = function(text) {
        chatInput.value = text;
        chatForm.dispatchEvent(new Event('submit'));
    };

    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const text = chatInput.value.trim();
        if (!text) return;

        loadedMessages.push({
            sender_id: currentUserId,
            is_user: true,
            content: text,
            created_at: new Date().toISOString()
        });

        renderHistory(loadedMessages);
        chatInput.value = '';

        try {
            await fetch('/user/chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: text })
            });
        } catch (err) {
            console.error('Lỗi gửi tin nhắn:', err);
        }
    });
});
</script>
@endsection