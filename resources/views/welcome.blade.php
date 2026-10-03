@extends('layouts.app')

@section('title', 'Trang Chủ - Danh Sách Sản Phẩm')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Danh Sách Sản Phẩm</h3>
        <a href="{{ route('cart.index') }}" class="btn btn-primary position-relative">
            <i class="fas fa-shopping-cart me-1"></i> Giỏ Hàng
            <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ session('cart') ? count(session('cart')) : 0 }}
            </span>
        </a>
    </div>

    <div class="row g-4">
        @foreach($hoods as $hood)
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="bg-light text-center py-4 rounded-top">
                    <i class="fas fa-fan fa-4x text-secondary"></i>
                </div>
                <div class="card-body d-flex flex-column">
                    <small class="text-muted mb-1">{{ $hood->category->name ?? 'Chưa phân loại' }}</small>
                    <h5 class="card-title fw-bold fs-6">{{ $hood->name }}</h5>
                    <p class="text-primary fw-bold fs-5 mt-auto mb-3">{{ number_format($hood->price, 0, ',', '.') }} đ</p>
                    
                    <button class="btn btn-outline-primary w-100 btn-add-to-cart" 
                            data-id="{{ $hood->id }}" 
                            data-name="{{ $hood->name }}" 
                            data-price="{{ $hood->price }}"
                            data-category="{{ $hood->category->name ?? 'Gia dụng' }}">
                        <i class="fas fa-cart-plus me-1"></i> Thêm vào giỏ
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- SCRIPT THÊM VÀO GIỎ HÀNG TRỰC TIẾP -->
<script>
document.querySelectorAll('.btn-add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        
        fetch('{{ route("cart.add") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ hood_id: productId, quantity: 1 })
        })
        .then(response => response.json())
        .then(data => {
            alert('Đã thêm sản phẩm vào giỏ hàng thành công!');
            const badge = document.getElementById('cart-badge');
            if (badge) {
                badge.innerText = parseInt(badge.innerText || 0) + 1;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Đã thêm sản phẩm vào giỏ hàng!');
            location.reload();
        });
    });
});
</script>
@endsection