@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Chi Tiết Máy Hút Mùi</h1>
        <div>
            <a href="{{ route('hoods.edit', $hood) }}" class="btn btn-warning me-2">Sửa</a>
            <a href="{{ route('hoods.index') }}" class="btn btn-secondary">← Quay Lại</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-5 text-center mb-4 mb-md-0">
                    @php
                        $rawImage = $hood->image ?? $hood->image_url ?? $hood->category?->image;
                        $imageUrl = null;
                        if (!empty($rawImage)) {
                            $rawImage = trim($rawImage);
                            if (\Illuminate\Support\Str::startsWith($rawImage, ['http://', 'https://', 'data:image/'])) {
                                $imageUrl = $rawImage;
                            } else {
                                $filename = basename($rawImage);
                                $imageUrl = asset('storage/hoods/' . $filename);
                            }
                        }
                    @endphp

                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $hood->name }}" class="img-fluid rounded shadow-sm" style="max-height: 400px; object-fit: contain;" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'p-5 bg-light rounded text-muted\'>Chưa có hình ảnh</div>';">
                    @else
                        <div class="p-5 bg-light rounded text-muted">Chưa có hình ảnh</div>
                    @endif
                </div>
                <div class="col-md-7">
                    <h2 class="h4 fw-bold">{{ $hood->name }}</h2>
                    <p class="text-muted mb-3">Model: {{ $hood->model ?? 'N/A' }}</p>

                    <table class="table table-striped border-top">
                        <tbody>
                            <tr><th width="35%">Danh mục</th><td>{{ $hood->category->name ?? 'N/A' }}</td></tr>
                            <tr><th>Loại</th><td>{{ $hood->type }}</td></tr>
                            <tr><th>Giá bán</th><td class="text-danger fw-bold fs-5">{{ number_format($hood->price, 0, ',', '.') }} VNĐ</td></tr>
                            <tr><th>Công suất</th><td>{{ $hood->power ?? 'N/A' }}</td></tr>
                            <tr><th>Kích thước</th><td>{{ $hood->dimensions ?? 'N/A' }}</td></tr>
                            <tr><th>Màu sắc</th><td><span class="badge bg-secondary">{{ $hood->color ?? 'N/A' }}</span></td></tr>
                            <tr><th>Chất liệu</th><td>{{ $hood->material ?? 'N/A' }}</td></tr>
                            <tr><th>Nhà sản xuất</th><td>{{ $hood->manufacturer ?? 'N/A' }}</td></tr>
                            <tr><th>Thời gian bảo hành</th><td>{{ $hood->warranty_months }} tháng</td></tr>
                            <tr><th>Tồn kho</th><td>{{ $hood->stock_quantity }} sản phẩm</td></tr>
                            <tr>
                                <th>Trạng thái</th>
                                <td>
                                    @if ($hood->is_active && $hood->stock_quantity > 0)
                                        <span class="badge bg-success">Đang bán</span>
                                    @elseif ($hood->is_active && $hood->stock_quantity <= 0)
                                        <span class="badge bg-warning text-dark">Đang nhập</span>
                                    @else
                                        <span class="badge bg-danger">Ngừng bán</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    @if ($hood->description)
                        <div class="mt-4">
                            <h5 class="h6 fw-bold">Mô tả sản phẩm</h5>
                            <p class="text-secondary">{!! nl2br(e($hood->description)) !!}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection