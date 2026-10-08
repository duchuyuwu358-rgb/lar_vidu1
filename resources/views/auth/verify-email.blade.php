@extends('layouts.app')

@section('title', 'Xác minh Gmail - XFAN Store')

@section('content')
<div class="container py-4" style="max-width: 620px">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4 text-center">
            <div class="mb-3 text-primary">
                <i class="bi bi-envelope-check-fill display-4"></i>
            </div>
            
            <h4 class="fw-bold mb-3">Xác minh địa chỉ Gmail</h4>
            
            <p class="text-muted fs-6">
                Cảm ơn bạn đã đăng ký! Hãy kiểm tra hộp thư đến (hoặc thư mục Spam) của địa chỉ Gmail bạn vừa dùng để bấm vào liên kết xác minh.
            </p>

            {{-- Thông báo thành công mặc định của Laravel --}}
            @if(session('status') == 'verification-link-sent')
                <div class="alert alert-success mb-3 text-start">
                    ✅ Đã gửi lại email xác minh! Vui lòng kiểm tra hòm thư Gmail của bạn.
                </div>
            @elseif(session('status'))
                <div class="alert alert-success mb-3 text-start">
                    ✅ {{ session('status') }}
                </div>
            @endif

            {{-- Thông báo thành công tùy chỉnh --}}
            @if(session('success'))
                <div class="alert alert-success mb-3 text-start">
                    ✅ {{ session('success') }}
                </div>
            @endif

            {{-- Thông báo cảnh báo --}}
            @if(session('warning'))
                <div class="alert alert-warning mb-3 text-start">
                    ⚠️ {{ session('warning') }}
                </div>
            @endif

            {{-- Thông báo lỗi kết nối SMTP --}}
            @if(session('error'))
                <div class="alert alert-danger mb-3 text-start">
                    <div class="fw-bold mb-1">⚠️ Không thể gửi email xác minh lúc này:</div>
                    <small class="d-block text-break font-monospace p-2 rounded bg-light text-danger border border-danger-subtle">
                        {{ session('error') }}
                    </small>
                </div>
            @endif

            <div class="d-flex align-items-center justify-content-center gap-2 mt-4 flex-wrap">
                <form method="POST" action="{{ route('verification.send') }}" class="m-0">
                    @csrf
                    <button class="btn btn-primary px-3" type="submit">
                        <i class="bi bi-envelope-paper me-1"></i> Gửi lại email
                    </button>
                </form>

                <a href="{{ route('storefront') }}" class="btn btn-outline-primary px-3">
                    Bỏ qua (Vào cửa hàng) <i class="bi bi-arrow-right ms-1"></i>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button class="btn btn-outline-secondary px-3" type="submit">
                        <i class="bi bi-box-arrow-right me-1"></i> Đăng xuất
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection