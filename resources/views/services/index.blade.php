@extends('layouts.app')

@section('title', 'Dịch Vụ Vệ Sinh & Lắp Đặt')

@section('content')
<div class="container py-4">

    <!-- Banner chính -->
    <div class="p-4 p-md-5 mb-4 text-white rounded-3 bg-primary bg-gradient shadow-sm">
        <h2 class="fw-bold display-6 mb-2">
            <i class="fas fa-tools me-2"></i> Dịch Vụ Vệ Sinh & Lắp Đặt Máy Hút Mùi
        </h2>
        <p class="mb-0 fs-5 opacity-90">Đội ngũ kỹ thuật viên chuyên nghiệp - Tận tâm - Phục vụ tận nhà</p>
    </div>

    <!-- Thanh Tìm Kiếm & Bộ Lọc Dịch Vụ -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('services.index') }}" method="GET" class="row g-3">
                
                <!-- Ô tìm kiếm từ khóa -->
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" 
                               placeholder="Tìm kiếm dịch vụ..." value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Lọc theo giá -->
                <div class="col-md-3">
                    <select name="price_range" class="form-select bg-light">
                        <option value="">-- Mức giá --</option>
                        <option value="under_300" {{ request('price_range') === 'under_300' ? 'selected' : '' }}>Dưới 300.000 đ</option>
                        <option value="300_500" {{ request('price_range') === '300_500' ? 'selected' : '' }}>300.000 đ - 500.000 đ</option>
                        <option value="above_500" {{ request('price_range') === 'above_500' ? 'selected' : '' }}>Trên 500.000 đ</option>
                    </select>
                </div>

                <!-- Sắp xếp -->
                <div class="col-md-2">
                    <select name="sort" class="form-select bg-light">
                        <option value="">-- Sắp xếp --</option>
                        <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Giá tăng dần</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Giá giảm dần</option>
                    </select>
                </div>

                <!-- Nút Lọc & Nút Đặt lại -->
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        Lọc
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
        <p class="text-muted mb-3">
            Kết quả tìm kiếm cho từ khóa: <strong class="text-dark">"{{ request('search') }}"</strong>
        </p>
    @endif

    <!-- Danh sách Dịch vụ -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @forelse($services as $service)
            <div class="col">
                <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden hover-shadow transition">
                    
                    <!-- Ảnh Dịch vụ -->
                    <div class="ratio ratio-16x9 bg-light">
                        @if(!empty($service->image))
                            <img src="{{ asset('storage/' . $service->image) }}" class="card-img-top object-fit-cover" alt="{{ $service->name }}">
                        @else
                            <div class="d-flex align-items-center justify-content-center text-secondary bg-light">
                                <i class="fas fa-concierge-bell fa-3x"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Nội dung Dịch vụ -->
                    <div class="card-body d-flex flex-column p-4">
                        <h5 class="card-title fw-bold text-dark mb-2">{{ $service->name }}</h5>
                        <p class="card-text text-primary fs-4 fw-bold mb-2">
                            {{ number_format($service->price, 0, ',', '.') }}đ
                        </p>
                        <p class="card-text text-muted small flex-grow-1">
                            {{ Str::limit($service->description ?? 'Dịch vụ chất lượng cao, phục vụ nhanh chóng.', 100) }}
                        </p>

                        <!-- Form/Nút Thêm Giỏ Hàng -->
                        <form action="{{ Route::has('services.addToCart') ? route('services.addToCart', $service->id) : url('/dich-vu/add-to-cart/' . $service->id) }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                                <i class="fas fa-cart-plus me-1"></i> Đăng ký dịch vụ
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-light rounded-3">
                    <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold text-secondary">Không tìm thấy dịch vụ phù hợp</h5>
                    <p class="text-muted mb-3">Vui lòng thử lại với từ khóa hoặc bộ lọc khác.</p>
                    <a href="{{ route('services.index') }}" class="btn btn-primary fw-bold">Xem tất cả dịch vụ</a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Phân trang -->
    @if(method_exists($services, 'links'))
        <div class="d-flex justify-content-center mt-4">
            {{ $services->links() }}
        </div>
    @endif

</div>
@endsection