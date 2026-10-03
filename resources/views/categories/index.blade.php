@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Quản Lý Danh Mục</h1>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">+ Thêm Danh Mục</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Thống kê nhanh -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-primary text-white p-3 shadow-sm">
                <h5>Tổng Danh Mục</h5>
                <h3 class="mb-0">{{ $totalCategories ?? $categories->count() }}</h3>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success text-white p-3 shadow-sm">
                <h5>Danh Mục Hoạt Động</h5>
                <h3 class="mb-0">{{ $activeCategories ?? $categories->where('is_active', true)->count() }}</h3>
            </div>
        </div>
    </div>

    <!-- Bộ lọc & Tìm kiếm -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ route('categories.index') }}" method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Tìm kiếm danh mục..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">-- Tất cả Trạng Thái --</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Ngừng hoạt động</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-info text-white w-100">Tìm Kiếm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng danh sách danh mục -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Tên Danh Mục</th>
                            <th>Slug</th>
                            <th>Số Máy</th>
                            <th>Mô Tả</th>
                            <th>Trạng Thái</th>
                            <th class="text-center text-nowrap" style="width: 170px;">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td class="fw-bold">#{{ $category->id }}</td>
                                <td class="fw-bold">{{ $category->name }}</td>
                                <td class="text-muted">{{ $category->slug }}</td>
                                <td>
                                    <span class="badge bg-info text-white">{{ $category->hoods_count ?? 0 }} sản phẩm</span>
                                </td>
                                <td>{{ $category->description ?? '-' }}</td>
                                <td>
                                    @if ($category->is_active ?? true)
                                        <span class="badge bg-success">Đang hoạt động</span>
                                    @else
                                        <span class="badge bg-secondary">Ngừng hoạt động</span>
                                    @endif
                                </td>
                                <!-- BỘ 3 NÚT HÀNH ĐỘNG DÀN CÙNG 1 HÀNG NGANG -->
                                <td class="text-center text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <a href="{{ route('categories.show', $category) }}" class="btn btn-sm btn-info text-white px-2">Xem</a>
                                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-warning text-white px-2">Sửa</a>
                                        <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger px-2">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Không tìm thấy danh mục nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection