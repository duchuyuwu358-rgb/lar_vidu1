@extends('layouts.app')

@section('title', 'Thanh Toán Đơn Hàng')

@section('content')
<div class="row mt-4">
    <!-- Cột trái: Thông tin nhận hàng và Hình thức thanh toán -->
    <div class="col-md-7 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h4 class="mb-0 fw-bold"><i class="fas fa-truck text-primary me-2"></i> Thông tin giao hàng</h4>
            </div>
            <div class="card-body p-4">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ Route::has('cart.processCheckout') ? route('cart.processCheckout') : (Route::has('cart.checkout.process') ? route('cart.checkout.process') : url('/cart/checkout')) }}" method="POST" id="checkout-form">
                    @csrf
                    
                    <!-- Input ẩn lưu Phí Vận Chuyển -->
                    <input type="hidden" name="shipping_fee" id="shipping_fee_input" value="0">

                    <!-- Input Họ và tên -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên người nhận <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               name="name" 
                               maxlength="50"
                               placeholder="Nhập đầy đủ họ và tên" 
                               required 
                               value="{{ old('name', auth()->user()->name ?? '') }}">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Input Số điện thoại (Chỉ cho phép gõ số và +, tối đa 11 chữ số) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" 
                               class="form-control @error('phone') is-invalid @enderror" 
                               name="phone" 
                               id="phone"
                               placeholder="Nhập số điện thoại (10 - 11 số)" 
                               maxlength="11"
                               oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                               required 
                               value="{{ old('phone', auth()->user()->phone ?? '') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 3 Dropdown chọn địa chỉ GHN -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                            <select id="province_select" name="province_id" class="form-select @error('province_id') is-invalid @enderror" required>
                                <option value="">-- Chọn Tỉnh/Thành --</option>
                            </select>
                            @error('province_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Quận / Huyện <span class="text-danger">*</span></label>
                            <select id="district_select" name="to_district_id" class="form-select @error('to_district_id') is-invalid @enderror" disabled required>
                                <option value="">-- Chọn Quận/Huyện --</option>
                            </select>
                            @error('to_district_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Phường / Xã <span class="text-danger">*</span></label>
                            <select id="ward_select" name="to_ward_code" class="form-select @error('to_ward_code') is-invalid @enderror" disabled required>
                                <option value="">-- Chọn Phường/Xã --</option>
                            </select>
                            @error('to_ward_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Input Địa chỉ giao hàng chi tiết (Tối đa 255 ký tự) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Địa chỉ giao hàng chi tiết (Số nhà, tên đường...) <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  name="address" 
                                  id="address"
                                  rows="2" 
                                  maxlength="255"
                                  placeholder="Ví dụ: Số 123 đường Lê Lợi (Tối đa 255 ký tự)" 
                                  required>{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <h5 class="fw-bold mb-3"><i class="fas fa-wallet text-primary me-2"></i> Hình thức thanh toán</h5>
                    
                    <!-- Radio Thanh toán COD -->
                    <div class="card p-3 mb-2 border">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="payment_cod" value="cod" {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold cursor-pointer" for="payment_cod">
                                <i class="fas fa-money-bill-wave text-success me-2"></i> Thanh toán trực tiếp (COD khi nhận hàng)
                            </label>
                        </div>
                    </div>

                    <!-- Radio Thanh toán MoMo -->
                    <div class="card p-3 mb-4 border">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="payment_momo" value="momo" {{ old('payment_method') === 'momo' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold cursor-pointer" for="payment_momo">
                                <i class="fas fa-qrcode text-danger me-2"></i> Thanh toán qua Ví Điện Tử MoMo
                            </label>
                        </div>
                    </div>
                    @error('payment_method')
                        <div class="text-danger mb-3 small">{{ $message }}</div>
                    @enderror

                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5 shadow-sm">
                        <i class="fas fa-check-circle me-1"></i> XÁC NHẬN ĐẶT HÀNG
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Cột phải: Tóm tắt các sản phẩm đã chọn -->
    <div class="col-md-5">
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-header bg-light border-bottom py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-receipt me-2"></i> Đơn hàng đã chọn</h5>
            </div>
            <div class="card-body p-4">
                <ul class="list-group mb-3">
                    @if(isset($checkoutCart) && (is_array($checkoutCart) || is_object($checkoutCart)) && count($checkoutCart) > 0)
                        @foreach ($checkoutCart as $itemKey =>$details)
                            @php
                                $price = is_array($details) ? ($details['price'] ?? 0) : ($details->price ?? 0);
                                $qty = is_array($details) ? ($details['quantity'] ?? 1) : ($details->quantity ?? 1);
                                $name = is_array($details) ? ($details['name'] ?? 'Sản phẩm') : ($details->name ?? 'Sản phẩm');
                                $type = is_array($details) ? ($details['type'] ?? '') : ($details->type ?? '');
                                $itemSubtotal = $price * $qty;
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center lh-sm py-3">
                                <div>
                                    <h6 class="my-0 fw-bold text-dark">{{ $name }}</h6>
                                    <small class="text-muted">SL: {{ $qty }} x {{ number_format($price, 0, ',', '.') }} đ</small>
                                    @if($type === 'service')
                                        <span class="badge bg-info text-dark ms-1">Dịch vụ</span>
                                    @endif
                                </div>
                                <span class="fw-bold text-primary">{{ number_format($itemSubtotal, 0, ',', '.') }} đ</span>
                            </li>
                        @endforeach
                    @else
                        <li class="list-group-item text-center text-muted py-4">Chưa chọn sản phẩm nào</li>
                    @endif
                </ul>

                <!-- Khối Bổ Sung Chi Tiết Phí GHN & Tổng Tiền -->
                <div class="border-top pt-3 mt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tạm tính (Tiền hàng):</span>
                        <strong id="subtotal_text" class="text-dark">{{ number_format($totalAmount ?? 0, 0, ',', '.') }} đ</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Phí vận chuyển (GHN):</span>
                        <strong class="text-primary" id="shipping_fee_text">0 đ</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center fw-bold fs-5">
                        <span>Tổng tiền thanh toán:</span>
                        <span class="text-danger fs-3" id="final_total_text">{{ number_format($totalAmount ?? 0, 0, ',', '.') }} đ</span>
                    </div>
                </div>

                <input type="hidden" id="total_price_input" value="{{ $totalAmount ?? 0 }}">
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Xử Lý Tải Tỉnh/Quận/Xã & Tính Phí GHN -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const provinceSelect = document.getElementById('province_select');
    const districtSelect = document.getElementById('district_select');
    const wardSelect = document.getElementById('ward_select');
    const shippingFeeText = document.getElementById('shipping_fee_text');
    const shippingFeeInput = document.getElementById('shipping_fee_input');
    const finalTotalText = document.getElementById('final_total_text');
    const totalPriceInput = document.getElementById('total_price_input');

    const subtotal = parseInt(totalPriceInput ? totalPriceInput.value : 0) || 0;

    const provincesUrl = "{{ Route::has('locations.provinces') ? route('locations.provinces') : url('/cart/api/provinces') }}";
    const districtsBaseUrl = "{{ Route::has('locations.districts') ? route('locations.districts', ['provinceId' => '___ID___']) : url('/cart/api/districts/___ID___') }}";
    const wardsBaseUrl = "{{ Route::has('locations.wards') ? route('locations.wards', ['districtId' => '___ID___']) : url('/cart/api/wards/___ID___') }}";
    const feeUrl = "{{ Route::has('locations.fee') ? route('locations.fee') : url('/cart/api/shipping-fee') }}";

    // 1. Load Tỉnh/Thành từ GHN
    fetch(provincesUrl)
        .then(res => res.json())
        .then(res => {
            const list = res.data || res;
            if (Array.isArray(list)) {
                let options = '<option value="">-- Chọn Tỉnh/Thành --</option>';
                list.forEach(p => {
                    options += `<option value="${p.ProvinceID}">${p.ProvinceName}</option>`;
                });
                provinceSelect.innerHTML = options;
            }
        })
        .catch(err => console.error("Lỗi tải tỉnh/thành:", err));

    // 2. Chọn Tỉnh -> Load Quận/Huyện
    provinceSelect.addEventListener('change', function () {
        districtSelect.innerHTML = '<option value="">-- Đang tải... --</option>';
        districtSelect.disabled = true;
        wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
        wardSelect.disabled = true;
        updateTotals(0);
        if (!this.value) return;

        const url = districtsBaseUrl.replace('___ID___', this.value);
        fetch(url)
            .then(res => res.json())
            .then(res => {
                const list = res.data || res;
                if (Array.isArray(list)) {
                    let options = '<option value="">-- Chọn Quận/Huyện --</option>';
                    list.forEach(d => {
                        options += `<option value="${d.DistrictID}">${d.DistrictName}</option>`;
                    });
                    districtSelect.innerHTML = options;
                    districtSelect.disabled = false;
                }
            })
            .catch(err => console.error("Lỗi tải quận/huyện:", err));
    });

    // 3. Chọn Quận/Huyện -> Load Phường/Xã
    districtSelect.addEventListener('change', function () {
        wardSelect.innerHTML = '<option value="">-- Đang tải... --</option>';
        wardSelect.disabled = true;
        updateTotals(0);
        if (!this.value) return;

        const url = wardsBaseUrl.replace('___ID___', this.value);
        fetch(url)
            .then(res => res.json())
            .then(res => {
                const list = res.data || res;
                if (Array.isArray(list)) {
                    let options = '<option value="">-- Chọn Phường/Xã --</option>';
                    list.forEach(w => {
                        options += `<option value="${w.WardCode}">${w.WardName}</option>`;
                    });
                    wardSelect.innerHTML = options;
                    wardSelect.disabled = false;
                }
            })
            .catch(err => console.error("Lỗi tải phường/xã:", err));
    });

    // 4. Chọn Phường/Xã -> Gọi GHN tính phí vận chuyển
    wardSelect.addEventListener('change', function () {
        if (!this.value || !districtSelect.value) return;
        shippingFeeText.innerText = 'Đang tính cước...';

        fetch(feeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                to_district_id: districtSelect.value,
                to_ward_code: this.value
            })
        })
        .then(res => res.json())
        .then(res => {
            let fee = 0;
            if (res.code === 200 && res.data && res.data.total !== undefined) {
                fee = parseInt(res.data.total) || 0;
            } else if (res.total !== undefined) {
                fee = parseInt(res.total) || 0;
            }
            updateTotals(fee);
        })
        .catch(err => {
            console.error("Lỗi tính phí GHN:", err);
            shippingFeeText.innerText = '0 đ';
            updateTotals(0);
        });
    });

    function updateTotals(fee) {
        shippingFeeText.innerText = new Intl.NumberFormat('vi-VN').format(fee) + ' đ';
        if (shippingFeeInput) {
            shippingFeeInput.value = fee;
        }
        const finalAmount = subtotal + fee;
        finalTotalText.innerText = new Intl.NumberFormat('vi-VN').format(finalAmount) + ' đ';
    }
});
</script>
@endsection