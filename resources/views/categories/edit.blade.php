@extends('layouts.app')

@section('title', 'Cập Nhật Danh Mục')

@section('content')
<div class="container mt-4" style="max-width: 850px;">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-0 fw-bold text-primary">
                <i class="bi bi-pencil-square me-2"></i>Cập Nhật Danh Mục: {{ $category->name }}
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Quay Lại
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <strong>Lỗi nhập liệu!</strong> Vui lòng kiểm tra lại thông tin dưới đây:
            <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Tên Danh Mục -->
                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Tên Danh Mục <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $category->name) }}" required>
                </div>

                <!-- Mô Tả Danh Mục -->
                <div class="mb-3">
                    <label for="description" class="form-label fw-bold">Mô Tả Danh Mục</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
                </div>

                <!-- CSS tùy chỉnh ô chọn màu -->
                <style>
                    .color-pill {
                        cursor: pointer;
                        border: 2px solid #cbd5e1;
                        background-color: #ffffff;
                        padding: 8px 16px;
                        border-radius: 50rem;
                        transition: all 0.2s ease;
                        user-select: none;
                    }
                    .color-pill:hover {
                        border-color: #0d6efd;
                        background-color: #f8fafc;
                    }
                    .color-pill input[type="checkbox"] {
                        cursor: pointer;
                        width: 18px;
                        height: 18px;
                    }
                </style>

                <!-- Danh Sách Màu Sắc Hỗ Trợ -->
                <div class="mb-4 p-3 bg-light border rounded-3">
                    <label class="form-label fw-bold d-block mb-1">
                        <i class="bi bi-palette me-1"></i> Danh Sách Màu Sắc Hỗ Trợ
                    </label>
                    <p class="text-muted small mb-3">Tích chọn các màu sắc khả dụng cho các sản phẩm thuộc danh mục này:</p>

                    @php
                        $colorList = [
                            'Đen'        => ['bg' => '#000000', 'border' => false],
                            'Bạc'        => ['bg' => '#c0c0c0', 'border' => false],
                            'Trắng'      => ['bg' => '#ffffff', 'border' => true],
                            'Xám'        => ['bg' => '#6c757d', 'border' => false],
                            'Inox'       => ['bg' => '#e2e8f0', 'border' => true],
                            'Vàng Đồng'  => ['bg' => '#d4af37', 'border' => false],
                            'Đỏ'         => ['bg' => '#dc3545', 'border' => false],
                            'Vàng'       => ['bg' => '#ffc107', 'border' => false],
                            'Xanh Dương' => ['bg' => '#0d6efd', 'border' => false],
                            'Xanh Lá'    => ['bg' => '#198754', 'border' => false],
                        ];

                        $currentColors = old('colors', $category->colors ?? []);
                        if (is_string($currentColors)) {
                            $currentColors = json_decode($currentColors, true) ?? [];
                        }
                    @endphp

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @foreach($colorList as $colorName => $style)
                            @php
                                $isChecked = is_array($currentColors) && in_array($colorName, $currentColors);
                            @endphp

                            <label class="color-pill d-flex align-items-center gap-2 shadow-sm">
                                <input type="checkbox" class="form-check-input m-0" name="colors[]" value="{{ $colorName }}" {{ $isChecked ? 'checked' : '' }}>
                                <span class="rounded-circle d-inline-block {{ $style['border'] ? 'border border-secondary-subtle' : '' }}" style="width: 15px; height: 15px; background-color: {{ $style['bg'] }};"></span>
                                <span class="fw-semibold text-dark fs-6">{{ $colorName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Trạng Thái Kích Hoạt -->
                <div class="mb-4">
                    <label for="status_select" class="form-label fw-bold">Trạng Thái Kích Hoạt <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror @error('is_active') is-invalid @enderror" id="status_select" name="status" onchange="document.getElementById('is_active_hidden').value = this.value">
                        <option value="1" {{ old('status', old('is_active', $category->is_active ?? '1')) == '1' ? 'selected' : '' }}>🟢 Hiển thị (Kích hoạt)</option>
                        <option value="0" {{ old('status', old('is_active', $category->is_active ?? '1')) == '0' ? 'selected' : '' }}>🔴 Ẩn (Khóa)</option>
                    </select>
                    <input type="hidden" id="is_active_hidden" name="is_active" value="{{ old('is_active', old('status', $category->is_active ?? '1')) }}">
                </div>

                <!-- Nút Thao Tác -->
                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary px-4">Hủy</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-check-circle me-1"></i> Lưu Thay Đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection