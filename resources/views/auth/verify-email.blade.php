@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 620px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3 mb-3">Xác minh Gmail</h1>
            
            <p class="text-muted">
                Hãy kiểm tra hộp thư đến (hoặc thư mục Spam) của địa chỉ Gmail bạn vừa đăng ký để bấm vào liên kết xác minh.
            </p>

            @if(session('status'))
                <div class="alert alert-success mb-3">
                    {{ session('status') }}
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