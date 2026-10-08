@extends('layouts.app')

@section('title', 'Gửi Thư Hỗ Trợ - XFAN Store')

@section('content')
<div class="container py-4" style="max-width: 750px;">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-dark text-white p-3 rounded-top-4">
            <h5 class="mb-0 fw-bold"><i class="bi bi-headset text-warning me-2"></i> Gửi Yêu Cầu Hỗ Trợ Kỹ Thuật & Dịch Vụ</h5>
        </div>
        <div class="card-body p-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('user.support.send') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ auth()->check() ? auth()->user()->name : old('name') }}" required placeholder="Nhập họ tên...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email liên hệ <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ auth()->check() ? auth()->user()->email : old('email') }}" required placeholder="Nhập email...">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Số điện thoại liên hệ</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="Nhập số điện thoại...">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Tiêu đề cần hỗ trợ <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required placeholder="VD: Hỗ trợ tư vấn kích thước máy hút mùi / Bảo hành">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Nội dung chi tiết <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="5" required placeholder="Mô tả chi tiết yêu cầu hỗ trợ...">{{ old('message') }}</textarea>
                    </div>

                    <!-- Ô TẢI TỆP ĐÍNH KÈM (PDF, WORD, HÌNH ẢNH) -->
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Tệp đính kèm / Hình ảnh (Tùy chọn):</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <div class="form-text text-muted">Hỗ trợ các định dạng: <b>.pdf, .doc, .docx, .jpg, .png</b> (Dung lượng tối đa 10MB).</div>
                    </div>

                    <div class="col-md-12 d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('storefront') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Quay lại cửa hàng</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send-fill me-1"></i> Gửi Yêu Cầu Hỗ Trợ</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection