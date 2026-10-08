@extends('layouts.app')

@section('title', 'Hòm Thư Hỗ Trợ Khách Hàng - XFAN Store')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-primary">
            <i class="bi bi-inbox-fill me-2"></i>Hòm Thư Hỗ Trợ Khách Hàng
        </h4>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- KHỐI THỐNG KÊ SỐ LƯỢNG THƯ -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-primary text-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <h6 class="text-white-50 mb-1 fw-semibold">Tổng số thư nhận được</h6>
                        <h3 class="fw-bold mb-0">{{ $totalCount ?? 0 }}</h3>
                    </div>
                    <i class="bi bi-envelope-fill fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-warning text-dark">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <h6 class="text-dark-50 mb-1 fw-semibold">Thư đang chờ xử lý</h6>
                        <h3 class="fw-bold mb-0">{{ $pendingCount ?? 0 }}</h3>
                    </div>
                    <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-success text-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <h6 class="text-white-50 mb-1 fw-semibold">Thư đã phản hồi</h6>
                        <h3 class="fw-bold mb-0">{{ $repliedCount ?? 0 }}</h3>
                    </div>
                    <i class="bi bi-check-circle-fill fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- BỘ LỌC TÌM KIẾM -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.support.index') }}" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Tìm tên, email hoặc tiêu đề thư..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="all">-- Tất cả trạng thái --</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                        <option value="replied" {{ request('status') == 'replied' ? 'selected' : '' }}>Đã phản hồi</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                    <a href="{{ route('admin.support.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH THƯ -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 70px;">ID</th>
                            <th>Khách hàng</th>
                            <th>SĐT / Email</th>
                            <th>Tiêu đề thư</th>
                            <th class="text-center">Tệp đính kèm</th>
                            <th class="text-center">Trạng thái</th>
                            <th>Ngày gửi</th>
                            <th class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supportRequests as $req)
                            <tr>
                                <td class="ps-3 fw-bold">#{{ $req->id }}</td>
                                <td class="fw-semibold">{{ $req->name }}</td>
                                <td>
                                    <div><i class="bi bi-envelope me-1"></i>{{ $req->email }}</div>
                                    @if($req->phone)<small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $req->phone }}</small>@endif
                                </td>
                                <td><span class="fw-bold text-dark">{{ $req->subject }}</span></td>
                                <td class="text-center">
                                    @if($req->attachment_path)
                                        <a href="{{ asset('storage/' . $req->attachment_path) }}" target="_blank" class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 text-decoration-none">
                                            <i class="bi bi-paperclip me-1"></i>Mở Tệp Khách Gửi
                                        </a>
                                    @else
                                        <span class="text-muted fs-7">Không có</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($req->status === 'replied' || !empty($req->reply_content))
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i>Đã phản hồi
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                            ⏳ Chờ xử lý
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $req->created_at ? $req->created_at->format('H:i - d/m/Y') : '' }}</td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewSupportModal{{ $req->id }}">
                                        <i class="bi bi-eye-fill me-1"></i>Xem & Phản hồi
                                    </button>
                                    <form action="{{ route('admin.support.destroy', $req->id) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('Bạn có chắc muốn xóa thư này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>

                                    <!-- MODAL XEM CÂU HỎI & CÂU TRẢ LỜI -->
                                    <div class="modal fade text-start" id="viewSupportModal{{ $req->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.support.reply', $req->id) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="modal-header bg-light">
                                                        <h5 class="modal-title fw-bold"><i class="bi bi-envelope-open-fill me-2"></i>Thư hỗ trợ từ {{ $req->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row mb-3">
                                                            <div class="col-md-6"><strong>Họ tên:</strong> {{ $req->name }}</div>
                                                            <div class="col-md-6"><strong>Email:</strong> {{ $req->email }}</div>
                                                            <div class="col-md-6 mt-2"><strong>Số điện thoại:</strong> {{ $req->phone ?? 'Chưa có' }}</div>
                                                            <div class="col-md-6 mt-2"><strong>Thời gian gửi:</strong> {{ $req->created_at ? $req->created_at->format('H:i d/m/Y') : '' }}</div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Tiêu đề thư:</label>
                                                            <div class="p-2 bg-light rounded border fw-semibold">{{ $req->subject }}</div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold text-dark"><i class="bi bi-person-fill me-1"></i>Nội dung khách hàng viết:</label>
                                                            <div class="p-3 bg-light rounded border mb-2" style="white-space: pre-wrap;">{{ $req->message ?? $req->content }}</div>

                                                            @if($req->attachment_path)
                                                                <div class="p-2 bg-light rounded border d-flex align-items-center justify-content-between">
                                                                    <span><i class="bi bi-paperclip me-1 text-primary"></i> <strong>Tệp / Hình ảnh khách đính kèm:</strong></span>
                                                                    <a href="{{ asset('storage/' . $req->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                        <i class="bi bi-box-arrow-up-right me-1"></i>Mở / Tải về tệp
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <hr class="my-4">

                                                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-reply-fill me-1"></i>Nội dung câu trả lời gửi cho khách</h6>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Nội dung câu trả lời gửi về {{ $req->email }}:</label>
                                                            <textarea name="reply_message" class="form-control" rows="4" placeholder="Nhập câu trả lời cho khách hàng...">{{ old('reply_message', $req->reply_content) }}</textarea>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Gửi kèm Tệp / Hình ảnh cho khách (PDF, Word, Ảnh...):</label>
                                                            <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Trạng thái xử lý:</label>
                                                            <select name="status" class="form-select">
                                                                <option value="pending" {{ $req->status === 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                                                                <option value="replied" {{ $req->status === 'replied' || !empty($req->reply_content) ? 'selected' : '' }}>Đã phản hồi</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                        <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill me-1"></i>Lưu & Gửi Phản Hồi Email</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Chưa có thư hỗ trợ nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 pt-3">
            {{ $supportRequests->links() }}
        </div>
    </div>
</div>
@endsection