@extends('layouts.app')

@php
    $orderCode = $order->order_number ?? $order->code ?? ('ORD-' . str_pad($order->id, 5, '0', STR_PAD_LEFT));
@endphp

@section('title', 'Chi Tiết Đơn Hàng ' . $orderCode)

@section('content')
<div class="container py-4">

    <!-- Thông báo Alert -->
    @if (session('success') || session('status'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') ?? session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary mb-0">
            <i class="fas fa-list-alt me-2"></i>Chi Tiết Đơn Hàng <span class="text-dark">{{ $orderCode }}</span>
        </h3>
        <div>
            <a href="{{ route('storefront') }}" class="btn btn-outline-primary btn-sm rounded-2 me-2">
                <i class="fas fa-store me-1"></i> Quay về cửa hàng
            </a>
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm rounded-2">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Cột trái: Thông tin đặt hàng & Trạng thái -->
        <div class="col-lg-4 col-md-5">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-dark text-white fw-bold py-3">
                    <i class="fas fa-user-alt me-2"></i>Thông Tin Đặt Hàng
                </div>
                <div class="card-body small">
                    <p class="mb-2">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Mã đơn hàng:</strong>
                        <span class="fw-bold text-primary">{{ $orderCode }}</span>
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Tên khách hàng:</strong>
                        <span class="fw-semibold">{{ $order->name ?? $order->user?->name ?? 'N/A' }}</span>
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Email:</strong>
                        <span>{{ $order->email ?? $order->user?->email ?? 'N/A' }}</span>
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Số điện thoại:</strong>
                        <span>{{ $order->phone ?? 'N/A' }}</span>
                    </p>
                    <p class="mb-0">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Địa chỉ nhận hàng:</strong>
                        <span>{{ $order->address ?? $order->shipping_address ?? 'N/A' }}</span>
                    </p>
                </div>
            </div>

            @php
                $status = strtolower($order->status ?? 'pending');
                
                $badgeClass = match($status) {
                    'completed', 'paid' => 'bg-success',
                    'shipping' => 'bg-primary',
                    'processing' => 'bg-info text-dark',
                    'cancelled', 'failed' => 'bg-danger',
                    default => 'bg-warning text-dark',
                };

                $statusLabel = match($status) {
                    'completed' => 'Hoàn thành',
                    'shipping' => 'Đang giao hàng',
                    'processing' => 'Đang xử lý',
                    'paid' => 'Đã thanh toán',
                    'cancelled' => 'Đã hủy',
                    'failed' => 'Thất bại',
                    'pending_payment' => 'Chờ thanh toán',
                    'pending' => 'Đang xử lý',
                    default => strtoupper($status),
                };

                $statusIcon = match($status) {
                    'completed' => 'fas fa-check-circle',
                    'shipping' => 'fas fa-truck',
                    'processing' => 'fas fa-sync-alt',
                    'cancelled', 'failed' => 'fas fa-times-circle',
                    default => 'fas fa-clock',
                };
            @endphp

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-dark text-white fw-bold py-3">
                    <i class="fas fa-info-circle me-2"></i>Trạng Thái Đơn Hàng
                </div>
                <div class="card-body small">
                    <div class="mb-3 d-flex align-items-center">
                        <strong class="text-muted me-2" style="width: 110px;">Trạng thái:</strong>
                        <span class="badge {{ $badgeClass }} px-2 py-1">
                            <i class="{{ $statusIcon }} me-1"></i>
                            {{ mb_strtoupper($statusLabel) }}
                        </span>
                    </div>

                    <div class="mb-3 d-flex align-items-center">
                        <strong class="text-muted me-2" style="width: 110px;">Thanh toán:</strong>
                        @if($order->payment_status === 'paid' || $order->status === 'paid' || $order->status === 'completed')
                            <span class="badge bg-success px-2 py-1">ĐÃ THANH TOÁN</span>
                        @else
                            <span class="badge bg-warning text-dark px-2 py-1">CHƯA THANH TOÁN</span>
                        @endif
                    </div>

                    <p class="mb-2">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Phương thức:</strong>
                        <span class="fw-bold">
                            @if(strtolower($order->payment_method ?? '') === 'momo' || $order->momo_transaction_id)
                                MOMO / CHUYỂN KHOẢN
                            @else
                                COD (THANH TOÁN KHI NHẬN HÀNG)
                            @endif
                        </span>
                    </p>

                    @if($order->momo_transaction_id || $order->transaction_id)
                        <p class="mb-2">
                            <strong class="text-muted d-inline-block" style="width: 110px;">Mã giao dịch:</strong>
                            <span class="badge bg-light text-dark border">{{ $order->momo_transaction_id ?? $order->transaction_id }}</span>
                        </p>
                    @endif

                    @if($order->ghn_order_code)
                        <p class="mb-2">
                            <strong class="text-muted d-inline-block" style="width: 110px;">Mã GHN:</strong>
                            <span class="badge bg-info text-dark border">{{ $order->ghn_order_code }}</span>
                        </p>
                    @endif

                    <p class="mb-0">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Thời gian tạo:</strong>
                        <span>{{ $order->created_at ? $order->created_at->format('H:i:s d/m/Y') : 'N/A' }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Cột phải: Sản phẩm / Dịch vụ -->
        <div class="col-lg-8 col-md-7">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-dark text-white fw-bold py-3">
                    <i class="fas fa-box me-2"></i>Sản Phẩm / Dịch Vụ Trong Đơn Hàng
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-3">Sản phẩm / Dịch vụ</th>
                                <th class="text-center">Đơn giá</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end pe-3">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @php 
                                $items = ($order->orderItems && $order->orderItems->isNotEmpty()) 
                                    ? $order->orderItems 
                                    : ($order->items ?? collect());
                                $subtotal = 0; 
                            @endphp

                            @forelse($items as $item)
                                @php 
                                    $pName = $item->display_name 
                                        ?? $item->service?->title 
                                        ?? $item->service?->name 
                                        ?? $item->hood?->name 
                                        ?? $item->product?->name 
                                        ?? $item->service_name 
                                        ?? $item->product_name 
                                        ?? $item->name 
                                        ?? $item->title 
                                        ?? 'Sản phẩm / Dịch vụ';

                                    $itemPrice = $item->price ?? 0;
                                    $itemQty = $item->quantity ?? $item->qty ?? 1;
                                    $itemTotal = $itemPrice * $itemQty;
                                    $subtotal += $itemTotal;
                                    
                                    $modelColor = $item->color ?? $item->model_name ?? $item->model ?? $item->options ?? null;
                                @endphp
                                <tr>
                                    <td class="ps-3 py-3">
                                        <div class="fw-bold text-dark fs-6">{{ $pName }}</div>
                                        @if($modelColor)
                                            <div class="text-muted small mt-1">
                                                <i class="fas fa-tag me-1 text-primary"></i>Mẫu / Màu: <span class="fw-semibold text-primary">{{ $modelColor }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center fw-semibold">{{ number_format($itemPrice) }}đ</td>
                                    <td class="text-center fw-bold text-dark">x{{ $itemQty }}</td>
                                    <td class="text-end pe-3 fw-bold text-primary fs-6">
                                        {{ number_format($itemTotal) }}đ
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block text-secondary"></i>
                                        Không có dữ liệu sản phẩm/dịch vụ cho đơn hàng này.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-top">
                            @php 
                                $discountAmount = $order->discount_amount ?? 0;
                                $couponCode     = $order->coupon_code ?? null;
                                $shippingFee    = $order->ghn_total_fee ?? max(0, ($order->total_price ?? $subtotal) - ($subtotal - $discountAmount));
                                $grandTotal     = $order->total_price ?? ($subtotal - $discountAmount + $shippingFee);
                            @endphp
                            <!-- Tạm tính tiền hàng -->
                            <tr>
                                <td colspan="3" class="text-end text-muted">Tiền hàng:</td>
                                <td class="text-end pe-3 fw-bold">{{ number_format($subtotal) }}đ</td>
                            </tr>

                            <!-- HIỂN THỊ MÃ GIẢM GIÁ VÀ SỐ TIỀN GIẢM -->
                            @if($discountAmount > 0 || !empty($couponCode))
                                <tr class="text-success">
                                    <td colspan="3" class="text-end fw-semibold">
                                        <i class="fas fa-ticket-alt me-1"></i>Mã giảm giá 
                                        @if(!empty($couponCode))
                                            (<strong class="text-uppercase">{{ $couponCode }}</strong>):
                                        @else
                                            :
                                        @endif
                                    </td>
                                    <td class="text-end pe-3 fw-bold">
                                        -{{ number_format($discountAmount) }}đ
                                    </td>
                                </tr>
                            @endif

                            <!-- Phí vận chuyển -->
                            <tr>
                                <td colspan="3" class="text-end text-muted">Phí vận chuyển (Shipping):</td>
                                <td class="text-end pe-3 text-primary fw-bold">+{{ number_format($shippingFee) }}đ</td>
                            </tr>

                            <!-- Tổng tiền thanh toán -->
                            <tr class="table-light">
                                <td colspan="3" class="text-end fw-bold h6 mb-0">TỔNG TIỀN THANH TOÁN:</td>
                                <td class="text-end pe-3 fw-bold h5 text-danger mb-0">
                                    {{ number_format($grandTotal) }}đ
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Nút bấm quay về cửa hàng -->
            <div class="d-flex justify-content-center gap-3 my-3">
                <a href="{{ route('storefront') }}" class="btn btn-primary btn-lg px-4 rounded-pill shadow-sm">
                    <i class="fas fa-shopping-bag me-2"></i>Tiếp Tục Mua Sắm
                </a>
                @if(Route::has('orders.index'))
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-dark btn-lg px-4 rounded-pill shadow-sm">
                        <i class="fas fa-receipt me-2"></i>Xem Tất Cả Đơn Hàng
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection