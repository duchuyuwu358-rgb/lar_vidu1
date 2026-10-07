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
                <h3 class="mb-0">{{ $totalHoods ?? 0 }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white p-3 shadow-sm">
                <h5>Đang Bán</h5>
                <h3 class="mb-0">{{ $sellingHoods ?? 0 }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white p-3 shadow-sm">
                <h5>Hết Hàng / Ngừng Bán</h5>
                <h3 class="mb-0">{{ $soldOutHoods ?? 0 }}</h3>
            </div>
        </div>
    </div>

    <!-- Bộ lọc & Tìm kiếm -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ route('hoods.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Tìm theo tên hoặc model..." value="{{ request('search', $search ?? '') }}">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">-- Tất cả danh mục --</option>
                        @if(isset($categories))
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (request('category', $category ?? '') == $cat->id || request('category_id') == $cat->id) ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">-- Tất cả loại --</option>
                        @if(isset($types) && is_array($types))
                            @foreach ($types as $val => $label)
                                <option value="{{ $val }}" {{ request('type', $type ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="selling" {{ request('status', $status ?? '') === 'selling' ? 'selected' : '' }}>Đang bán</option>
                        <option value="importing" {{ request('status', $status ?? '') === 'importing' ? 'selected' : '' }}>Đang nhập</option>
                        <option value="sold_out" {{ request('status', $status ?? '') === 'sold_out' ? 'selected' : '' }}>Hết hàng</option>
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
                                    @php
                                        $rawImage = $hood->image 
                                            ?? $hood->image_url 
                                            ?? $hood->photo 
                                            ?? $hood->thumbnail 
                                            ?? $hood->img 
                                            ?? $hood->picture 
                                            ?? $hood->avatar 
                                            ?? $hood->category?->image;

                                        $imageUrl = null;

                                        if (!empty($rawImage)) {
                                            $path = trim($rawImage);
                                            if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', 'data:image/'])) {
                                                $imageUrl = $path;
                                            } else {
                                                $filename = basename($path);
                                                $imageUrl = asset('storage/hoods/' . $filename);
                                            }
                                        }
                                    @endphp

                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" 
                                             alt="{{ $hood->name }}" 
                                             width="50" 
                                             height="50" 
                                             class="rounded object-fit-cover"
                                             referrerpolicy="no-referrer"
                                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'bg-light rounded d-flex align-items-center justify-content-center text-muted\' style=\'width:50px;height:50px;font-size:0.75rem;\'>No pic</div>';">
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 50px; height: 50px; font-size: 0.75rem;">
                                            No pic
                                        </div>
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $hood->name }}</td>
                                <td>{{ $hood->model ?? '-' }}</td>
                                <td>{{ $hood->category->name ?? '-' }}</td>
                                <td>{{ $types[$hood->type] ?? $hood->type ?? '-' }}</td>
                                <td class="text-nowrap">{{ $hood->price ? number_format($hood->price, 0, ',', '.') . ' VNĐ' : 'Liên hệ' }}</td>
                                <td>{{ $hood->stock_quantity ?? 0 }}</td>
                                <td>
                                    @if (($hood->is_active ?? true) && ($hood->stock_quantity ?? 0) > 0)
                                        <span class="badge bg-success">Đang bán</span>
                                    @elseif (($hood->is_active ?? true) && ($hood->stock_quantity ?? 0) <= 0)
                                        <span class="badge bg-warning text-dark">Đang nhập</span>
                                    @else
                                        <span class="badge bg-danger">Ngừng bán</span>
                                    @endif
                                </td>
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
        @if (method_exists($hoods, 'hasPages') && $hoods->hasPages())
            <div class="card-footer bg-white pt-3">
                {{ $hoods->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection