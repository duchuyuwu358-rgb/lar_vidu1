@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    
    <!-- Tiêu đề trang (Đã bỏ nút Sinh Đơn Mẫu) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 fw-bold text-primary mb-0">
            <i class="fas fa-boxes me-2"></i>Quản Lý Đơn Hàng
        </h2>
    </div>

    <!-- Thông báo Flash Message -->
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Bộ lọc & Tìm kiếm -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('admin.orders.index') }}" method="GET" class="row g-3">
                <input type="hidden" name="tab" value="{{ $currentTab }}">
                
                <div class="col-md-5">
                    <label class="form-label small text-muted font-weight-bold">Từ khóa tìm kiếm</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" 
                               placeholder="Mã đơn, Tên, SĐT, Email, Mã GHN..." value="{{ $search }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Từ ngày</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Đến ngày</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Lọc
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Các Tab Trạng Thái -->
    <div class="mb-3">
        <div class="nav nav-pills flex-column flex-sm-row gap-2">
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['tab' => 'all'])) }}" 
               class="nav-link {{ $currentTab === 'all' ? 'active' : 'bg-light text-dark' }}">
                Tất cả <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['all'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['tab' => 'pending_payment'])) }}" 
               class="nav-link {{ $currentTab === 'pending_payment' ? 'active' : 'bg-light text-dark' }}">
                Chờ thanh toán <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['pending_payment'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['tab' => 'paid'])) }}" 
               class="nav-link {{ $currentTab === 'paid' ? 'active' : 'bg-light text-dark' }}">
                Đã thanh toán <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['paid'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['tab' => 'processing'])) }}" 
               class="nav-link {{ $currentTab === 'processing' ? 'active' : 'bg-light text-dark' }}">
                Đang xử lý <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['processing'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['tab' => 'shipping'])) }}" 
               class="nav-link {{ $currentTab === 'shipping' ? 'active' : 'bg-light text-dark' }}">
                Đang giao <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['shipping'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['tab' => 'completed'])) }}" 
               class="nav-link {{ $currentTab === 'completed' ? 'active' : 'bg-light text-dark' }}">
                Hoàn thành <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['completed'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['tab' => 'cancelled'])) }}" 
               class="nav-link {{ $currentTab === 'cancelled' ? 'active' : 'bg-light text-dark' }}">
                Đã hủy <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['cancelled'] ?? 0 }}</span>
            </a>
        </div>
    </div>

    <!-- Bảng Danh Sách Đơn Hàng -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th>Thanh toán</th>
                            <th>Mã GHN</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="ps-3 fw-bold">#{{ $order->id }}</td>
                                <td>
                                    <div class="fw-bold">{{ $order->name }}</div>
                                    <small class="text-muted">{{ $order->phone }}</small>
                                </td>
                                <td class="fw-bold text-danger">
                                    {{ number_format($order->total_price, 0, ',', '.') }}đ
                                </td>
                                <td>
                                    @if($order->payment_status === 'paid')
                                        <span class="badge bg-success">Đã thanh toán</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Chưa thanh toán</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->ghn_order_code)
                                        <span class="badge bg-secondary font-monospace">{{ $order->ghn_order_code }}</span>
                                    @else
                                        <span class="text-muted small">Chưa có</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" class="form-select form-select-sm border-0 bg-light fw-semibold" onchange="this.form.submit()">
                                            <option value="pending_payment" {{ $order->status === 'pending_payment' ? 'selected' : '' }}>Chờ thanh toán</option>
                                            <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                                            <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                                            <option value="shipping" {{ $order->status === 'shipping' ? 'selected' : '' }}>Đang giao hàng</option>
                                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Đã hủy / Thất bại</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="small text-muted">
                                    {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Xem chi tiết -->
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-outline-primary" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @if(empty($order->ghn_order_code))
                                            <!-- Đẩy đơn GHN -->
                                            <form action="{{ route('admin.orders.pushGhn', $order->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Đẩy đơn sang GHN">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </form>
                                        @else
                                            <!-- Đồng bộ GHN -->
                                            <form action="{{ route('admin.orders.syncGhn', $order->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-info" title="Đồng bộ trạng thái GHN">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Không tìm thấy đơn hàng nào!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection