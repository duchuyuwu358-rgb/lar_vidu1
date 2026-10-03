@extends('layouts.app')

@section('title', 'Admin Portal - Báo cáo doanh thu & Thống kê')

@section('content')
<style>
    .hover-bg:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease-in-out;
    }
</style>

<div class="container-fluid mt-4">

    <!-- HEADER & NÚT XUẤT EXCEL -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1 text-dark">Admin Portal</h2>
            <p class="text-muted mb-0">Quản lý toàn bộ cửa hàng, báo cáo doanh thu & xuất dữ liệu</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.portal.export', ['period' => $period]) }}" class="btn btn-success fw-bold shadow-sm">
                <i class="fas fa-file-excel me-1"></i> Xuất Báo Cáo Excel
            </a>
            <a href="{{ Route::has('admin.hoods.index') ? route('admin.hoods.index') : (Route::has('hoods.index') ? route('hoods.index') : url('/admin/hoods')) }}" class="btn btn-primary fw-bold shadow-sm">
                <i class="fas fa-box me-1"></i> Quản lý sản phẩm
            </a>
        </div>
    </div>

    <!-- CARDS THỐNG KÊ KINH DOANH -->
    <div class="row g-3 mb-4">
        <div class="col-md">
            <div class="card bg-primary text-white p-3 border-0 shadow-sm rounded-3">
                <small class="text-white-50">Tổng sản phẩm</small>
                <h3 class="fw-bold mb-0 mt-2">{{ number_format($totalProducts) }}</h3>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-info text-white p-3 border-0 shadow-sm rounded-3">
                <small class="text-white-50">Danh mục</small>
                <h3 class="fw-bold mb-0 mt-2">{{ number_format($totalCategories) }}</h3>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-success text-white p-3 border-0 shadow-sm rounded-3">
                <small class="text-white-50">Khách hàng</small>
                <h3 class="fw-bold mb-0 mt-2">{{ number_format($totalCustomers) }}</h3>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-warning text-dark p-3 border-0 shadow-sm rounded-3">
                <small class="text-dark-50">Số lượng đã bán</small>
                <h3 class="fw-bold mb-0 mt-2">{{ number_format($totalSold) }}</h3>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-danger text-white p-3 border-0 shadow-sm rounded-3">
                <small class="text-white-50">Tổng doanh thu</small>
                <h3 class="fw-bold mb-0 mt-2">{{ number_format($totalRevenue, 0, ',', '.') }}đ</h3>
            </div>
        </div>
    </div>

    <!-- BIỂU ĐỒ DOANH THU -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-chart-line text-primary me-2"></i>{{ $chartTitle }}
                </h5>

                <form method="GET" action="{{ route('admin.portal') }}" id="periodForm" class="d-flex align-items-center gap-2">
                    <span class="fw-semibold text-muted fs-7">Thời gian:</span>
                    <select name="period" class="form-select form-select-sm fw-bold border-primary" style="width: 170px;" onchange="document.getElementById('periodForm').submit()">
                        <option value="1day" {{ $period == '1day' ? 'selected' : '' }}>1 Ngày (Hôm nay)</option>
                        <option value="1week" {{ $period == '1week' ? 'selected' : '' }}>1 Tuần (7 ngày)</option>
                        <option value="1month" {{ $period == '1month' ? 'selected' : '' }}>1 Tháng (30 ngày)</option>
                        <option value="1year" {{ $period == '1year' ? 'selected' : '' }}>1 Năm (12 tháng)</option>
                        <option value="all" {{ $period == 'all' ? 'selected' : '' }}>Tất cả thời gian</option>
                    </select>
                </form>
            </div>

            <div style="height: 350px; position: relative;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- TOP BÁN CHẠY & TRẠNG THÁI ĐƠN HÀNG -->
    <div class="row g-4 mb-4">
        <!-- TOP 5 SẢN PHẨM -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-crown text-warning me-2"></i>Top 5 Sản Phẩm Bán Chạy</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Sản phẩm</th>
                                    <th class="text-center">Đã bán</th>
                                    <th class="text-end pe-3">Tổng thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $item)
                                    @php
                                        $pName = $item->hood->name ?? $item->product->name ?? ('Sản phẩm #' . ($item->hood_id ?? $item->product_id ?? $item->id ?? ''));
                                    @endphp
                                    <tr>
                                        <td class="ps-3 fw-semibold text-dark">
                                            {{ $pName }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill px-3">{{ $item->total_qty }}</span>
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-danger">
                                            {{ number_format($item->total_amount ?? 0, 0, ',', '.') }}đ
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Chưa có dữ liệu bán hàng.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TRẠNG THÁI ĐƠN HÀNG -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-pie-chart text-info me-2"></i>Trạng Thái Đơn Hàng</h5>
                    @if(Route::has('admin.orders.index'))
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-between align-items-center p-2 rounded hover-bg">
                        <span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>Đã thanh toán:</span>
                        <a href="{{ Route::has('admin.orders.index') ? route('admin.orders.index', ['status' => 'completed']) : '#' }}" class="text-decoration-none">
                            <span class="badge bg-success fs-6">{{ $paidOrdersCount }} đơn</span>
                        </a>
                    </div>
                    <div class="mb-3 d-flex justify-content-between align-items-center p-2 rounded hover-bg">
                        <span class="text-warning fw-bold"><i class="fas fa-clock me-1"></i>Chờ thanh toán:</span>
                        <a href="{{ Route::has('admin.orders.index') ? route('admin.orders.index', ['status' => 'pending']) : '#' }}" class="text-decoration-none">
                            <span class="badge bg-warning text-dark fs-6">{{ $pendingOrdersCount }} đơn</span>
                        </a>
                    </div>
                    <div class="mb-3 d-flex justify-content-between align-items-center p-2 rounded hover-bg">
                        <span class="text-danger fw-bold"><i class="fas fa-times-circle me-1"></i>Hủy / Thất bại:</span>
                        <a href="{{ Route::has('admin.orders.index') ? route('admin.orders.index', ['status' => 'cancelled']) : '#' }}" class="text-decoration-none">
                            <span class="badge bg-danger fs-6">{{ $failedOrdersCount }} đơn</span>
                        </a>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center fw-bold fs-6 px-2">
                        <span>Tổng cộng:</span>
                        <span class="text-primary">{{ $totalOrdersCount }} đơn hàng</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ĐƠN HÀNG MỚI NHẤT -->
    @if(isset($recentOrders) && count($recentOrders) > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-shopping-bag text-primary me-2"></i>Đơn Hàng Gần Đây</h5>
            @if(Route::has('admin.orders.index'))
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-link text-decoration-none fw-bold">Quản lý tất cả đơn hàng &rarr;</a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td class="ps-3 fw-bold text-primary">#{{ $order->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $order->user->name ?? $order->name ?? 'Khách vãng lai' }}</div>
                                <small class="text-muted">{{ $order->user->email ?? $order->email ?? 'N/A' }}</small>
                            </td>
                            <td>
                                @php
                                    $st = strtolower($order->status ?? '');
                                    $pst = strtolower($order->payment_status ?? '');
                                @endphp
                                @if(in_array($st, ['completed', 'paid', 'success']) || in_array($pst, ['paid', 'completed', 'success']) || str_contains($pst, 'đã'))
                                    <span class="badge bg-success-subtle text-success border border-success">Đã thanh toán</span>
                                @elseif(in_array($st, ['failed', 'cancelled', 'cancel']) || in_array($pst, ['failed', 'cancelled']) || str_contains($pst, 'hủy') || str_contains($pst, 'thất bại'))
                                    <span class="badge bg-danger-subtle text-danger border border-danger">Hủy / Thất bại</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning">Chờ thanh toán</span>
                                @endif
                            </td>
                            <td>{{ $order->created_at ? $order->created_at->format('H:i d/m/Y') : '-' }}</td>
                            <td class="text-end pe-3">
                                @if(Route::has('admin.orders.show'))
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary fw-bold" title="Xem chi tiết">
                                        <i class="fas fa-eye me-1"></i> Xem
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>

<!-- SCRIPT DRAW CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const labels = @json($chartLabels ?? []);
        const dataValues = @json($chartValues ?? []);

        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: dataValues,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.08)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: '#0d6efd'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' Doanh thu: ' + (context.raw || 0).toLocaleString('vi-VN') + ' VNĐ';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return value.toLocaleString('vi-VN') + 'đ'; }
                        }
                    }
                }
            }
        });
    });
</script>
@endsectiona