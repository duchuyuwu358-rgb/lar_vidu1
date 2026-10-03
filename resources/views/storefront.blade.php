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

<!-- Product Grid -->
<div class="row g-4 mb-4">
    @forelse($hoods as $hood)
        <div class="col-md-4 col-sm-6">
            <div class="card h-100 shadow-sm border-0">
                <!-- Product Image -->
                <div class="text-center pt-3 px-3">
                    @php
                        // Tự động tìm ảnh: Ảnh sản phẩm -> URL -> Ảnh Danh mục
                        $imagePath = $hood->image ?? $hood->image_url ?? $hood->category?->image;
                    @endphp
                    @if($imagePath)
                        <img src="{{ \Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://']) ? $imagePath : asset('storage/' . $imagePath) }}" 
                             alt="{{ $hood->name }}" 
                             class="card-img-top rounded" 
                             style="height: 200px; object-fit: cover;">
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
                <p class="text-muted fs-5 mb-0">Hiện chưa có sản phẩm nào đang bán.</p>
            </div>
        </div>
    @endforelse
</div>

@if(method_exists($hoods, 'hasPages') && $hoods->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $hoods->appends(request()->query())->links() }}
    </div>
@endif
@endsection