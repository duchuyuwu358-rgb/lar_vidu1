@extends('layouts.app')

@section('title', 'Quản Lý Tài Khoản')

@section('content')
<div class="card shadow-sm mt-4 border-0">
    <!-- HEADER & NÚT THÊM TÀI KHOẢN -->
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-users me-2 text-primary"></i>Danh Sách Tài Khoản</h5>
        @if(auth()->user()->role === 'admin')
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus me-1"></i> Thêm Tài Khoản
            </button>
        @endif
    </div>
    
    <div class="card-body">
        <!-- BỘ LỌC TÌM KIẾM -->
        <form method="GET" action="{{ route('users.index') }}" class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tìm tên hoặc email..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="role" class="form-select form-select-sm">
                    <option value="">-- Tất cả vai trò --</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Quản trị viên (Admin)</option>
                    <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Nhân viên (Staff)</option>
                    <option value="customer" {{ request('role') == 'customer' ? 'selected' : '' }}>Khách hàng (Customer)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">Lọc</button>
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary" title="Tải lại"><i class="fas fa-redo"></i></a>
            </div>
        </form>

        <!-- THÔNG BÁO THÀNH CÔNG / LỖI -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- BẢNG DANH SÁCH -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3" width="60">ID</th>
                        <th>Tên người dùng</th>
                        <th>Email</th>
                        <th>Trạng thái xác minh</th>
                        <th>Quyền (Role)</th>
                        <th class="text-center pe-3" width="120">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="ps-3">{{ $user->id }}</td>
                            <td class="fw-bold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->hasVerifiedEmail())
                                    <span class="badge bg-success-subtle text-success border border-success"><i class="fas fa-check-circle me-1"></i>Đã xác minh</span>
                                @else
                                    <span class="badge bg-warning-subtle text-dark border border-warning"><i class="fas fa-clock me-1"></i>Chưa xác minh</span>
                                @endif
                            </td>
                            <td>
                                @if(auth()->user()->role === 'admin')
                                    <!-- Form thay đổi quyền dành cho Admin -->
                                    <form action="{{ route('users.update', $user) }}" method="POST" class="d-flex align-items-center">
                                        @csrf
                                        @method('PUT')
                                        <select name="role" class="form-select form-select-sm me-2" style="width: 130px;" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                            <option value="customer" {{ ($user->role === 'customer' || $user->role === 'user') ? 'selected' : '' }}>Khách hàng</option>
                                            <option value="staff" {{ $user->role === 'staff' ? 'selected' : '' }}>Nhân viên</option>
                                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Quản trị viên</option>
                                        </select>
                                        @if(auth()->id() !== $user->id)
                                            <button type="submit" class="btn btn-sm btn-primary">Lưu</button>
                                        @endif
                                    </form>
                                @else
                                    <!-- Hiển thị tĩnh dành cho Nhân viên (Chỉ xem) -->
                                    @if($user->role === 'admin')
                                        <span class="badge bg-danger">Quản trị viên</span>
                                    @elseif($user->role === 'staff')
                                        <span class="badge bg-info text-dark">Nhân viên</span>
                                    @else
                                        <span class="badge bg-secondary">Khách hàng</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                @if(auth()->user()->role === 'admin')
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa tài khoản">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary">Tài khoản bạn</span>
                                    @endif
                                @else
                                    <span class="badge bg-light text-muted border">Chỉ xem</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Không tìm thấy tài khoản nào phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card-footer bg-white py-3 border-top">
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- MODAL THÊM TÀI KHOẢN MỚI (CHỈ ADMIN THẤY) -->
@if(auth()->user()->role === 'admin')
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="addUserModalLabel"><i class="fas fa-user-plus me-2"></i>Thêm Tài Khoản Mới</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Nhập tên người dùng...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa chỉ Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required placeholder="Tối thiểu 6 ký tự...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phân quyền <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="customer">Khách hàng (Customer)</option>
                            <option value="staff">Nhân viên (Staff)</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary fw-bold">Tạo Tài Khoản</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection