@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-0">Thêm Máy Hút Mùi Mới</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('hoods.index') }}" class="btn btn-secondary">← Quay Lại</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Lỗi!</strong> Vui lòng kiểm tra lại thông tin nhập vào.
            <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('hoods.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Tên Máy Hút Mùi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="model" class="form-label">Model</label>
                        <input type="text" class="form-control @error('model') is-invalid @enderror" id="model" name="model" value="{{ old('model') }}">
                        @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <!-- DANH MỤC: TRUYỀN DATA-COLORS ĐỂ LỌC MÀU ĐỘNG -->
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label">Danh Mục <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required onchange="updateColorOptions()">
                            <option value="">-- Chọn Danh Mục --</option>
                            @foreach ($categories as $category)
                                @php
                                    $rawColors = is_array($category->colors) 
                                        ? $category->colors 
                                        : (is_string($category->colors) ? json_decode($category->colors, true) ?? explode(',', $category->colors) : []);
                                    $catColors = array_values(array_filter(array_map('trim', (array) $rawColors)));
                                @endphp
                                <option value="{{ $category->id }}" 
                                        data-colors='@json($catColors)'
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">Loại Máy <span class="text-danger">*</span></label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="">-- Chọn Loại --</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Giá (VNĐ)</label>
                        <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price') }}" step="0.01" min="0">
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="power" class="form-label">Công Suất</label>
                        <input type="text" class="form-control @error('power') is-invalid @enderror" id="power" name="power" value="{{ old('power') }}" placeholder="Ví dụ: 800W">
                        @error('power')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="dimensions" class="form-label">Kích Thước</label>
                        <input type="text" class="form-control @error('dimensions') is-invalid @enderror" id="dimensions" name="dimensions" value="{{ old('dimensions') }}" placeholder="Ví dụ: 80x70x35cm">
                        @error('dimensions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <!-- MÀU SẮC ĐỘNG THEO DANH MỤC -->
                    <div class="col-md-6 mb-3">
                        <label for="color" class="form-label fw-bold">Màu Sắc</label>
                        <select class="form-select fw-bold @error('color') is-invalid @enderror" 
                                id="color" name="color" onchange="updateColorStyle(this.value)" style="transition: all 0.2s;">
                        </select>
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="manufacturer" class="form-label">Nhà Sản Xuất</label>
                        <input type="text" class="form-control @error('manufacturer') is-invalid @enderror" id="manufacturer" name="manufacturer" value="{{ old('manufacturer') }}">
                        @error('manufacturer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="material" class="form-label">Chất Liệu</label>
                        <input type="text" class="form-control @error('material') is-invalid @enderror" id="material" name="material" value="{{ old('material') }}">
                        @error('material')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="warranty_months" class="form-label">Bảo Hành (tháng)</label>
                        <input type="number" class="form-control @error('warranty_months') is-invalid @enderror" id="warranty_months" name="warranty_months" value="{{ old('warranty_months', 12) }}" min="0" max="240">
                        @error('warranty_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="stock_quantity" class="form-label">Số Lượng Tồn Kho</label>
                        <input type="number" class="form-control @error('stock_quantity') is-invalid @enderror" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" min="0">
                        @error('stock_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Hình Ảnh Sản Phẩm</label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                    <small class="form-text text-muted">Tối đa 2MB, định dạng: JPEG, PNG, JPG, GIF</small>
                    @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Mô Tả</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Trạng Thái Sản Phẩm <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="selling" {{ old('status', 'selling') == 'selling' ? 'selected' : '' }}>Đang bán</option>
                        <option value="importing" {{ old('status') == 'importing' ? 'selected' : '' }}>Đang nhập</option>
                        <option value="sold_out" {{ old('status') == 'sold_out' ? 'selected' : '' }}>Hết hàng</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('hoods.index') }}" class="btn btn-outline-secondary">Hủy</a>
                    <button type="submit" class="btn btn-primary">Thêm Mới</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const colorConfig = {
        'Trắng':      { bg: '#ffffff', text: '#212529', border: '#ced4da' },
        'Đen':        { bg: '#1a1a1a', text: '#ffffff', border: '#000000' },
        'Bạc':        { bg: '#c0c0c0', text: '#111111', border: '#a0a0a0' },
        'Xám':        { bg: '#6c757d', text: '#ffffff', border: '#495057' },
        'Inox':       { bg: '#e2e8f0', text: '#1e293b', border: '#cbd5e1' },
        'Đỏ':         { bg: '#dc3545', text: '#ffffff', border: '#b02a37' },
        'Vàng':       { bg: '#ffc107', text: '#000000', border: '#d39e00' },
        'Vàng Đồng':  { bg: '#b8860b', text: '#ffffff', border: '#8b6508' },
        'Xanh Dương': { bg: '#0d6efd', text: '#ffffff', border: '#0a58ca' }
    };

    const currentSelectedColor = "{{ old('color') }}";

    function updateColorOptions() {
        const categorySelect = document.getElementById('category_id');
        const colorSelect = document.getElementById('color');
        if (!categorySelect || !colorSelect) return;

        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        let allowedColors = [];

        if (selectedOption && selectedOption.dataset.colors) {
            try {
                allowedColors = JSON.parse(selectedOption.dataset.colors);
            } catch (e) {
                allowedColors = [];
            }
        }

        colorSelect.innerHTML = '';

        if (!allowedColors || allowedColors.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- Chọn Danh Mục để chọn Màu --';
            colorSelect.appendChild(opt);
        } else {
            if (currentSelectedColor && !allowedColors.includes(currentSelectedColor)) {
                allowedColors.unshift(currentSelectedColor);
            }

            allowedColors.forEach(color => {
                const cleanColor = color.trim();
                const opt = document.createElement('option');
                opt.value = cleanColor;
                opt.textContent = cleanColor;
                if (cleanColor === currentSelectedColor) {
                    opt.selected = true;
                }
                colorSelect.appendChild(opt);
            });
        }

        updateColorStyle(colorSelect.value);
    }

    function updateColorStyle(colorName) {
        const select = document.getElementById('color');
        const style = colorConfig[colorName] || { bg: '#ffffff', text: '#212529', border: '#ced4da' };

        if (select) {
            select.style.backgroundColor = style.bg;
            select.style.color = style.text;
            select.style.borderColor = style.border;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateColorOptions();
    });
</script>
@endsection