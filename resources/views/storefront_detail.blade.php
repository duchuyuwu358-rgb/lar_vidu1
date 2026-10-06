@extends('layouts.app')

@section('title', $hood->name)

@section('content')
@php
    // Khởi tạo $colors ngay đầu view để dùng chung cho cả Form đặt hàng lẫn Bảng thông số
    $rawColors = $hood->color ?? $hood->colors ?? $hood->category?->colors ?? 'Trắng';
    
    if (is_string($rawColors)) {
        $colors = array_filter(array_map('trim', explode(',', $rawColors)));
    } else {
        $colors = (array) $rawColors;
    }

    if (empty($colors)) {
        $colors = ['Trắng'];
    }

    // Bảng mã màu động
    $colorMap = [
        'Trắng'      => ['bg' => '#FFFFFF', 'text' => '#212529', 'border' => '#CCCCCC'],
        'Đen'        => ['bg' => '#1A1A1A', 'text' => '#FFFFFF', 'border' => '#000000'],
        'Bạc'        => ['bg' => '#C0C0C0', 'text' => '#111111', 'border' => '#A0A0A0'],
        'Xám'        => ['bg' => '#6C757D', 'text' => '#FFFFFF', 'border' => '#495057'],
        'Inox'       => ['bg' => '#E2E8F0', 'text' => '#1E293B', 'border' => '#CBD5E1'],
        'Đỏ'         => ['bg' => '#DC3545', 'text' => '#FFFFFF', 'border' => '#B02A37'],
        'Vàng'       => ['bg' => '#FFC107', 'text' => '#212529', 'border' => '#D39E00'],
        'Cam'        => ['bg' => '#FD7E14', 'text' => '#FFFFFF', 'border' => '#DC6803'],
        'Hồng'       => ['bg' => '#E83E8C', 'text' => '#FFFFFF', 'border' => '#D63384'],
        'Xanh Dương' => ['bg' => '#0D6EFD', 'text' => '#FFFFFF', 'border' => '#0A58CA'],
        'Xanh Lá'    => ['bg' => '#198754', 'text' => '#FFFFFF', 'border' => '#146C43'],
        'Đồng'       => ['bg' => '#B87333', 'text' => '#FFFFFF', 'border' => '#965B25'],
        'Gỗ'         => ['bg' => '#8B5A2B', 'text' => '#FFFFFF', 'border' => '#6B4220'],
    ];
@endphp

