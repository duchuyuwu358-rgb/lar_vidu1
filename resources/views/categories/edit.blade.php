@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-0">Chỉnh Sửa Danh Mục</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">← Quay Lại</a>
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
            <form action="{{ route('categories.update', $category) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Tên Danh Mục <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" name="name" value="{{ old('name', $category->name) }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label fw-bold">Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $category->slug) }}">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-bold">Mô Tả</label>
                    <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $category->description) }}</textarea>
                </div>

                <!-- MÀU SẮC DANH MỤC -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Danh Sách Màu Sắc Hỗ Trợ</label>
                    <p class="text-muted small mb-2">Tích chọn các màu sắc khả dụng cho các sản phẩm thuộc danh mục này:</p>
                    
                    @php
                        $savedColors = old('colors', $category->colors ?? ['Đen', 'Bạc', 'Trắng']);
                        if (is_string($savedColors)) {
                            $savedColors = json_decode($savedColors, true) ?? array_map('trim', explode(',', $savedColors));
                        }
                        if (!is_array($savedColors)) {
                            $savedColors = [];
                        }

                        $availableColors = [
                            'Đen' => '#212529',
                            'Bạc' => '#c0c0c0',
                            'Trắng' => '#ffffff',
                            'Xám' => '#6c757d',
                            'Vàng Đồng' => '#d4af37'
                        ];
                    @endphp

                    <div class="d-flex flex-wrap gap-3">
                        @foreach($availableColors as $colorName => $hexCode)
                            <div class="form-check border rounded p-2 px-3 d-flex align-items-center bg-light">
                                <input class="form-check-input me-2" type="checkbox" name="colors[]" 
                                       id="color_{{ $loop->index }}" value="{{ $colorName }}"
                                       {{ in_array($colorName, $savedColors) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold d-flex align-items-center cursor-pointer mb-0" for="color_{{ $loop->index }}">
                                    <span class="d-inline-block rounded-circle me-1 border" 
                                          style="width: 16px; height: 16px; background-color: {{ $hexCode }};"></span>
                                    {{ $colorName }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- UPLOAD HÌNH ẢNH DANH MỤC -->
                <div class="mb-3">
                    <label for="image" class="form-label fw-bold">Hình Ảnh Danh Mục</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    @if($category->image)
                        <div class="mt-2">
                            <span class="d-block small text-muted mb-1">Ảnh hiện tại:</span>
                            <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="img-thumbnail" style="max-height: 100px; object-fit: cover;">
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label fw-bold">Trạng Thái Danh Mục <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active" {{ old('status', $category->is_active ? 'active' : 'inactive') == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="inactive" {{ old('status', $category->is_active ? 'active' : 'inactive') == 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Hủy</a>
                    <button type="submit" class="btn btn-primary">Cập Nhật Danh Mục</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection