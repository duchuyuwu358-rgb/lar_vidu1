@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 520px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3 mb-4">Đăng nhập</h1>

            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Gmail</label>
                    <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input class="form-control" type="password" id="password" name="password" required>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>

                <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
            </form>

            <p class="mt-3 mb-0">
                Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký bằng Gmail</a>
            </p>
        </div>
    </div>
</div>
@endsection