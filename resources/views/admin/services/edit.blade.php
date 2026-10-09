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

                <!-- Ô Chọn File Ảnh & Khung Xem Trước Duy Nhất -->
                <div class="mb-3 image-upload-wrapper">
                    <label class="form-label fw-semibold">Hình ảnh minh họa</label>
                    <input type="file" name="image" id="serviceImageInput" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                    @php
                        $rawImage = $service->image ?? $service->image_url ?? $service->image_path ?? null;
                        $hasImage = !empty($rawImage);
                        $imageUrl = null;

                        if ($hasImage) {
                            $rawImage = trim($rawImage);
                            if (\Illuminate\Support\Str::startsWith($rawImage, ['http://', 'https://', 'data:image/'])) {
                                $imageUrl = $rawImage;
                            } else {
                                $cleanPath = ltrim($rawImage, '/');
                                if (\Illuminate\Support\Str::startsWith($cleanPath, 'storage/')) {
                                    $cleanPath = substr($cleanPath, 8);
                                }
                                $imageUrl = asset('storage/' . $cleanPath);
                            }
                        }
                    @endphp

                    <!-- Khung xem trước duy nhất -->
                    <div id="singleServicePreviewBox" class="mt-3 p-3 bg-light rounded-3 border {{ $hasImage ? '' : 'd-none' }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-secondary" id="singleServicePreviewLabel">
                                <i class="bi bi-image me-1"></i> Hình ảnh đính kèm (Xem trước):
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-danger border-0 fw-semibold" id="btnRemoveServicePreview">
                                <i class="bi bi-x-circle me-1"></i> Xóa ảnh
                            </button>
                        </div>
                        <div class="text-center">
                            <img id="singleServicePreviewImg" src="{{ $imageUrl ?? '#' }}" alt="Ảnh gói dịch vụ" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: contain;">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('serviceImageInput');
        const previewBox = document.getElementById('singleServicePreviewBox');
        const previewImg = document.getElementById('singleServicePreviewImg');
        const btnRemove = document.getElementById('btnRemoveServicePreview');
        const wrapper = document.querySelector('.image-upload-wrapper');

        function cleanupDuplicateBoxes() {
            if (!wrapper) return;
            const allBoxes = wrapper.querySelectorAll('.p-3.bg-light.rounded-3.border, .image-preview-wrapper, .image-preview-box');
            allBoxes.forEach(box => {
                if (box !== previewBox) {
                    box.remove();
                }
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        previewImg.src = evt.target.result;
                        previewBox.classList.remove('d-none');
                        setTimeout(cleanupDuplicateBoxes, 50);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        if (btnRemove) {
            btnRemove.addEventListener('click', function() {
                if (fileInput) fileInput.value = '';
                previewImg.src = '#';
                previewBox.classList.add('d-none');
                cleanupDuplicateBoxes();
            });
        }

        if (wrapper) {
            const observer = new MutationObserver(function() {
                cleanupDuplicateBoxes();
            });
            observer.observe(wrapper, { childList: true, subtree: true });
        }
    });
</script>
@endsection