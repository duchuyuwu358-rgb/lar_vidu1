@extends('layouts.app')

@section('title', 'Dịch Vụ Vệ Sinh & Lắp Đặt - XFAN STORE')

@section('content')
<div class="container py-4">

    <!-- Hero Banner Khổ Lớn Mở Rộng Dòng Chữ -->
    <div class="position-relative rounded-4 overflow-hidden mb-4 shadow text-white" 
         style="background: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.88)), url('https://images.unsplash.com/photo-1581578731548-c64695cc6952?q=80&w=1600&auto=format&fit=crop') center/cover no-repeat; padding: 3.5rem 2.5rem;">
        <div class="container py-2">
            <div class="row align-items-center">
                <div class="col-lg-12 text-center text-lg-start">
                    <span class="badge bg-warning text-dark text-uppercase px-3 py-2 mb-3 fw-bold fs-6">
                        <i class="fas fa-tools me-1"></i> KỸ THUẬT VIÊN CHUYÊN NGHIỆP
                    </span>

                    <!-- Tiêu đề lớn rộng dòng -->
                    <h1 class="fw-bold fs-1 mb-3 text-white lh-base">
                        Dịch Vụ Vệ Sinh & Lắp Đặt Máy Hút Mùi
                    </h1>

                    <!-- Đoạn văn bản mở rộng full width -->
                    <p class="fs-5 text-light opacity-90 mb-4 w-100 ps-0">
                        Đội ngũ kỹ thuật tận tâm - Phục vụ nhanh chóng tại nhà - Bảo hành dịch vụ 30 ngày.
                    </p>

                    <!-- Badge chỉnh sửa chữ -->
                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
                        <span class="badge bg-white text-dark shadow-sm px-3 py-2 fs-6 fw-semibold rounded-pill">
                            <i class="fas fa-check-circle text-success me-1"></i> Vệ sinh bụi bẩn
                        </span>
                        <span class="badge bg-white text-dark shadow-sm px-3 py-2 fs-6 fw-semibold rounded-pill">
                            <i class="fas fa-clock text-warning me-1"></i> Phục vụ 24/7
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thanh Tìm Kiếm & Bộ Lọc Dịch Vụ -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('services.index') }}" method="GET" class="row g-3">
                
                <!-- Ô tìm kiếm từ khóa -->
                <div class="col-md-5">
                    <label class="form-label fw-bold text-secondary small">Tìm kiếm dịch vụ</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" 
                               placeholder="Tìm kiếm tên dịch vụ..." value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Lọc theo giá -->
                <div class="col-md-3">
                    <label class="form-label fw-bold text-secondary small">Khoảng giá</label>
                    <select name="price_range" class="form-select bg-light">
                        <option value="">-- Tất cả mức giá --</option>
                        <option value="under_300" {{ request('price_range') === 'under_300' ? 'selected' : '' }}>Dưới 300.000 đ</option>
                        <option value="300_500" {{ request('price_range') === '300_500' ? 'selected' : '' }}>300.000 đ - 500.000 đ</option>
                        <option value="above_500" {{ request('price_range') === 'above_500' ? 'selected' : '' }}>Trên 500.000 đ</option>
                    </select>
                </div>

                <!-- Sắp xếp -->
                <div class="col-md-2">
                    <label class="form-label fw-bold text-secondary small">Sắp xếp</label>
                    <select name="sort" class="form-select bg-light">
                        <option value="">-- Mặc định --</option>
                        <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Giá tăng dần</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Giá giảm dần</option>
                    </select>
                </div>

                <!-- Nút Lọc & Nút Đặt lại -->
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fas fa-filter me-1"></i> Lọc
                    </button>
                    @if(request()->hasAny(['search', 'price_range', 'sort']))
                        <a href="{{ route('services.index') }}" class="btn btn-outline-secondary" title="Xóa bộ lọc">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

            </form>
        </div>
    </div>

    <!-- Thông báo kết quả tìm kiếm nếu có -->
    @if(request('search'))
        <p class="text-muted mb-3 fs-6">
            <i class="fas fa-info-circle text-primary me-1"></i> Kết quả tìm kiếm cho từ khóa: <strong class="text-dark">"{{ request('search') }}"</strong>
        </p>
    @endif

    <!-- Danh sách Dịch vụ -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
        @forelse($services as $service)
            <div class="col">
                <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                    
                    <!-- Xử lý Ảnh Dịch vụ -->
                    <div class="ratio ratio-16x9 bg-light">
                        @php
                            $rawImage = trim($service->image ?? $service->photo ?? $service->thumbnail ?? '');
                            $imageUrl = null;

                            if (!empty($rawImage)) {
                                if (\Illuminate\Support\Str::startsWith($rawImage, ['http://', 'https://', 'data:image/'])) {
                                    $imageUrl = $rawImage;
                                } else {
                                    $filename = basename($rawImage);
                                    if (\Illuminate\Support\Str::contains($rawImage, 'services/')) {
                                        $imageUrl = asset('storage/' . ltrim($rawImage, '/'));
                                    } else {
                                        $imageUrl = asset('storage/services/' . $filename);
                                    }
                                }
                            } else {
                                $imageUrl = $service->image_url ?? 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=600&q=80';
                            }
                        @endphp

                        <img src="{{ $imageUrl }}" 
                             class="card-img-top object-fit-cover" 
                             alt="{{ $service->name }}"
                             onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=600&q=80';">
                    </div>

                    <!-- Nội dung Dịch vụ -->
                    <div class="card-body d-flex flex-column p-4">
                        <h5 class="card-title fw-bold text-dark mb-2 fs-6">{{ $service->name }}</h5>
                        <p class="card-text text-primary fs-5 fw-bold mb-2">
                            {{ number_format($service->price, 0, ',', '.') }}đ
                        </p>
                        <p class="card-text text-muted small flex-grow-1">
                            {{ Str::limit($service->description ?? 'Dịch vụ chất lượng cao, phục vụ nhanh chóng và chuyên nghiệp.', 100) }}
                        </p>

                        <!-- Form/Nút Đăng ký dịch vụ -->
                        <form action="{{ Route::has('services.addToCart') ? route('services.addToCart', $service->id) : url('/dich-vu/add-to-cart/' . $service->id) }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-pill">
                                <i class="fas fa-cart-plus me-1"></i> Đăng ký dịch vụ
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 shadow-sm border">
                    <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold text-secondary">Không tìm thấy dịch vụ phù hợp</h5>
                    <p class="text-muted mb-3">Vui lòng thử lại với từ khóa hoặc bộ lọc khác.</p>
                    <a href="{{ route('services.index') }}" class="btn btn-primary fw-bold rounded-pill px-4">
                        <i class="fas fa-sync-alt me-1"></i> Xem tất cả dịch vụ
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Phân trang -->
    @if(method_exists($services, 'links') && $services->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $services->appends(request()->query())->links() }}
        </div>
    @endif

</div>
@endsection