<style>
    /* Hiệu ứng nổi bật khi chọn nút màu sắc */
    .btn-check:checked + .custom-color-option {
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.4);
        border-color: #0d6efd !important;
        transform: scale(1.02);
    }
    .custom-color-option {
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
</style>

<div class="container py-4">
    <!-- Nút quay lại -->
    <div class="mb-3">
        <a href="{{ route('storefront') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Quay lại cửa hàng
        </a>
    </div>

    <!-- KHU VỰC THÔNG TIN CHÍNH & ĐẶT HÀNG -->
    <div class="card p-4 shadow-sm border-0 mb-4">
        <div class="row align-items-center">
            <!-- Cột Trái: Ảnh Sản Phẩm -->
            <div class="col-md-5 text-center mb-3 mb-md-0">
                @php
                    $imagePath = $hood->image ?? $hood->image_url ?? $hood->category?->image;
                @endphp

                @if($imagePath)
                    <img src="{{ \Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://']) ? $imagePath : asset('storage/' . $imagePath) }}" 
                         class="img-fluid rounded shadow-sm" 
                         alt="{{ $hood->name }}" 
                         style="max-height: 350px; object-fit: cover; width: 100%;">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 300px;">
                        <i class="fas fa-fan fa-4x text-secondary"></i>
                    </div>
                @endif
            </div>
            
            <!-- Cột Phải: Thông tin & Nút Đặt Hàng -->
            <div class="col-md-7">
                <h2 class="fw-bold text-dark mb-1">{{ $hood->name }}</h2>
                <p class="text-muted small mb-2">
                    Mã sản phẩm: <strong class="text-dark">{{ $hood->code ?? $hood->model ?? 'N/A' }}</strong> | 
                    Thương hiệu: <strong class="text-primary">{{ $hood->manufacturer ?? $hood->brand ?? 'XFAN' }}</strong>
                </p>
                
                <h3 class="text-primary fw-bold my-3">
                    {{ $hood->price ? number_format($hood->price, 0, ',', '.') . ' đ' : 'Liên hệ' }}
                </h3>
                
                <!-- Tóm tắt thông số nhanh -->
                <div class="row g-2 mb-3 small text-secondary">
                    <div class="col-6 col-sm-4">
                        <i class="fas fa-layer-group text-primary me-1"></i> Danh mục: 
                        <span class="badge bg-secondary">{{ $hood->category->name ?? 'Chưa phân loại' }}</span>
                    </div>
                    <div class="col-6 col-sm-4">
                        <i class="fas fa-bolt text-warning me-1"></i> Công suất: 
                        <strong class="text-dark">{{ $hood->power ?? $hood->capacity ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-6 col-sm-4">
                        <i class="fas fa-shield-alt text-success me-1"></i> Bảo hành: 
                        <strong class="text-dark">{{ $hood->warranty ?? $hood->warranty_months ?? '24' }} tháng</strong>
                    </div>
                    <div class="col-6 col-sm-4">
                        <i class="fas fa-fan text-info me-1"></i> Loại máy: 
                        <strong class="text-dark">{{ $hood->type ?? $hood->hood_type ?? 'Treo tường' }}</strong>
                    </div>
                    <div class="col-6 col-sm-4">
                        <i class="fas fa-boxes text-danger me-1"></i> Tồn kho: 
                        <strong class="text-dark">{{ $hood->stock_quantity ?? 0 }} sản phẩm</strong>
                    </div>
                </div>

                <hr class="my-3">
                
                <p class="fw-bold mb-1">Mô tả sản phẩm:</p>
                <p class="text-secondary mb-3">{!! nl2br(e($hood->description ?? 'Chưa có mô tả cho sản phẩm này.')) !!}</p>

                <!-- KHU VỰC KIỂM TRA PHÂN QUYỀN MUA HÀNG -->
                <div class="p-3 bg-light border rounded">
                    @auth
                        @if(auth()->user()->hasVerifiedEmail())
                            @if(($hood->stock_quantity ?? 0) > 0)
                                <form action="{{ route('cart.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="hood_id" value="{{ $hood->id }}">
                                    
                                    <!-- BỘ CHỌN MÀU SẮC DỰA TRÊN MÀU THỰC TẾ -->
                                    <div class="mb-3">
                                        <label class="fw-bold d-block mb-2">Chọn màu sắc:</label>
                                        <div class="d-flex flex-wrap gap-2" role="group" aria-label="Color selector">
                                            @foreach($colors as $index => $color)
                                                @php
                                                    $formattedColor = mb_convert_case($color, MB_CASE_TITLE, "UTF-8");
                                                    $cStyle = $colorMap[$formattedColor] ?? ['bg' => '#F8F9FA', 'text' => '#212529', 'border' => '#CED4DA'];
                                                @endphp
                                                <input type="radio" class="btn-check" name="color" id="color_option_{{ $index }}" value="{{ $color }}" {{ $loop->first ? 'checked' : '' }}>
                                                
                                                <label class="btn custom-color-option px-3 py-2 d-inline-flex align-items-center gap-2 rounded border" 
                                                       for="color_option_{{ $index }}"
                                                       style="background-color: {{ $cStyle['bg'] }}; color: {{ $cStyle['text'] }}; border-color: {{ $cStyle['border'] }} !important;">
                                                    <span class="rounded-circle border" 
                                                          style="width: 12px; height: 12px; display: inline-block; background-color: {{ $cStyle['bg'] }}; border-color: {{ $cStyle['text'] }}55 !important;"></span>
                                                    <span class="fw-semibold">{{ $color }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- CHỌN SỐ LƯỢNG VÀ NÚT BẤM (THÊM VÀO GIỎ / MUA NGAY) -->
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <label class="me-2 fw-bold mb-0">Số lượng:</label>
                                        <input type="number" name="quantity" class="form-control me-2" value="1" min="1" max="{{ $hood->stock_quantity ?? 1 }}" style="width: 80px;">
                                        
                                        <!-- Nút Thêm Vào Giỏ -->
                                        <button type="submit" name="action" value="add_to_cart" class="btn btn-outline-primary fw-bold">
                                            <i class="fas fa-cart-plus me-1"></i> Thêm vào giỏ
                                        </button>

                                        <!-- Nút Mua Ngay -->
                                        <button type="submit" name="action" value="buy_now" class="btn btn-danger fw-bold">
                                            <i class="fas fa-bolt me-1"></i> Mua ngay
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-times-circle me-1"></i> Sản phẩm hiện đang tạm hết hàng.
                                </div>
                            @endif
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Bạn cần <a href="{{ route('verification.notice') }}" class="alert-link fw-bold">Xác minh Email</a> để có thể đặt hàng.
                            </div>
                        @endif
                    @else
                        <div class="d-flex align-items-center">
                            <span class="text-danger fw-bold me-3"><i class="fas fa-lock me-1"></i> Đăng nhập để mua hàng</span>
                            <a href="{{ route('login') }}" class="btn btn-warning btn-sm me-2">
                                <i class="fas fa-sign-in-alt me-1"></i> Đăng nhập
                            </a>
                            <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-sm">Đăng ký ngay</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- BẢNG THÔNG SỐ KỸ THUẬT CHI TIẾT -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white fw-bold py-3">
            <i class="fas fa-list-alt me-2"></i>Thông Số Kỹ Thuật Chi Tiết
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <tbody>
                        <tr>
                            <th class="w-25 ps-4 text-secondary">Tên Sản Phẩm</th>
                            <td class="fw-bold text-dark">{{ $hood->name }}</td>
                        </tr>
                        <tr>
                            <th class="ps-4 text-secondary">Mã Model</th>
                            <td class="fw-bold text-primary">{{ $hood->model ?? $hood->code ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="ps-4 text-secondary">Danh Mục</th>
                            <td>{{ $hood->category->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="ps-4 text-secondary">Loại Máy Hút Mùi</th>
                            <td>{{ $hood->type ?? $hood->hood_type ?? 'Treo tường' }}</td>
                        </tr>
                        <tr>
                            <th class="ps-4 text-secondary">Công Suất</th>
                            <td><span class="badge bg-info text-dark fs-6">{{ $hood->power ?? $hood->capacity ?? 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <th class="ps-4 text-secondary">Kích Thước</th>
                            <td>{{ $hood->dimensions ?? $hood->size ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="ps-4 text-secondary">Màu Sắc Khả Dụng</th>
                            <td>{{ implode(', ', $colors) }}</td>
                        </tr>
                        <tr>
                            <th class="ps-4 text-secondary">Nhà Sản Xuất / Thương Hiệu</th>
                            <td>{{ $hood->manufacturer ?? $hood->brand ?? 'XFAN' }}</td>
                        </tr>
                        <tr>
                            <th class="ps-4 text-secondary">Chất Liệu</th>
                            <td>{{ $hood->material ?? 'Inox, Kính cường lực' }}</td>
                        </tr>
                        <tr>
                            <th class="ps-4 text-secondary">Bảo Hành</th>
                            <td><strong>{{ $hood->warranty ?? $hood->warranty_months ?? '24' }} tháng</strong> (Chính hãng)</td>
                        </tr>
                        <tr>
                            <th class="ps-4 text-secondary">Số Lượng Tồn Kho</th>
                            <td>{{ $hood->stock_quantity ?? 0 }} sản phẩm</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection