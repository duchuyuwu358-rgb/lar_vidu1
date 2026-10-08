@extends('layouts.app')

@section('title', 'Quản lý Khuyến mại - XFAN Store')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="bi bi-tags-fill text-primary me-2"></i>Quản lý Mã Khuyến mại</h4>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Thêm Mã Mới
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- BỘ LỌC VÀ TÌM KIẾM --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.coupons.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Tìm theo Mã voucher..." value="{{ $search }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="all" {{ $status == 'all' ? 'selected' : '' }}>-- Tất cả trạng thái --</option>
                        <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Đã khóa</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="all" {{ $type == 'all' ? 'selected' : '' }}>-- Loại giảm giá --</option>
                        <option value="percent" {{ $type == 'percent' ? 'selected' : '' }}>Phần trăm (%)</option>
                        <option value="fixed" {{ $type == 'fixed' ? 'selected' : '' }}>Số tiền cố định (đ)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search me-1"></i>Lọc</button>
                </div>
            </form>
        </div>
    </div>

    {{-- BẢNG DANH SÁCH KHUYẾN MẠI --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Mã Voucher</th>
                        <th>Loại Giảm</th>
                        <th>Mức Giảm</th>
                        <th>Đơn Tối Thiểu</th>
                        <th>Trạng Thái</th>
                        <th>Hạn Sử Dụng</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $cp)
                        <tr>
                            <td>#{{ $cp->id }}</td>
                            <td><span class="badge bg-primary fs-6">{{ $cp->code }}</span></td>
                            <td>{{ $cp->type == 'percent' ? 'Phần trăm (%)' : 'Cố định (đ)' }}</td>
                            <td class="fw-bold text-success">
                                {{ $cp->type == 'percent' ? $cp->value . '%' : number_format($cp->value) . ' đ' }}
                            </td>
                            <td>{{ number_format($cp->min_order_amount) }} đ</td>
                            <td>
                                @if($cp->is_active)
                                    <span class="badge bg-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Đã khóa</span>
                                @endif
                            </td>
                            <td>{{ $cp->expires_at ? $cp->expires_at->format('d/m/Y H:i') : 'Vô thời hạn' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.coupons.edit', $cp->id) }}" class="btn btn-sm btn-outline-warning me-1">
                                    <i class="bi bi-pencil-square"></i> Sửa
                                </a>
                                <form action="{{ route('admin.coupons.destroy', $cp->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mã này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">
                                        <i class="bi bi-trash"></i> Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Không tìm thấy mã khuyến mại nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $coupons->links() }}
        </div>
    </div>
</div>
@endsection