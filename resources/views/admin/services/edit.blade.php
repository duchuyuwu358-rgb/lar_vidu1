@extends('layouts.app')

@section('title', 'Chỉnh sửa Gói Dịch Vụ')

@section('content')
<div class="container py-4">
    <div class="col-md-7 mx-auto">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h4 class="fw-bold mb-3 text-primary">
                <i class="bi bi-pencil-square me-2"></i>Chỉnh Sửa Gói Dịch Vụ
            </h4>

            <form action="{{ route('admin.services.update', $service->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Tên gói dịch vụ -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên gói dịch vụ <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $service->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Giá dịch vụ -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Giá dịch vụ (đ) <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $service->price) }}" required>
                    @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Trạng thái dịch vụ -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Trạng thái dịch vụ <span class="text-danger">*</span></label>
                    <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                        <option value="1" {{ old('is_active', $service->is_active) == 1 ? 'selected' : '' }}>Còn hàng (Hoạt động)</option>
                        <option value="0" {{ old('is_active', $service->is_active) == 0 ? 'selected' : '' }}>Hết hàng (Ẩn)</option>
                    </select>
                    @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Ô Chọn File Ảnh & Hiển Thị Ảnh Hiện Tại / Ảnh Mới -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Hình ảnh minh họa</label>
                    <input type="file" name="image" id="serviceImageInput" class="form-control @error('image') is-invalid @enderror" accept="image/*" onchange="previewEditImage(event)">
                    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                    @php
                        $existingImage = $service->image ?? $service->image_url ?? null;
                        $hasImage = !empty($existingImage);
                        $imageSrc = '#';
                        if ($hasImage) {
                            $imageSrc = \Illuminate\Support\Str::startsWith($existingImage, ['http://', 'https://']) 
                                ? $existingImage 
                                : asset('storage/' . $existingImage);
                        }
                    @endphp

                    <!-- Khung hiển thị duy nhất 1 ảnh (Ảnh cũ hiện tại hoặc Ảnh mới chọn) -->
                    <div id="editImagePreviewWrapper" class="mt-3 p-3 bg-light rounded-3 border {{ $hasImage ? '' : 'd-none' }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span id="editImageLabel" class="fw-bold text-secondary">
                                <i class="bi bi-image me-1"></i> {{ $hasImage ? 'Ảnh hiện tại đang sử dụng:' : 'Ảnh xem trước:' }}
                            </span>
                        </div>
                        <div class="text-center">
                            <img id="editImagePreviewTarget" src="{{ $imageSrc }}" alt="Ảnh minh họa" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: contain;">
                        </div>
                    </div>
                </div>

                <!-- Mô tả chi tiết -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả chi tiết</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description', $service->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Nút bấm -->
                <div class="d-flex justify-content-between mt-4 border-top pt-3">
                    <a href="{{ route('admin.services.index') }}" class="btn btn-light px-4">Quay lại</a>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-check-circle me-1"></i> Cập nhật thông tin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
    function previewEditImage(event) {
        const input = event.target;
        const wrapper = document.getElementById('editImagePreviewWrapper');
        const target = document.getElementById('editImagePreviewTarget');
        const label = document.getElementById('editImageLabel');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                target.src = e.target.result;
                label.innerHTML = '<i class="bi bi-image me-1"></i> Ảnh mới chọn (Xem trước):';
                wrapper.classList.remove('d-none');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection