@extends('layouts.app')

@section('title', 'Hòm Thư Phản Hồi - XFAN Store')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold text-primary mb-1">
                <i class="bi bi-inbox-fill me-2"></i>Thư Phản Hồi & Hỗ Trợ
            </h3>
            <p class="text-muted small mb-0">Lịch sử trao đổi 2 chiều giữa bạn và Ban quản trị XFAN Store</p>
        </div>
        <a href="{{ route('user.support.form') }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-pencil-square me-1"></i> Gửi thư hỗ trợ mới
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4 py-3">Mã Thư</th>
                            <th class="py-3">Tiêu đề / Nội dung đã gửi</th>
                            <th class="py-3 text-center">Ngày gửi</th>
                            <th class="py-3 text-center">Trạng thái</th>
                            <th class="pe-4 py-3 text-end">Chi tiết hội thoại</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td class="ps-4 fw-bold text-primary">#{{ $req->id }}</td>
                                <td>
                                    <div class="fw-bold text-dark mb-1">{{ $req->subject ?? 'Yêu cầu hỗ trợ' }}</div>
                                    <div class="text-muted small text-truncate" style="max-width: 300px;">
                                        {{ $req->message ?? $req->content }}
                                    </div>
                                </td>
                                <td class="text-center text-muted small">
                                    <i class="bi bi-clock me-1"></i>{{ $req->created_at ? $req->created_at->format('H:i - d/m/Y') : '' }}
                                </td>
                                <td class="text-center">
                                    @if(!empty($req->reply_content) || $req->status === 'replied')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                                            <i class="bi bi-check-circle-fill me-1"></i>Đã phản hồi
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill">
                                            <i class="bi bi-hourglass-split me-1"></i>Đang chờ xử lý
                                        </span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#viewReplyModal{{ $req->id }}">
                                        <i class="bi bi-chat-dots-fill me-1"></i>Xem hội thoại
                                    </button>

                                    <!-- Modal Chi Tiết Hội Thoại 2 Chiều -->
                                    <div class="modal fade text-start" id="viewReplyModal{{ $req->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header bg-primary text-white rounded-top-4">
                                                    <h5 class="modal-title fw-bold">
                                                        <i class="bi bi-chat-left-text-fill me-2"></i>Chi Tiết Yêu Cầu Hỗ Trợ #{{ $req->id }}
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <!-- Nội dung đã gửi -->
                                                    <div class="p-3 bg-light rounded-3 border">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <strong class="text-dark"><i class="bi bi-person-circle me-1 text-primary"></i>Nội dung trao đổi:</strong>
                                                            <small class="text-muted">{{ $req->created_at ? $req->created_at->format('H:i d/m/Y') : '' }}</small>
                                                        </div>
                                                        <div class="fw-bold text-primary mb-1">Tiêu đề: {{ $req->subject }}</div>
                                                        <p class="mb-0 text-dark small" style="white-space: pre-wrap;">{{ $req->message ?? $req->content }}</p>

                                                        <!-- Tệp đính kèm / Ảnh đính kèm -->
                                                        @if(!empty($req->attachment_path))
                                                            @php
                                                                $cleanPath = ltrim(str_replace(['public/', 'storage/'], '', $req->attachment_path), '/');
                                                                $fullPath = storage_path('app/public/' . $cleanPath);
                                                                $filePath = asset('storage/' . $cleanPath);
                                                                $fileName = basename($cleanPath);
                                                                $ext = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
                                                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                                $fileExists = !empty($cleanPath) && file_exists($fullPath);
                                                            @endphp

                                                            <div class="mt-3 p-3 bg-white rounded-3 border shadow-sm">
                                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                                    <span class="small fw-bold text-dark text-break">
                                                                        <i class="bi bi-paperclip me-1 text-primary"></i>Tệp đính kèm: <code class="text-primary">{{ $fileName }}</code>
                                                                    </span>
                                                                    @if($fileExists)
                                                                        <a href="{{ $filePath }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                                            <i class="bi bi-box-arrow-up-right me-1"></i>Mở / Tải về
                                                                        </a>
                                                                    @endif
                                                                </div>

                                                                @if($isImage)
                                                                    <div class="text-center mt-3 pt-3 border-top">
                                                                        @if($fileExists)
                                                                            <img src="{{ $filePath }}" alt="{{ $fileName }}" class="img-fluid rounded-3 border shadow-sm" style="max-height: 380px; object-fit: contain;">
                                                                        @else
                                                                            <div class="alert alert-secondary fs-7 py-2 my-0">
                                                                                <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Tệp ảnh cũ không còn tồn tại trên bộ nhớ máy chủ.
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <!-- Nội dung Admin phản hồi riêng -->
                                                    @if(!empty($req->reply_content) && $req->reply_content !== ($req->message ?? $req->content))
                                                        <div class="mt-3 p-3 bg-success-subtle rounded-3 border border-success-subtle">
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <strong class="text-success"><i class="bi bi-shield-check me-1"></i>Ban Quản Trị XFAN Store Trả Lời:</strong>
                                                                <small class="text-muted">{{ $req->updated_at ? $req->updated_at->format('H:i d/m/Y') : '' }}</small>
                                                            </div>
                                                            <p class="mb-0 text-dark fw-semibold small" style="white-space: pre-wrap;">{{ $req->reply_content }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer bg-light border-0 rounded-bottom-4">
                                                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Đóng</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    <span>Bạn chưa gửi thư hỗ trợ nào.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(method_exists($requests, 'hasPages') && $requests->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection