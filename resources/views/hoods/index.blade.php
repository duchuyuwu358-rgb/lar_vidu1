@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Quản Lý Máy Hút Mùi</h1>
        <a href="{{ route('hoods.create') }}" class="btn btn-primary">+ Thêm Máy Hút Mùi</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Thống kê nhanh -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white p-3 shadow-sm">
                <h5>Tổng Số Lượng</h5>
                <h3 class="mb-0">{{ $totalHoods }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white p-3 shadow-sm">
                <h5>Đang Bán</h5>
                <h3 class="mb-0">{{ $sellingHoods }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white p-3 shadow-sm">
                <h5>Hết Hàng / Ngừng Bán</h5>
                <h3 class="mb-0">{{ $soldOutHoods }}</h3>
            </div>
        </div>
    </div>

    <!-- Bộ lọc & Tìm kiếm -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ route('hoods.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Tìm theo tên hoặc model..." value="{{ $search }}">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">-- Tất cả danh mục --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $category == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">-- Tất cả loại --</option>
                        @foreach ($types as $val => $label)
                            <option value="{{ $val }}" {{ $type == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="selling" {{ $status === 'selling' ? 'selected' : '' }}>Đang bán</option>
                        <option value="importing" {{ $status === 'importing' ? 'selected' : '' }}>Đang nhập</option>
                        <option value="sold_out" {{ $status === 'sold_out' ? 'selected' : '' }}>Hết hàng</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary w-100">Lọc</button>
                    <a href="{{ route('hoods.index') }}" class="btn btn-outline-secondary">Xóa</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng danh sách -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Model</th>
                            <th>Danh mục</th>
                            <th>Loại</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                            <th class="text-center text-nowrap" style="width: 170px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($hoods as $hood)
                            <tr>
                                <td>
                                    @if ($hood->image)
                                        <img src="{{ asset('storage/' . $hood->image) }}" alt="{{ $hood->name }}" width="50" height="50" class="rounded object-fit-cover">
                                    @else
                                        <span class="text-muted small">Không có ảnh</span>
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $hood->name }}</td>
                                <td>{{ $hood->model ?? '-' }}</td>
                                <td>{{ $hood->category->name ?? '-' }}</td>
                                <td>{{ $types[$hood->type] ?? $hood->type }}</td>
                                <td class="text-nowrap">{{ number_format($hood->price, 0, ',', '.') }} VNĐ</td>
                                <td>{{ $hood->stock_quantity }}</td>
                                <td>
                                    @if ($hood->is_active && $hood->stock_quantity > 0)
                                        <span class="badge bg-success">Đang bán</span>
                                    @elseif ($hood->is_active && $hood->stock_quantity <= 0)
                                        <span class="badge bg-warning text-dark">Đang nhập</span>
                                    @else
                                        <span class="badge bg-danger">Ngừng bán</span>
                                    @endif
                                </td>
                                <!-- Cột 3 nút cùng 1 hàng -->
                                <td class="text-center text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <a href="{{ route('hoods.show', $hood) }}" class="btn btn-sm btn-info text-white px-2">Xem</a>
                                        <a href="{{ route('hoods.edit', $hood) }}" class="btn btn-sm btn-warning text-white px-2">Sửa</a>
                                        <form action="{{ route('hoods.destroy', $hood) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger px-2">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Không tìm thấy máy hút mùi nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($hoods->hasPages())
            <div class="card-footer bg-white pt-3">
                {{ $hoods->links() }}
            </div>
        @endif
    </div>
</div>
@endsection