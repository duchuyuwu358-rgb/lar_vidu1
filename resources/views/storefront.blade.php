@extends('layouts.app')

@section('title', 'Cửa Hàng Máy Hút Mùi')

@section('content')
<!-- Hero Banner -->
<div class="p-4 p-md-5 mb-4 bg-white rounded-3 shadow-sm border">
    <div class="container-fluid py-2">
        <h1 class="display-6 fw-bold text-primary">
            <i class="fas fa-store me-2"></i>Cửa Hàng Máy Hút Mùi
        </h1>
        <p class="lead mb-0 text-secondary">
            Xin chào <strong>{{ auth()->user()->name ?? 'Quý khách' }}</strong>. Khám phá các sản phẩm chính hãng đang bán.
        </p>
    </div>
</div>

<!-- Thanh Tìm Kiếm & Bộ Lọc -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3 p-md-4">
        <form action="{{ route('storefront') }}" method="GET" class="row g-3">
            <!-- Ô tìm kiếm -->
            <div class="col-md-4">
                <label class="form-label fw-bold text-secondary small">Tìm kiếm</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0 bg-light" 
                           placeholder="Tên, mã SP hoặc model..." 
                           value="{{ request('search') }}">
                </div>
            </div>

            <!-- Dropdown Danh mục -->
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary small">Danh mục</label>
                <select name="category" class="form-select bg-light">
                    <option value="">-- Tất cả danh mục --</option>
                    @if(isset($categories))
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (request('category') == $cat->id || request('category_id') == $cat->id) ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Dropdown Khoảng giá -->
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary small">Khoảng giá</label>
                <select name="price_range" class="form-select bg-light">
                    <option value="">-- Tất cả mức giá --</option>
                    <option value="under_3m" {{ request('price_range') == 'under_3m' ? 'selected' : '' }}>Dưới 3 triệu</option>
                    <option value="3m_5m" {{ request('price_range') == '3m_5m' ? 'selected' : '' }}>3 - 5 triệu</option>
                    <option value="over_5m" {{ request('price_range') == 'over_5m' ? 'selected' : '' }}>Trên 5 triệu</option>
                </select>
            </div>

            <!-- Nút Lọc & Reset -->
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-filter me-1"></i> Lọc
                </button>
                @if(request()->hasAny(['search', 'category', 'category_id', 'price_range']))
                    <a href="{{ route('storefront') }}" class="btn btn-outline-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Product Grid -->
<div class="row g-4 mb-4">
    @forelse($hoods as $hood)
        <div class="col-md-4 col-sm-6">
            <div class="card h-100 shadow-sm border-0">
                <!-- Product Image -->
                <div class="text-center pt-3 px-3">
                    @php
                        $rawImage = $hood->image ?? $hood->category?->image;
                        $imageUrl = null;
                        if ($rawImage) {
                            $rawImage = trim($rawImage);
                            if (\Illuminate\Support\Str::startsWith($rawImage, ['http://', 'https://', 'data:image/'])) {
                                $imageUrl = $rawImage;
                            } else {
                                // Tự động lấy tên file gốc và ép đường dẫn về storage/hoods/
                                $filename = basename($rawImage);
                                $imageUrl = asset('storage/hoods/' . $filename);
                            }
                        }
                    @endphp
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" 
                             alt="{{ $hood->name }}" 
                             class="card-img-top rounded object-fit-cover" 
                             style="height: 200px;"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'bg-light d-flex align-items-center justify-content-center rounded\' style=\'height: 200px;\'><i class=\'fas fa-fan fa-4x text-secondary\'></i></div>';">
                    @else
                        <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 200px;">
                            <i class="fas fa-fan fa-4x text-secondary"></i>
                        </div>
                    @endif
                </div>

                <!-- Card Body -->
                <div class="card-body d-flex flex-column">
                    <div class="mb-2">
                        <span class="badge bg-secondary">{{ $hood->category->name ?? 'Gia dụng' }}</span>
                    </div>
                    <h5 class="card-title fw-bold text-dark">{{ $hood->name }}</h5>
                    <p class="card-text text-muted small mb-3">
                        Mã SP: <code>{{ $hood->code ?? $hood->model ?? 'N/A' }}</code>
                    </p>
                    
                    <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
                        <span class="text-primary fw-bold fs-5">
                            {{ $hood->price ? number_format($hood->price, 0, ',', '.') . ' đ' : 'Liên hệ' }}
                        </span>
                        <a href="{{ route('storefront.show', $hood->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i> Chi Tiết
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5 bg-white rounded-3 shadow-sm border">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <p class="text-muted fs-5 mb-0">Không tìm thấy sản phẩm nào phù hợp với bộ lọc.</p>
                @if(request()->hasAny(['search', 'category', 'category_id', 'price_range']))
                    <a href="{{ route('storefront') }}" class="btn btn-sm btn-outline-primary mt-3">
                        <i class="fas fa-sync-alt me-1"></i> Xem tất cả sản phẩm
                    </a>
                @endif
            </div>
        </div>
    @endforelse
</div>

<!-- Phân trang -->
@if(method_exists($hoods, 'hasPages') && $hoods->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $hoods->appends(request()->query())->links() }}
    </div>
@endif
@endsection