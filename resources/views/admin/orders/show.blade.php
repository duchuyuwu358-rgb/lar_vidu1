@extends('layouts.app')

@section('title', 'Chi Tiết Đơn Hàng #' . $order->id)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold text-dark mb-0">
            <i class="fas fa-receipt text-primary me-2"></i>Chi Tiết Đơn Hàng #{{ $order->id }}
        </h2>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Quay lại
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $pStatus = mb_strtolower($order->payment_status ?? '', 'UTF-8');
        $oStatus = mb_strtolower($order->status ?? '', 'UTF-8');
        
        $currentStatus = $order->status ?? $order->payment_status ?? 'pending';
    @endphp

    <div class="row g-4">
        <!-- THÔNG TIN KHÁCH HÀNG & THANH TOÁN -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white fw-bold py-3">
                    <i class="fas fa-user me-2"></i>Thông Tin Đặt Hàng
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Tên khách hàng:</strong> {{ $order->user->name ?? $order->name ?? 'Khách vãng lai' }}</p>
                    <p class="mb-2"><strong>Email:</strong> {{ $order->user->email ?? $order->email ?? 'N/A' }}</p>
                    <p class="mb-2"><strong>Số điện thoại:</strong> {{ $order->phone ?? $order->user->phone ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>Địa chỉ nhận hàng:</strong> {{ $order->address ?? $order->shipping_address ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-bold py-3">
                    <i class="fas fa-info-circle me-2"></i>Trạng Thái Đơn Hàng
                </div>
                <div class="card-body">
                    <!-- Form Cập Nhật Trạng Thái Thanh Toán -->
                    <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="mb-3">
                        @csrf
                        @method('PUT')

                        <label class="form-label fw-bold text-secondary">Trạng thái thanh toán:</label>
                        <div class="input-group mb-2">
                            <select name="status" class="form-select fw-bold border-primary">
                                <option value="pending" {{ in_array($currentStatus, ['pending', 'chờ thanh toán']) ? 'selected' : '' }}>
                                    🟡 CHỜ THANH TOÁN
                                </option>
                                <option value="paid" {{ in_array($currentStatus, ['paid', 'completed', 'đã thanh toán']) ? 'selected' : '' }}>
                                    🟢 ĐÃ THANH TOÁN
                                </option>
                                <option value="failed" {{ in_array($currentStatus, ['failed', 'thất bại']) ? 'selected' : '' }}>
                                    🔴 THANH TOÁN THẤT BẠI
                                </option>
                                <option value="cancelled" {{ in_array($currentStatus, ['cancelled', 'đã hủy', 'cancel']) ? 'selected' : '' }}>
                                    ⚪ ĐÃ HỦY ĐƠN
                                </option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                            <i class="fas fa-save me-1"></i> Cập nhật trạng thái
                        </button>
                    </form>

                    <hr class="my-3">

                    <p class="mb-2"><strong>Phương thức:</strong> {{ strtoupper($order->payment_method ?? 'MoMo / Chuyển khoản') }}</p>
                    <p class="mb-0"><strong>Thời gian tạo:</strong> {{ $order->created_at ? $order->created_at->format('H:i:s d/m/Y') : 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- DANH SÁCH MÓN HÀNG -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-bold py-3">
                    <i class="fas fa-boxes me-2"></i>Sản Phẩm Trong Đơn Hàng
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-center">Đơn giá</th>
                                    <th class="text-center">Số lượng</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $items = $order->orderItems ?? $order->items ?? [];
                                @endphp

                                @forelse($items as $item)
                                    @php
                                        $productName = $item->hood->name ?? $item->product->name ?? $item->product_name ?? $item->name ?? ('Máy hút mùi #' . ($item->hood_id ?? $item->product_id ?? ''));
                                        $price = $item->price ?? $item->unit_price ?? 0;
                                        $qty = $item->quantity ?? $item->qty ?? 1;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $productName }}</div>
                                            @if(!empty($item->color))
                                                <small class="text-muted">Màu sắc: <strong>{{ $item->color }}</strong></small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($price, 0, ',', '.') }}đ</td>
                                        <td class="text-center fw-bold">x{{ $qty }}</td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($price * $qty, 0, ',', '.') }}đ</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Chưa có chi tiết danh sách sản phẩm.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                @php
                                    $totalAmount = $order->total_price ?? $order->total_amount ?? $order->total ?? 0;
                                    $shippingFee = $order->shipping_fee ?? $order->ghn_total_fee ?? 0;
                                    $subtotal = $totalAmount - $shippingFee;
                                @endphp
                                <tr>
                                    <td colspan="3" class="text-end text-muted fw-semibold">Tiền hàng:</td>
                                    <td class="text-end fw-bold text-dark">
                                        {{ number_format($subtotal > 0 ? $subtotal : $totalAmount, 0, ',', '.') }}đ
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end text-muted fw-semibold">Phí vận chuyển (Shipping):</td>
                                    <td class="text-end fw-bold text-primary">
                                        +{{ number_format($shippingFee, 0, ',', '.') }}đ
                                    </td>
                                </tr>
                                <tr class="border-top">
                                    <td colspan="3" class="text-end fw-bold fs-5 text-uppercase">TỔNG TIỀN THANH TOÁN:</td>
                                    <td class="text-end fw-bold fs-5 text-danger">
                                        {{ number_format($totalAmount, 0, ',', '.') }}đ
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection