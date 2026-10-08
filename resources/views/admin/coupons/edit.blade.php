@extends('layouts.app')

@section('title', 'Chỉnh Sửa Mã Khuyến Mại - XFAN Store')

@section('content')
<div class="container-fluid py-3" style="max-width: 850px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-primary">
            <i class="bi bi-pencil-square me-2"></i>Chỉnh Sửa Mã Khuyến Mại #{{ $coupon->id }}
        </h4>
        <a href="{{ Route::has('admin.coupons.index') ? route('admin.coupons.index') : (Route::has('coupons.index') ? route('coupons.index') : url('/admin/coupons')) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ Route::has('admin.coupons.update') ? route('admin.coupons.update', $coupon->id) : (Route::has('coupons.update') ? route('coupons.update', $coupon->id) : url('/admin/coupons/' . $coupon->id)) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Mã Khuyến Mại (Code) <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control text-uppercase fw-bold" value="{{ old('code', $coupon->code) }}" placeholder="VD: XFAN10, GIAM50K..." required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Loại Khuyến Mại <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="percent" {{ old('type', $coupon->type) == 'percent' ? 'selected' : '' }}>Giảm theo phần trăm (%)</option>
                            <option value="fixed" {{ old('type', $coupon->type) == 'fixed' ? 'selected' : '' }}>Giảm số tiền cố định (VNĐ)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Áp Dụng Cho Sản Phẩm Cụ Thể:</label>
                        <select name="hood_id" class="form-select">
                            <option value="">-- Tất cả Sản phẩm --</option>
                            @if(isset($hoods) && count($hoods) > 0)
                                @foreach($hoods as $hood)
                                    <option value="{{ $hood->id }}" {{ old('hood_id', $coupon->hood_id) == $hood->id ? 'selected' : '' }}>{{ $hood->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <small class="text-muted" style="font-size:0.78rem;">Nếu chọn, mã chỉ giảm giá cho sản phẩm này.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Áp Dụng Cho Danh Mục:</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Tất cả Danh mục --</option>
                            @if(isset($categories) && count($categories) > 0)
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $coupon->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <small class="text-muted" style="font-size:0.78rem;">Nếu chọn, mã chỉ áp dụng cho sản phẩm thuộc danh mục này.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Giá Trị Giảm <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="value" class="form-control" value="{{ old('value', $coupon->value) }}" placeholder="Nhập số % hoặc số tiền VNĐ..." required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Đơn Hàng Tối Thiểu (VNĐ)</label>
                        <input type="number" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', $coupon->min_order_amount ?? 0) }}" placeholder="VD: 500000">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Số Lượng Mã (Giới hạn)</label>
                        <input type="number" name="quantity" class="form-control" value="{{ old('quantity', $coupon->quantity) }}" placeholder="Để trống nếu không giới hạn">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ngày Hết Hạn</label>
                        <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '') }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Trạng Thái Kích Hoạt</label>
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">Kích hoạt mã khuyến mại này</label>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Mô Tả / Ghi Chú</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Nhập mô tả ngắn về chương trình ưu đãi...">{{ old('description', $coupon->description) }}</textarea>
                    </div>

                    <div class="col-md-12 d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ Route::has('admin.coupons.index') ? route('admin.coupons.index') : (Route::has('coupons.index') ? route('coupons.index') : url('/admin/coupons')) }}" class="btn btn-light border px-4">Hủy</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Cập Nhật Mã Khuyến Mại</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection