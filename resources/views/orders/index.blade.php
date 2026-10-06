@extends('layouts.app') {{-- ⚠️ Nếu trang /cart dùng layout khác (vd: layouts.master), hãy sửa lại tên ở đây --}}

@section('title', 'Lịch Sử Đơn Hàng')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h2 class="h4 mb-4 fw-bold text-primary">
                <i class="fas fa-receipt me-2"></i>Lịch Sử Đơn Hàng Của Bạn
            </h2>

            @if($orders->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>Bạn chưa có đơn hàng nào.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">STT</th>
                                <th>Mã đơn</th>
                                <th>Mã giao dịch</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $index => $order)
                                <tr>
                                    <!-- 1. SỐ THỨ TỰ (TỰ ĐỘNG THEO PHÂN TRANG) -->
                                    <td class="text-center fw-bold text-secondary">
                                        {{ method_exists($orders, 'firstItem') ? $orders->firstItem() + $index : $index + 1 }}
                                    </td>

                                    <!-- 2. MÃ ĐƠN HÀNG -->
                                    <td>
                                        <a href="{{ route('orders.show', $order->id) }}" class="text-decoration-none fw-bold text-primary">
                                            {{ $order->order_code ?? 'ORD-' . str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                        </a>
                                    </td>

                                    <!-- 3. MÃ GIAO DỊCH THỰC TẾ -->
                                    <td>
                                        @if($order->payment_method === 'cod')
                                            <span class="badge bg-secondary">COD</span>
                                        @else
                                            @php
                                                // 1. Tìm trên bảng orders
                                                $transId = $order->momo_transaction_id 
                                                    ?? $order->transaction_id 
                                                    ?? $order->momo_trans_id 
                                                    ?? $order->vnp_transaction_no;

                                                // 2. Nếu không thấy, tìm trong bảng payment_transactions
                                                if (!$transId && isset($order->paymentTransactions) && $order->paymentTransactions->isNotEmpty()) {
                                                    $lastTrans = $order->paymentTransactions->last();
                                                    $transId = $lastTrans->transaction_id 
                                                        ?? $lastTrans->momo_trans_id 
                                                        ?? $lastTrans->trans_id 
                                                        ?? null;
                                                }
                                            @endphp

                                            @if($transId)
                                                <span class="badge bg-light text-dark border font-monospace">{{ $transId }}</span>
                                            @elseif($order->payment_status === 'paid')
                                                <span class="badge bg-success">Đã thanh toán</span>
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        @endif
                                    </td>

                                    <!-- 4. TỔNG TIỀN -->
                                    <td class="text-danger fw-bold">
                                        {{ number_format($order->total_price ?? $order->total_amount ?? 0, 0, ',', '.') }}đ
                                    </td>

                                    <!-- 5. TRẠNG THÁI ĐƠN HÀNG -->
                                    <td>
                                        <span class="badge {{ $order->status_badge ?? 'bg-secondary' }} px-2 py-1">
                                            @if(!empty($order->status_icon))
                                                <i class="{{ $order->status_icon }} me-1"></i>
                                            @endif
                                            {{ $order->status_label ?? $order->status }}
                                        </span>
                                    </td>

                                    <!-- 6. NGÀY ĐẶT -->
                                    <td>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}</td>

                                    <!-- 7. THAO TÁC -->
                                    <td class="text-center">
                                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="fas fa-eye me-1"></i> Xem chi tiết
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(method_exists($orders, 'hasPages') && $orders->hasPages())
                    <div class="mt-3">
                        {{ $orders->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection