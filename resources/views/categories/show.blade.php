@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-0">{{ $category->name }}</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('categories.edit', $category) }}" class="btn btn-warning text-dark me-1">
                <i class="fas fa-edit me-1"></i> Sửa
            </a>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay Lại
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Thông Tin Danh Mục</h5>
                </div>
                <div class="card-body p-4">
                    <p class="mb-2">
                        <strong>Tên:</strong> {{ $category->name }}
                    </p>
                    <p class="mb-2">
                        <strong>Slug:</strong> <code>{{ $category->slug }}</code>
                    </p>
                    <p class="mb-2">
                        <strong>Mô Tả:</strong><br>
                        <span class="text-muted">{{ $category->description ?? 'Chưa có mô tả' }}</span>
                    </p>
                    <p class="mb-0">
                        <strong>Trạng Thái:</strong>
                        <span class="badge bg-{{ $category->status_color_class }}">
                            {{ $category->status_label }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Máy Hút Mùi Trong Danh Mục</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tên Máy</th>
                                <th>Model</th>
                                <th>Giá</th>
                                <th>Tồn Kho</th>
                                <th class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hoods as $hood)
                                <tr>
                                    <td>#{{ $hood->id }}</td>
                                    <td><strong>{{ $hood->name }}</strong></td>
                                    <td><code>{{ $hood->model ?? 'N/A' }}</code></td>
                                    <td>{{ $hood->price ? number_format($hood->price, 0, ',', '.') . ' đ' : 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $hood->stock_quantity > 0 ? 'success' : 'danger' }}">
                                            {{ $hood->stock_quantity }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('hoods.show', $hood) }}" class="btn btn-sm btn-info text-white" title="Xem Chi Tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <p class="text-muted mb-0">Không có máy hút mùi nào trong danh mục này</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($hoods->hasPages())
                <div class="d-flex justify-content-center mb-4">
                    {{ $hoods->links() }}
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">Thông Tin Khác</h5>
                </div>
                <div class="card-body p-4">
                    <p class="mb-3">
                        <small class="text-muted d-block">ID Danh Mục</small>
                        <strong>#{{ $category->id }}</strong>
                    </p>
                    <p class="mb-3">
                        <small class="text-muted d-block">Ngày Tạo</small>
                        <strong>{{ $category->created_at ? $category->created_at->format('d/m/Y H:i') : 'N/A' }}</strong>
                    </p>
                    <p class="mb-3">
                        <small class="text-muted d-block">Cập Nhật Lần Cuối</small>
                        <strong>{{ $category->updated_at ? $category->updated_at->format('d/m/Y H:i') : 'N/A' }}</strong>
                    </p>
                    <hr>
                    <p class="mb-0">
                        <small class="text-muted d-block">Tổng Máy Hút Mùi</small>
                        <strong class="text-primary fs-5 mb-0">{{ $hoods->total() }} sản phẩm</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection