@extends('layouts.app')

@section('title', 'Giỏ Hàng - Chọn Sản Phẩm')

@section('content')
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold"><i class="fas fa-shopping-cart text-primary me-2"></i> Giỏ hàng của bạn</h4>
        <span class="badge bg-primary fs-6" id="total-items-badge">0 Sản phẩm đã chọn</span>
    </div>

    <div class="card-body p-4">
        @if(session('error'))
            <div class="alert alert-danger mb-4">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success mb-4">{{ session('success') }}</div>
        @endif

        @if(count($groupedCart) > 0)
            <form action="{{ route('cart.checkout') }}" method="GET" id="cart-form">
                
                <div class="d-flex align-items-center mb-3 p-3 bg-light rounded border">
                    <input type="checkbox" id="select-all-global" class="form-check-input me-3 fs-5" style="width: 22px; height: 22px;" checked>
                    <label for="select-all-global" class="fw-bold mb-0 text-dark cursor-pointer fs-6">Chọn tất cả sản phẩm trong giỏ</label>
                </div>

                @foreach($groupedCart as $categoryName => $items)
                    <div class="card mb-4 border category-group">
                        <div class="card-header bg-light d-flex align-items-center py-3">
                            <input type="checkbox" class="form-check-input me-3 category-checkbox fs-5" style="width: 20px; height: 20px;" checked>
                            <h5 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-layer-group me-2"></i>{{ $categoryName }}
                            </h5>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">Chọn</th>
                                            <th>Sản Phẩm & Mẫu</th>
                                            <th>Đơn Giá</th>
                                            <th style="width: 140px;">Số Lượng</th>
                                            <th>Thành Tiền</th>
                                            <th style="width: 60px;" class="text-center">Xóa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $key => $item)
                                            @php 
                                                $subtotal = $item['price'] * $item['quantity'];
                                            @endphp
                                            <tr class="cart-item-row" data-key="{{ $key }}" data-price="{{ $item['price'] }}">
                                                <td class="text-center">
                                                    <input type="checkbox" name="selected_items[]" value="{{ $key }}" class="form-check-input item-checkbox" style="width: 18px; height: 18px;" checked>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if(!empty($item['image']))
                                                            <img src="{{ \Illuminate\Support\Str::startsWith($item['image'], ['http://', 'https://']) ? $item['image'] : asset('storage/' . $item['image']) }}" 
                                                                 alt="{{ $item['name'] }}" 
                                                                 class="rounded me-3 border" 
                                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                                        @else
                                                            <div class="bg-light rounded d-flex align-items-center justify-content-center me-3 border" style="width: 50px; height: 50px;">
                                                                <i class="fas fa-fan text-primary fs-4"></i>
                                                            </div>
                                                        @endif

                                                        <div>
                                                            <span class="fw-bold d-block text-dark">{{ $item['name'] }}</span>
                                                            <small class="text-muted d-block"><i class="fas fa-barcode me-1"></i>Mẫu: <strong>{{ $item['model'] }}</strong></small>
                                                            @if(!empty($item['color']))
                                                                <small class="badge bg-outline-secondary text-dark border"><i class="fas fa-palette me-1"></i>Màu: {{ $item['color'] }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="fw-semibold">{{ number_format($item['price'], 0, ',', '.') }} đ</td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <button class="btn btn-outline-secondary btn-decrease" type="button">-</button>
                                                        <input type="number" name="quantities[{{ $key }}]" class="form-control text-center item-quantity" value="{{ $item['quantity'] }}" min="1">
                                                        <button class="btn btn-outline-secondary btn-increase" type="button">+</button>
                                                    </div>
                                                </td>
                                                <td class="fw-bold text-danger item-subtotal">
                                                    {{ number_format($subtotal, 0, ',', '.') }} đ
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Xóa khỏi giỏ" onclick="removeItem('{{ $key }}')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="card p-4 border bg-light mt-4">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <a href="{{ route('storefront') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Tiếp tục mua hàng
                            </a>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="mb-2">
                                <span class="fw-bold fs-5">Tổng tiền sản phẩm đã chọn: </span>
                                <span class="text-danger fw-bold fs-3 ms-2" id="selected-total">0 đ</span>
                            </div>
                            <button type="submit" class="btn btn-success px-4 py-2 fw-bold fs-6">
                                Tiến Hành Thanh Toán <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @else
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                <h5 class="fw-bold">Giỏ hàng của bạn đang trống!</h5>
                <p class="text-muted">Hãy chọn sản phẩm từ cửa hàng và bấm "Thêm vào giỏ".</p>
                <a href="{{ route('storefront') }}" class="btn btn-primary mt-2">
                    <i class="fas fa-store me-1"></i> Đến Cửa Hàng
                </a>
            </div>
        @endif
    </div>
</div>

<form id="delete-item-form" action="" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

<script>
function removeItem(key) {
    if (confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
        let form = document.getElementById('delete-item-form');
        form.action = "{{ url('/cart/remove') }}/" + key;
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    function calculateTotal() {
        let total = 0;
        let count = 0;

        document.querySelectorAll('.category-group').forEach(group => {
            let categoryAllChecked = true;
            const itemCbs = group.querySelectorAll('.item-checkbox');

            itemCbs.forEach(cb => {
                const row = cb.closest('.cart-item-row');
                const price = parseFloat(row.getAttribute('data-price'));
                const qtyInput = row.querySelector('.item-quantity');
                const qty = parseInt(qtyInput.value) || 1;
                const subtotal = price * qty;

                row.querySelector('.item-subtotal').innerText = subtotal.toLocaleString('vi-VN') + ' đ';

                if (cb.checked) {
                    total += subtotal;
                    count++;
                } else {
                    categoryAllChecked = false;
                }
            });

            const catCb = group.querySelector('.category-checkbox');
            if (catCb) catCb.checked = categoryAllChecked && itemCbs.length > 0;
        });

        document.getElementById('selected-total').innerText = total.toLocaleString('vi-VN') + ' đ';
        document.getElementById('total-items-badge').innerText = count + ' Sản phẩm đã chọn';
    }

    document.getElementById('select-all-global')?.addEventListener('change', function() {
        document.querySelectorAll('.form-check-input').forEach(cb => cb.checked = this.checked);
        calculateTotal();
    });

    document.querySelectorAll('.category-checkbox').forEach(catCb => {
        catCb.addEventListener('change', function() {
            this.closest('.category-group').querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
            calculateTotal();
        });
    });

    document.querySelectorAll('.item-checkbox').forEach(cb => cb.addEventListener('change', calculateTotal));

    document.querySelectorAll('.cart-item-row').forEach(row => {
        const qtyInput = row.querySelector('.item-quantity');
        
        row.querySelector('.btn-decrease')?.addEventListener('click', () => {
            if (parseInt(qtyInput.value) > 1) {
                qtyInput.value = parseInt(qtyInput.value) - 1;
                calculateTotal();
            }
        });

        row.querySelector('.btn-increase')?.addEventListener('click', () => {
            qtyInput.value = parseInt(qtyInput.value) + 1;
            calculateTotal();
        });

        qtyInput?.addEventListener('change', calculateTotal);
    });

    calculateTotal();
});
</script>
@endsection