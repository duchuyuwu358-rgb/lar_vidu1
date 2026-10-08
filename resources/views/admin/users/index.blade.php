@extends('layouts.app')

@section('title', 'Quản Lý Tài Khoản - XFAN Store')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-primary">
            <i class="bi bi-people-fill me-2"></i>Danh Sách Tài Khoản
        </h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="bi bi-person-plus-fill me-1"></i> Thêm Tài Khoản
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Bộ lọc & Tìm kiếm -->
    <div class="card mb-4 border-0 shadow-sm rounded-3">
        <div class="card-body p-3">
            <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Tìm theo tên hoặc email...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="role" class="form-select">
                        <option value="all">-- Tất cả vai trò --</option>
                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Quản trị viên (Admin)</option>
                        <option value="staff" {{ $role === 'staff' ? 'selected' : '' }}>Nhân viên (Staff)</option>
                        <option value="user" {{ $role === 'user' ? 'selected' : '' }}>Khách hàng (User)</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng danh sách tài khoản -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Tên người dùng</th>
                            <th>Email</th>
                            <th>Ngày đăng ký</th>
                            <th>Xác minh</th>
                            <th>Quyền (Role)</th>
                            <th class="text-end pe-4">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td class="ps-3 fw-bold">#{{ $u->id }}</td>
                                <td class="fw-semibold">{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td class="text-muted small">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $u->created_at ? $u->created_at->format('d/m/Y H:i') : 'Chưa rõ' }}
                                </td>
                                <td>
                                    @if($u->email_verified_at)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i>Đã xác minh
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                            <i class="bi bi-clock me-1"></i>Chưa xác minh
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.users.update', $u->id) }}" method="POST" class="d-flex align-items-center gap-1">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="name" value="{{ $u->name }}">
                                        <input type="hidden" name="email" value="{{ $u->email }}">
                                        
                                        @if($u->id === auth()->id())
                                            <span class="badge bg-primary px-3 py-2">Tài khoản bạn</span>
                                        @else
                                            <select name="role" class="form-select form-select-sm" style="width: 130px;">
                                                <option value="user" {{ $u->role === 'user' ? 'selected' : '' }}>Khách hàng</option>
                                                <option value="staff" {{ $u->role === 'staff' ? 'selected' : '' }}>Nhân viên</option>
                                                <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary py-1 px-2" title="Lưu vai trò"><i class="bi bi-save"></i></button>
                                        @endif
                                    </form>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Nút Sửa & Đổi mật khẩu -->
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}">
                                            <i class="bi bi-pencil-square me-1"></i>Sửa / Đổi MK
                                        </button>

                                        <!-- Nút Xóa (cho phép xóa bất kỳ tài khoản nào trừ chính tài khoản đang đăng nhập) -->
                                        @if($u->id !== auth()->id())
                                            <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản {{ $u->email }} không?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa tài khoản">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <!-- Modal Sửa & Đổi mật khẩu cho user #{{ $u->id }} -->
                                    <div class="modal fade text-start" id="editUserModal{{ $u->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.users.update', $u->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold text-primary">
                                                            <i class="bi bi-person-gear me-2"></i>Sửa & Đặt Lại Mật Khẩu #{{ $u->id }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Tên người dùng</label>
                                                            <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Địa chỉ Email</label>
                                                            <input type="email" name="email" class="form-control" value="{{ $u->email }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Vai trò (Role)</label>
                                                            <select name="role" class="form-select" {{ $u->id === auth()->id() ? 'disabled' : '' }}>
                                                                <option value="user" {{ $u->role === 'user' ? 'selected' : '' }}>Khách hàng (User)</option>
                                                                <option value="staff" {{ $u->role === 'staff' ? 'selected' : '' }}>Nhân viên (Staff)</option>
                                                                <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Quản trị viên (Admin)</option>
                                                            </select>
                                                            @if($u->id === auth()->id())
                                                                <input type="hidden" name="role" value="{{ $u->role }}">
                                                            @endif
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold text-danger">Đặt Lại Mật Khẩu Mới</label>
                                                            <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu mới (để trống nếu không đổi)...">
                                                            <small class="text-muted d-block mt-1">Mật khẩu lưu trong CSDL mã hóa 1 chiều. Nhập mật khẩu mới tại đây nếu muốn thiết lập lại mật khẩu mới cho người dùng.</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Hủy</button>
                                                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Cập nhật</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Không tìm thấy tài khoản nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Thêm Tài Khoản Mới -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-person-plus-fill me-2"></i>Thêm Tài Khoản Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên người dùng <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Nhập họ và tên..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="nhanvien@gmail.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vai trò (Role) <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="user">Khách hàng (User)</option>
                            <option value="staff">Nhân viên (Staff)</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Tạo tài khoản</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection