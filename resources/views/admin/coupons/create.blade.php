@extends('layouts.app')

@section('title', 'Thêm Mã Khuyến Mại Mới - XFAN Store')

@section('content')
<div class="container-fluid py-3" style="max-width: 850px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-primary">
            <i class="bi bi-ticket-perforated-fill me-2"></i>Thêm Mã Khuyến Mại Mới
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
            <form action="{{ Route::has('admin.coupons.store') ? route('admin.coupons.store') : (Route::has('coupons.store') ? route('coupons.store') : url('/admin/coupons')) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Mã Khuyến Mại (Code) <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control text-uppercase fw-bold" value="{{ old('code') }}" placeholder="VD: XFAN10, GIAM50K..." required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Loại Khuyến Mại <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="percent" {{ old('type') == 'percent' ? 'selected' : '' }}>Giảm theo phần trăm (%)</option>
                            <option value="fixed" {{ old('type') == 'fixed' ? 'selected' : '' }}>Giảm số tiền cố định (VNĐ)</option>
                        </select>
                    </div>

                    <!-- LỌC ÁP DỤNG CỤ THỂ THEO SẢN PHẨM HOẶC DANH MỤC -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Áp Dụng Cho Sản Phẩm Cụ Thể:</label>
                        <select name="hood_id" class="form-select">
                            <option value="">-- Tất cả Sản phẩm --</option>
                            @if(isset($hoods) && count($hoods) > 0)
                                @foreach($hoods as $hood)
                                    <option value="{{ $hood->id }}" {{ old('hood_id') == $hood->id ? 'selected' : '' }}>{{ $hood->name }}</option>
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
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <small class="text-muted" style="font-size:0.78rem;">Nếu chọn, mã chỉ áp dụng cho sản phẩm thuộc danh mục này.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Giá Trị Giảm <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="value" class="form-control" value="{{ old('value') }}" placeholder="Nhập số % hoặc số tiền VNĐ..." required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Đơn Hàng Tối Thiểu (VNĐ)</label>
                        <input type="number" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', 0) }}" placeholder="VD: 500000">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Số Lượng Mã (Giới hạn)</label>
                        <input type="number" name="quantity" class="form-control" value="{{ old('quantity') }}" placeholder="Để trống nếu không giới hạn">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ngày Hết Hạn</label>
                        <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Mô Tả / Ghi Chú</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Nhập mô tả ngắn về chương trình ưu đãi...">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-md-12 d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ Route::has('admin.coupons.index') ? route('admin.coupons.index') : (Route::has('coupons.index') ? route('coupons.index') : url('/admin/coupons')) }}" class="btn btn-light border px-4">Hủy</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Lưu Mã Khuyến Mại</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection