@extends(View::exists('layouts.admin') ? 'layouts.admin' : 'layouts.app')

@section('title', 'Admin Portal - Báo cáo doanh thu')

@section('content')
<div class="container-fluid py-3">
    <!-- HEADER & BUTTONS -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-0">Admin Portal</h2>
            <p class="text-muted small mb-0">Quản lý toàn bộ cửa hàng, báo cáo doanh thu & xuất dữ liệu</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <!-- Nút mở Modal Báo Cáo Excel -->
            <button type="button" class="btn btn-success fw-bold shadow-sm px-3 py-2" 
                    data-bs-toggle="modal" data-bs-target="#exportExcelModal"
                    data-toggle="modal" data-target="#exportExcelModal">
                <i class="fas fa-file-excel me-2"></i>Xuất Báo Cáo Excel
            </button>

            <!-- Nút tải Excel nhanh -->
            @php
                $exportRoute = Route::has('admin.portal.export') ? route('admin.portal.export') : '#';
            @endphp
            <a href="{{ $exportRoute }}" class="btn btn-outline-success fw-bold shadow-sm px-3 py-2" title="Tải toàn bộ báo cáo Excel ngay">
                <i class="fas fa-download me-1"></i> Tải nhanh
            </a>

            <!-- Nút Quản lý sản phẩm -->
            @php
                $productsRoute = Route::has('admin.hoods.index') ? route('admin.hoods.index') : (Route::has('hoods.index') ? route('hoods.index') : '#');
            @endphp
            <a href="{{ $productsRoute }}" class="btn btn-primary fw-bold shadow-sm px-3 py-2">
                <i class="fas fa-boxes me-2"></i>Quản lý sản phẩm
            </a>
        </div>
    </div>

    <!-- CARDS THỐNG KÊ TỔNG QUAN -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 mb-4">
        <div class="col">
            <div class="card bg-primary text-white p-3 border-0 shadow-sm rounded-3 h-100">
                <small class="text-white-50">Tổng sản phẩm</small>
                <h3 class="fw-bold mb-0 mt-1">{{ number_format($totalProducts ?? 0) }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card bg-info text-white p-3 border-0 shadow-sm rounded-3 h-100">
                <small class="text-white-50">Danh mục</small>
                <h3 class="fw-bold mb-0 mt-1">{{ number_format($totalCategories ?? 0) }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card bg-success text-white p-3 border-0 shadow-sm rounded-3 h-100">
                <small class="text-white-50">Khách hàng</small>
                <h3 class="fw-bold mb-0 mt-1">{{ number_format($totalCustomers ?? 0) }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card bg-warning text-dark p-3 border-0 shadow-sm rounded-3 h-100">
                <small class="text-dark-50">Số lượng đã bán</small>
                <h3 class="fw-bold mb-0 mt-1">{{ number_format($totalSold ?? 0) }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="card bg-danger text-white p-3 border-0 shadow-sm rounded-3 h-100">
                <small class="text-white-50">Tổng doanh thu</small>
                <h3 class="fw-bold mb-0 mt-1">{{ number_format($totalRevenue ?? 0, 0, ',', '.') }}đ</h3>
            </div>
        </div>
    </div>

    <!-- BIỂU ĐỒ DOANH THU -->
    <div class="card border-0 shadow-sm p-4 mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between pb-3 mb-3 border-bottom gap-3">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary-subtle text-primary p-2 rounded-3">
                    <i class="fas fa-chart-line fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">{{ $chartTitle ?? 'Doanh thu' }}</h5>
                    <small class="text-muted">{{ $chartSubtitle ?? '' }}</small>
                </div>
            </div>

            <!-- Bộ lọc Dropdown + Nút Thời Gian -->
            <div class="d-flex flex-wrap align-items-center gap-3">
                @php
                    $portalUrl = Route::has('admin.portal') ? route('admin.portal') : url()->current();
                @endphp
                <form action="{{ $portalUrl }}" method="GET" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="period" value="{{ $period ?? '1day' }}">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary border-end-0 fw-bold"><i class="fas fa-filter me-1"></i>Sản phẩm:</span>
                        <select name="product_id" class="form-select form-select-sm border-start-0 ps-1 shadow-none fw-semibold text-primary" style="min-width: 210px;" onchange="this.form.submit()">
                            <option value="all" {{ ($selectedProductId ?? 'all') === 'all' ? 'selected' : '' }}>-- Tất cả sản phẩm --</option>
                            @foreach($allProducts ?? [] as $product)
                                <option value="{{ $product->id }}" {{ (string)($selectedProductId ?? '') === (string)$product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <!-- Mốc thời gian -->
                <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border" role="group">
                    @foreach(['1day' => 'Hôm nay', '1week' => '7 ngày', '1month' => '30 ngày', '1year' => '12 tháng', 'all' => 'Tất cả'] as $pKey => $pLabel)
                        <a href="{{ $portalUrl }}?period={{ $pKey }}&product_id={{ $selectedProductId ?? 'all' }}" 
                           class="btn btn-sm rounded-pill px-3 {{ ($period ?? '1day') === $pKey ? 'btn-primary shadow-sm fw-bold' : 'btn-light text-secondary' }}">
                           {{ $pLabel }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div style="height: 330px; position: relative;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- BẢNG TOP SẢN PHẨM & TRẠNG THÁI ĐƠN HÀNG -->
    <div class="row g-4 mb-4">
        <!-- Top Sản Phẩm Bán Chạy -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="mb-3">
                    <h5 class="fw-bold mb-0 text-warning">
                        <i class="fas fa-crown me-1"></i> Top Sản Phẩm Bán Chạy
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-center">Đã bán</th>
                                <th class="text-end">Tổng thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts ?? [] as $item)
                                @php
                                    $productName = $item->hood->name ?? $item->product->name ?? ('Sản phẩm #' . ($item->product_id_fk ?? $item->hood_id ?? $item->product_id ?? ''));
                                @endphp
                                <tr>
                                    <td class="fw-bold text-dark">{{ $productName }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ number_format($item->total_qty ?? 0) }}</span>
                                    </td>
                                    <td class="text-end text-success fw-bold">
                                        {{ number_format($item->total_amount ?? 0, 0, ',', '.') }}đ
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Chưa có dữ liệu thống kê sản phẩm.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Trạng Thái Đơn Hàng -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-3 h-100">
                <h5 class="fw-bold mb-3 text-info">
                    <i class="fas fa-chart-pie me-1"></i> Trạng Thái Đơn Hàng
                </h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <span><i class="fas fa-check-circle text-success me-2 fs-5"></i>Đã thanh toán / Hoàn thành</span>
                        <span class="badge bg-success rounded-pill px-3 py-2 fs-6">{{ number_format($paidOrdersCount ?? 0) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <span><i class="fas fa-clock text-warning me-2 fs-5"></i>Chờ thanh toán</span>
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fs-6">{{ number_format($pendingOrdersCount ?? 0) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <span><i class="fas fa-times-circle text-danger me-2 fs-5"></i>Đã hủy / Thất bại</span>
                        <span class="badge bg-danger rounded-pill px-3 py-2 fs-6">{{ number_format($failedOrdersCount ?? 0) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 fw-bold bg-light rounded mt-2">
                        <span class="ms-2"><i class="fas fa-shopping-cart text-primary me-2 fs-5"></i>Tổng đơn hàng</span>
                        <span class="badge bg-primary rounded-pill px-3 py-2 fs-6 me-2">{{ number_format($totalOrdersCount ?? 0) }} đơn</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ĐƠN HÀNG GẦN ĐÂY -->
    <div class="card border-0 shadow-sm p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-primary">
                <i class="fas fa-shopping-bag me-1"></i> Đơn Hàng Gần Đây
            </h5>
            @php
                $ordersIndexRoute = Route::has('admin.orders.index') ? route('admin.orders.index') : (Route::has('orders.index') ? route('orders.index') : '#');
            @endphp
            <a href="{{ $ordersIndexRoute }}" class="btn btn-sm btn-link text-decoration-none">Quản lý tất cả đơn hàng &rarr;</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày đặt</th>
                        <th class="text-center pe-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders ?? [] as $order)
                        @php
                            $orderTotal = $order->total_amount ?? $order->total_price ?? 0;
                            $st = strtolower($order->status ?? '');
                            $custName = $order->user->name ?? $order->customer_name ?? $order->name ?? 'Khách vãng lai';
                            $custEmail = $order->user->email ?? $order->customer_email ?? $order->email ?? '---';
                        @endphp
                        <tr>
                            <td class="ps-3 fw-bold text-primary">#{{ $order->id }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $custName }}</div>
                                <small class="text-muted">{{ $custEmail }}</small>
                            </td>
                            <td class="fw-bold text-dark">{{ number_format($orderTotal, 0, ',', '.') }}đ</td>
                            <td>
                                @if(in_array($st, ['completed', 'paid', 'success', 'da_thanh_toan', 'đã thanh toán', 'thành công']))
                                    <span class="badge bg-success px-2 py-1">Đã thanh toán</span>
                                @elseif(in_array($st, ['processing', 'shipping', 'dang_xu_ly', 'dang_giao']))
                                    <span class="badge bg-info text-white px-2 py-1">Đang xử lý/giao</span>
                                @elseif(in_array($st, ['cancelled', 'canceled', 'failed', 'da_huy', 'đã hủy', 'thất bại']))
                                    <span class="badge bg-danger px-2 py-1">Đã hủy / Thất bại</span>
                                @else
                                    <span class="badge bg-warning text-dark px-2 py-1">Chờ thanh toán</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('H:i d/m/Y') : '---' }}
                            </td>
                            <td class="text-center pe-3">
                                @php
                                    $orderShowRoute = Route::has('admin.orders.show') ? route('admin.orders.show', $order->id) : (Route::has('orders.show') ? route('orders.show', $order->id) : '#');
                                @endphp
                                <a href="{{ $orderShowRoute }}" class="btn btn-sm btn-outline-primary rounded-2 px-3">
                                    <i class="fas fa-eye me-1"></i> Xem
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Chưa có đơn hàng nào gần đây.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL BỘ LỌC XUẤT EXCEL -->
<div class="modal fade" id="exportExcelModal" tabindex="-1" aria-labelledby="exportExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ $exportRoute }}" method="GET">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="exportExcelModalLabel"><i class="fas fa-file-excel me-2"></i>Tùy Chọn Xuất Báo Cáo Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- 1. Tìm kiếm từ khóa -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="fas fa-search me-1 text-primary"></i>Tìm kiếm nâng cao:</label>
                        <input type="text" name="search" class="form-control" placeholder="Nhập mã đơn, tên khách, email, SĐT...">
                    </div>

                    <!-- 2. Lọc Trạng Thái Đơn Hàng -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="fas fa-list-check me-1 text-warning"></i>Trạng thái đơn hàng:</label>
                        <select name="status" class="form-select">
                            <option value="all">-- Tất cả trạng thái --</option>
                            <option value="paid">Đã thanh toán / Thành công</option>
                            <option value="pending">Chờ thanh toán / Chờ xử lý</option>
                            <option value="cancelled">Đã hủy / Thất bại</option>
                        </select>
                    </div>

                    <!-- 3. Lọc Theo Sản Phẩm -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="fas fa-box me-1 text-info"></i>Lọc theo sản phẩm:</label>
                        <select name="product_id" class="form-select">
                            <option value="all">-- Tất cả sản phẩm --</option>
                            @foreach($allProducts ?? [] as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 4. Lọc Theo Khoảng Thời Gian -->
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-calendar-alt me-1 text-danger"></i>Từ ngày:</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-calendar-check me-1 text-success"></i>Đến ngày:</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>

                    <div class="alert alert-success border-0 bg-success-subtle text-success small mb-0 rounded-3">
                        <i class="fas fa-check-circle me-1"></i> Xuất file CSV (UTF-8 Excel) đầy đủ danh mục, số lượng, thành tiền và có tích hợp dòng <strong>TỔNG CỘNG</strong> tự động.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success fw-bold px-4"><i class="fas fa-download me-1"></i> Tải Báo Cáo Excel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- SCRIPT VẼ BIỂU ĐỒ CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartEl = document.getElementById('revenueChart');
        if (!chartEl) return;

        const ctx = chartEl.getContext('2d');
        const labels = {!! json_encode($chartLabels ?? []) !!};
        const dataValues = {!! json_encode($chartValues ?? []) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: dataValues,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#0d6efd',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                let value = context.raw || 0;
                                return ' Doanh thu: ' + value.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + ' Tr';
                                if (value >= 1000) return (value / 1000) + ' k';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush