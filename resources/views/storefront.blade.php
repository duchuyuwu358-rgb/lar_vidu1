@extends('layouts.app')

@section('title', 'Cửa Hàng Máy Hút Mùi - XFAN STORE')

@section('content')
<!-- Hero Banner Khổ Lớn Mở Rộng Dòng Chữ -->
<div class="position-relative rounded-4 overflow-hidden mb-4 shadow text-white" 
     style="background: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.88)), url('https://images.unsplash.com/photo-1556911220-e15b29be8c8f?q=80&w=1600&auto=format&fit=crop') center/cover no-repeat; padding: 3.5rem 2.5rem;">
    <div class="container-fluid py-2">
        <div class="row align-items-center">
            <div class="col-lg-12 text-center text-lg-start">
                <span class="badge bg-primary text-uppercase px-3 py-2 mb-3 fw-bold fs-6">
                    <i class="fas fa-star me-1 text-warning"></i> XFAN STORE • CHÍNH HÃNG 100%
                </span>
                
                <!-- Tiêu đề lớn & rộng dòng -->
                <h1 class="fw-bold fs-1 mb-3 text-white lh-base">
                    Cửa Hàng Máy Hút Mùi Cao Cấp
                </h1>

                <!-- Đoạn mô tả mở rộng thoải mái -->
                <p class="fs-5 text-light opacity-90 mb-4 w-100 ps-0">
                    Xin chào <strong>{{ auth()->user()->name ?? 'Admin' }}</strong>! Khám phá các dòng sản phẩm máy hút mùi chính hãng, giữ không gian bếp luôn thoáng mát và sang trọng.
                </p>

                <!-- Badge chỉnh sửa chữ -->
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
                    <span class="badge bg-white text-dark shadow-sm px-3 py-2 fs-6 fw-semibold rounded-pill">
                        <i class="fas fa-shield-alt text-warning me-1"></i> Bảo hành chính hãng 24 tháng
                    </span>
                    <span class="badge bg-white text-dark shadow-sm px-3 py-2 fs-6 fw-semibold rounded-pill">
                        <i class="fas fa-truck text-primary me-1"></i> Giao hàng toàn quốc
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Thanh Tìm Kiếm & Bộ Lọc -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
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
                @if(request()->anyFilled(['search', 'category', 'category_id', 'price_range']))
                    <a href="{{ route('storefront') }}" class="btn btn-outline-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Danh Sách Sản Phẩm -->
<div class="row g-4 mb-4">
    @forelse($hoods as $hood)
        <div class="col-md-4 col-sm-6">
            <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                <!-- Product Image -->
                <div class="text-center pt-3 px-3 d-flex align-items-center justify-content-center bg-white" style="height: 220px;">
                    @php
                        $rawImage = $hood->image ?? $hood->image_url ?? $hood->photo ?? $hood->thumbnail ?? $hood->category?->image;
                        $imageUrl = null;
                        if ($rawImage) {
                            $rawImage = trim($rawImage);
                            if (\Illuminate\Support\Str::startsWith($rawImage, ['http://', 'https://', 'data:image/'])) {
                                $imageUrl = $rawImage;
                            } else {
                                $filename = basename($rawImage);
                                $imageUrl = asset('storage/hoods/' . $filename);
                            }
                        }
                    @endphp

                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" 
                             alt="{{ $hood->name }}" 
                             class="img-fluid rounded" 
                             style="max-height: 100%; max-width: 100%; object-fit: contain;"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'bg-light d-flex align-items-center justify-content-center w-100 h-100 rounded\'><i class=\'fas fa-fan fa-4x text-secondary\'></i></div>';">
                    @else
                        <div class="bg-light d-flex align-items-center justify-content-center w-100 h-100 rounded">
                            <i class="fas fa-fan fa-4x text-secondary"></i>
                        </div>
                    @endif
                </div>

                <!-- Card Body -->
                <div class="card-body d-flex flex-column p-4">
                    <div class="mb-2">
                        <span class="badge bg-light text-primary border">{{ $hood->category->name ?? 'Gia dụng' }}</span>
                    </div>
                    <h5 class="card-title fw-bold text-dark fs-6">{{ $hood->name }}</h5>
                    <p class="card-text text-muted small mb-3">
                        Mã SP: <code>{{ $hood->code ?? $hood->model ?? 'N/A' }}</code>
                    </p>
                    
                    <div class="mt-auto pt-3 d-flex justify-content-between align-items-center border-top">
                        <span class="text-primary fw-bold fs-5">
                            {{ $hood->price ? number_format($hood->price, 0, ',', '.') . ' đ' : 'Liên hệ' }}
                        </span>
                        <a href="{{ route('storefront.show', $hood->id) }}" class="btn btn-outline-primary btn-sm fw-semibold rounded-pill px-3">
                            <i class="fas fa-eye me-1"></i> Chi Tiết
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5 bg-white rounded-4 shadow-sm border">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <p class="text-muted fs-5 mb-0">Không tìm thấy sản phẩm nào phù hợp với bộ lọc.</p>
                @if(request()->anyFilled(['search', 'category', 'category_id', 'price_range']))
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