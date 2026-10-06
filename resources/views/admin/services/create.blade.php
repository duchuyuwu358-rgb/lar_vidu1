@extends('layouts.app')

@section('title', 'Thêm Gói Dịch Vụ Mới')

@section('content')
<div class="col-md-6 mx-auto">
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h4 class="fw-bold mb-3">Thêm Gói Dịch Vụ Mới</h4>

        <form action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Tên gói dịch vụ <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nhập tên dịch vụ...">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Giá dịch vụ (đ) <span class="text-danger">*</span></label>
                <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" placeholder="Nhập giá...">
                @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Trạng thái dịch vụ <span class="text-danger">*</span></label>
                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Còn hàng</option>
                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Hết hàng</option>
                </select>
                @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Hình ảnh minh họa</label>
                <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Mô tả chi tiết</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Nhập mô tả...">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.services.index') }}" class="btn btn-light">Quay lại</a>
                <button type="submit" class="btn btn-primary fw-bold">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>
@endsection