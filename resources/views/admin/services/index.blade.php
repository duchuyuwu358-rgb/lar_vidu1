@extends('layouts.app')

@section('title', 'Quản lý Dịch vụ - Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0"><i class="bi bi-wrench-adjustable-circle me-2"></i>Quản Lý Gói Dịch Vụ</h3>
    <a href="{{ route('admin.services.create') }}" class="btn btn-primary fw-semibold"><i class="bi bi-plus-lg me-1"></i> Thêm Gói Mới</a>
</div>

@if(session('status'))
    <div class="alert alert-success border-0 rounded-3 mb-3">{{ session('status') }}</div>
@endif

<!-- BỘ LỌC VÀ TÌM KIẾM -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="{{ route('admin.services.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Nhập tên gói dịch vụ cần tìm..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select bg-light">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Còn hàng</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Hết hàng</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold"><i class="bi bi-filter me-1"></i> Lọc</button>
                @if(request('search') || request('status') !== null)
                    <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary" title="Xóa bộ lọc"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- BẢNG DANH SÁCH -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 80px;">Hình ảnh</th>
                    <th>Tên gói</th>
                    <th>Giá dịch vụ</th>
                    <th>Trạng thái</th>
                    <th>Mô tả</th>
                    <th class="text-end pe-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $service)
                    <tr>
                        <td class="ps-3">
                            @if($service->image)
                                <img src="{{ asset('storage/' . $service->image) }}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 50px; height: 50px;">
                                    <i class="bi bi-image fs-4"></i>
                                </div>
                            @endif
                        </td>
                        <td class="fw-bold text-dark">{{ $service->name }}</td>
                        <td class="text-primary fw-bold">{{ number_format($service->price, 0, ',', '.') }}đ</td>
                        <td>
                            @if($service->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Còn hàng</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Hết hàng</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ Str::limit($service->description, 50) }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil-square"></i> Sửa
                            </a>
                            <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa gói này?')">
                                @csrf 
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                            Không tìm thấy gói dịch vụ nào phù hợp.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($services->hasPages())
        <div class="card-footer bg-white border-0 pt-3">
            {{ $services->links() }}
        </div>
    @endif
</div>
@endsection