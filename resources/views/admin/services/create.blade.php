@extends('layouts.app') {{-- Đổi thành 'layouts.admin' nếu layout admin của bạn nằm trong thư mục admin --}}

@section('title', 'Thêm Gói Dịch Vụ Mới')

@section('content')
<div class="container py-4">
    <div class="col-md-7 mx-auto">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h4 class="fw-bold mb-3">Thêm Gói Dịch Vụ Mới</h4>

            <form action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Tên gói dịch vụ -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên gói dịch vụ <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nhập tên dịch vụ..." required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Giá dịch vụ -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Giá dịch vụ (đ) <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" placeholder="Nhập giá..." required>
                    @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Trạng thái dịch vụ -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Trạng thái dịch vụ <span class="text-danger">*</span></label>
                    <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Còn hàng (Hoạt động)</option>
                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Hết hàng (Ẩn)</option>
                    </select>
                    @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Hình ảnh minh họa & Khung xem trước -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Hình ảnh minh họa</label>
                    <input type="file" name="image" id="imageInput" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                    <!-- Khung hiển thị ảnh xem trước ngay khi vừa chọn file -->
                    <div class="mt-3 text-center">
                        <img id="imagePreview" src="#" alt="Xem trước ảnh" class="img-thumbnail shadow-sm d-none" style="max-height: 200px; border-radius: 10px; object-fit: cover;">
                    </div>
                </div>

                <!-- Mô tả chi tiết -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả chi tiết</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Nhập mô tả...">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Nút bấm -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('admin.services.index') }}" class="btn btn-light px-4">Quay lại</a>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Lưu thông tin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript tự động đọc file và hiển thị ảnh trực tiếp -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');

        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('d-none'); // Bật hiển thị khung ảnh
                    }
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.src = '#';
                    imagePreview.classList.add('d-none'); // Ẩn khung ảnh nếu bấm bỏ chọn file
                }
            });
        }
    });
</script>
@endsection