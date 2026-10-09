@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-0">Cập Nhật Máy Hút Mùi</h1>
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
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('hoods.update', ['hood' => $hood->id ?? $hood]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @php
                    $currentStatus = $hood->is_active ? (($hood->stock_quantity ?? 0) > 0 ? 'selling' : 'importing') : 'sold_out';
                @endphp

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label fw-bold">Tên Máy Hút Mùi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $hood->name) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="model" class="form-label fw-bold">Model</label>
                        <input type="text" class="form-control @error('model') is-invalid @enderror" id="model" name="model" value="{{ old('model', $hood->model) }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label fw-bold">Danh Mục <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required onchange="updateColorOptions(false)">
                            <option value="">-- Chọn Danh Mục --</option>
                            @if(isset($categories))
                                @foreach ($categories as $category)
                                    @php
                                        $catColors = $category->colors ?? [];
                                        if (is_string($catColors)) {
                                            $catColors = json_decode($catColors, true) ?? [];
                                        }
                                    @endphp
                                    <option value="{{ $category->id }}" 
                                            data-colors='@json($catColors)' 
                                            {{ old('category_id', $hood->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label fw-bold">Loại Máy <span class="text-danger">*</span></label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            @if(isset($types) && is_array($types))
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" {{ old('type', $hood->type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label fw-bold">Giá (VNĐ)</label>
                        <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $hood->price) }}" step="0.01" min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="power" class="form-label fw-bold">Công Suất</label>
                        <input type="text" class="form-control @error('power') is-invalid @enderror" id="power" name="power" value="{{ old('power', $hood->power) }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="dimensions" class="form-label fw-bold">Kích Thước</label>
                        <input type="text" class="form-control @error('dimensions') is-invalid @enderror" id="dimensions" name="dimensions" value="{{ old('dimensions', $hood->dimensions) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="color" class="form-label fw-bold">Màu Sắc</label>
                        <select class="form-select fw-bold @error('color') is-invalid @enderror" id="color" name="color" onchange="updateColorStyle(this.value)"></select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="manufacturer" class="form-label fw-bold">Nhà Sản Xuất</label>
                        <input type="text" class="form-control @error('manufacturer') is-invalid @enderror" id="manufacturer" name="manufacturer" value="{{ old('manufacturer', $hood->manufacturer) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="material" class="form-label fw-bold">Chất Liệu</label>
                        <input type="text" class="form-control @error('material') is-invalid @enderror" id="material" name="material" value="{{ old('material', $hood->material) }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="warranty_months" class="form-label fw-bold">Bảo Hành (tháng)</label>
                        <input type="number" class="form-control @error('warranty_months') is-invalid @enderror" id="warranty_months" name="warranty_months" value="{{ old('warranty_months', $hood->warranty_months) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="stock_quantity" class="form-label fw-bold">Số Lượng Tồn Kho</label>
                        <input type="number" class="form-control @error('stock_quantity') is-invalid @enderror" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $hood->stock_quantity) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label fw-bold">Hình Ảnh Sản Phẩm</label>
                    @php
                        $imagePath = $hood->image ?? $hood->image_url;
                        $imageUrl = null;
                        if (!empty($imagePath)) {
                            $imagePath = trim($imagePath);
                            if (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://', 'data:image/'])) {
                                $imageUrl = $imagePath;
                            } else {
                                $filename = basename($imagePath);
                                $imageUrl = asset('storage/hoods/' . $filename);
                            }
                        }
                    @endphp
                    @if ($imageUrl)
                        <div class="mb-2">
                            <img src="{{ $imageUrl }}" alt="Ảnh hiện tại" width="100" class="img-thumbnail shadow-sm" onerror="this.style.display='none';">
                        </div>
                    @endif
                    <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-bold">Mô Tả</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $hood->description) }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label fw-bold">Trạng Thái Sản Phẩm <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="selling" {{ old('status', $currentStatus) == 'selling' ? 'selected' : '' }}>Đang bán</option>
                        <option value="importing" {{ old('status', $currentStatus) == 'importing' ? 'selected' : '' }}>Đang nhập</option>
                        <option value="sold_out" {{ old('status', $currentStatus) == 'sold_out' ? 'selected' : '' }}>Hết hàng</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('hoods.index') }}" class="btn btn-outline-secondary">Hủy</a>
                    <button type="submit" class="btn btn-primary">Lưu Thay Đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const colorConfig = {
        'Trắng': { bg: '#ffffff', text: '#212529', border: '#ced4da' },
        'Đen': { bg: '#1a1a1a', text: '#ffffff', border: '#000000' },
        'Bạc': { bg: '#c0c0c0', text: '#111111', border: '#a0a0a0' },
        'Xám': { bg: '#6c757d', text: '#ffffff', border: '#495057' },
        'Inox': { bg: '#e2e8f0', text: '#1e293b', border: '#cbd5e1' },
        'Đỏ': { bg: '#dc3545', text: '#ffffff', border: '#b02a37' },
        'Vàng': { bg: '#ffc107', text: '#000000', border: '#d39e00' },
        'Vàng Đồng': { bg: '#b8860b', text: '#ffffff', border: '#8b6508' },
        'Xanh Dương': { bg: '#0d6efd', text: '#ffffff', border: '#0a58ca' },
        'Xanh Lá': { bg: '#198754', text: '#ffffff', border: '#146c43' }
    };

    const initialColor = "{{ old('color', $hood->color) }}";

    function updateColorOptions(isInitialLoad = false) {
        const categorySelect = document.getElementById('category_id');
        const colorSelect = document.getElementById('color');
        if (!categorySelect || !colorSelect) return;

        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        let allowedColors = [];

        if (selectedOption && selectedOption.getAttribute('data-colors')) {
            try { 
                allowedColors = JSON.parse(selectedOption.getAttribute('data-colors')); 
            } catch (e) { 
                console.error("Lỗi parse màu sắc:", e);
                allowedColors = []; 
            }
        }

        colorSelect.innerHTML = '';

        if (!Array.isArray(allowedColors) || allowedColors.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- Chọn Danh Mục để chọn Màu --';
            colorSelect.appendChild(opt);
        } else {
            if (isInitialLoad && initialColor && !allowedColors.includes(initialColor)) {
                allowedColors.unshift(initialColor);
            }

            allowedColors.forEach((color, index) => {
                const cleanColor = String(color).trim();
                const opt = document.createElement('option');
                opt.value = cleanColor;
                opt.textContent = cleanColor;
                
                if (isInitialLoad && cleanColor === initialColor) {
                    opt.selected = true;
                } else if (!isInitialLoad && index === 0) {
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
        updateColorOptions(true);
    });
</script>
@endsection