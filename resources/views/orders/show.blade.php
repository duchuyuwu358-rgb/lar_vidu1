@extends('layouts.app')

@section('title', 'Chi Tiết Đơn Hàng #' . $order->id)

@section('content')
<div class="container py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary mb-0">
            <i class="fas fa-list-alt me-2"></i>Chi Tiết Đơn Hàng #{{ $order->id }}
        </h3>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm rounded-2">
            <i class="fas fa-arrow-left me-1"></i> Quay lại
        </a>
    </div>

    <div class="row g-4">
        <!-- Cột trái: Thông tin giao hàng & Trạng thái -->
        <div class="col-lg-4 col-md-5">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-dark text-white fw-bold py-3">
                    <i class="fas fa-user-alt me-2"></i>Thông Tin Đặt Hàng
                </div>
                <div class="card-body small">
                    <p class="mb-2">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Tên khách hàng:</strong>
                        <span class="fw-semibold">{{ $order->name ?? $order->user->name ?? 'N/A' }}</span>
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Email:</strong>
                        <span>{{ $order->email ?? $order->user->email ?? 'N/A' }}</span>
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Số điện thoại:</strong>
                        <span>{{ $order->phone ?? 'N/A' }}</span>
                    </p>
                    <p class="mb-0">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Địa chỉ nhận hàng:</strong>
                        <span>{{ $order->address ?? 'N/A' }}</span>
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-dark text-white fw-bold py-3">
                    <i class="fas fa-info-circle me-2"></i>Trạng Thái Đơn Hàng
                </div>
                <div class="card-body small">
                    <div class="mb-3 d-flex align-items-center">
                        <strong class="text-muted me-2" style="width: 110px;">Trạng thái:</strong>
                        <span class="badge {{ $order->status_badge }} px-2 py-1">
                            <i class="{{ $order->status_icon }} me-1"></i>
                            {{ mb_strtoupper($order->status_label) }}
                        </span>
                    </div>

                    <div class="mb-3 d-flex align-items-center">
                        <strong class="text-muted me-2" style="width: 110px;">Thanh toán:</strong>
                        @if($order->payment_status === 'paid' || $order->status === 'paid')
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
                    <p class="mb-0">
                        <strong class="text-muted d-inline-block" style="width: 110px;">Thời gian tạo:</strong>
                        <span>{{ $order->created_at->format('H:i:s d/m/Y') }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Cột phải: Bảng sản phẩm -->
        <div class="col-lg-8 col-md-7">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-dark text-white fw-bold py-3">
                    <i class="fas fa-box me-2"></i>Sản Phẩm Trong Đơn Hàng
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-3">Sản phẩm</th>
                                <th class="text-center">Đơn giá</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end pe-3">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @php 
                                $items = $order->orderItems->isNotEmpty() ? $order->orderItems : $order->items;
                                $subtotal = 0; 
                            @endphp

                            @forelse($items as $item)
                                @php 
                                    $pName = $item->hood->name ?? $item->product->name ?? $item->product_name ?? 'Sản phẩm';
                                    $itemPrice = $item->price ?? 0;
                                    $itemQty = $item->quantity ?? 1;
                                    $itemTotal = $itemPrice * $itemQty;
                                    $subtotal += $itemTotal;
                                    
                                    $modelColor = $item->color ?? $item->model_name ?? $item->model ?? $item->options ?? null;
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">{{ $pName }}</div>
                                        @if($modelColor)
                                            <div class="text-muted small">
                                                <i class="fas fa-tag me-1"></i>Mẫu / Màu: <span class="fw-semibold text-primary">{{ $modelColor }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($itemPrice) }}đ</td>
                                    <td class="text-center fw-bold">x{{ $itemQty }}</td>
                                    <td class="text-end pe-3 fw-bold text-primary">
                                        {{ number_format($itemTotal) }}đ
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Không có dữ liệu sản phẩm cho đơn hàng này.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-top">
                            @php 
                                $grandTotal = $order->total_price ?? $order->total_amount ?? $subtotal;
                                $shippingFee = max(0, $grandTotal - $subtotal);
                            @endphp
                            <tr>
                                <td colspan="3" class="text-end text-muted">Tiền hàng:</td>
                                <td class="text-end pe-3 fw-bold">{{ number_format($subtotal) }}đ</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end text-muted">Phí vận chuyển (Shipping):</td>
                                <td class="text-end pe-3 text-primary fw-bold">+{{ number_format($shippingFee) }}đ</td>
                            </tr>
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
        </div>
    </div>
</div>
@endsection