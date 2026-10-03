@extends('layouts.app')

@section('title', 'Lịch Sử Đơn Hàng')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h2 class="h4 mb-4 fw-bold text-primary">
            <i class="fas fa-receipt me-2"></i>Lịch Sử Đơn Hàng Của Bạn
        </h2>

        @if($orders->isEmpty())
            <div class="alert alert-info mb-0">Bạn chưa có đơn hàng nào.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Mã đơn</th>
                            <th>Mã giao dịch</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td class="fw-bold">
                                    <a href="{{ route('orders.show', $order->id) }}" class="text-decoration-none fw-bold text-primary">
                                        #{{ $order->id }}
                                    </a>
                                </td>
                                <td>{{ $order->momo_transaction_id ?? 'N/A' }}</td>
                                <td class="text-danger fw-bold">{{ number_format($order->total_price ?? $order->total_amount ?? 0) }}đ</td>
                                <td>
                                    <span class="badge {{ $order->status_badge }} px-2 py-1">
                                        <i class="{{ $order->status_icon }} me-1"></i>
                                        {{ $order->status_label }}
                                    </span>
                                </td>
                                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
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
            <div class="mt-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection