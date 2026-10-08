@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 620px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3 mb-3">Xác minh Gmail</h1>
            
            <p class="text-muted">
                Hãy kiểm tra hộp thư đến (hoặc thư mục Spam) của địa chỉ Gmail bạn vừa đăng ký để bấm vào liên kết xác minh.
            </p>

            {{-- Thông báo thành công mặc định của Laravel --}}
            @if(session('status') == 'verification-link-sent')
                <div class="alert alert-success mb-3">
                    ✅ Đã gửi lại email xác minh! Vui lòng kiểm tra hòm thư của bạn.
                </div>
            @elseif(session('status'))
                <div class="alert alert-success mb-3">
                    ✅ {{ session('status') }}
                </div>
            @endif

            {{-- Thông báo thành công tùy chỉnh --}}
            @if(session('success'))
                <div class="alert alert-success mb-3">
                    ✅ {{ session('success') }}
                </div>
            @endif

            {{-- Thông báo cảnh báo --}}
            @if(session('warning'))
                <div class="alert alert-warning mb-3">
                    ⚠️ {{ session('warning') }}
                </div>
            @endif

            {{-- Thông báo lỗi khi không thể kết nối tới SMTP Server --}}
            @if(session('error'))
                <div class="alert alert-danger mb-3 text-start">
                    <div class="fw-bold mb-1">⚠️ Không thể gửi email xác minh lúc này:</div>
                    <small class="d-block text-break font-monospace p-2 rounded bg-light text-danger border border-danger-subtle">
                        {{ session('error') }}
                    </small>
                </div>
            @endif

            <div class="d-flex align-items-center justify-content-between mt-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Gửi lại email xác minh</button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary" type="submit">Đăng xuất</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection