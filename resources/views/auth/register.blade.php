@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 520px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3 mb-4">Đăng ký tài khoản</h1>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="name">Họ tên</label>
                    <input class="form-control" type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Gmail</label>
                    <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="ban@gmail.com" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="8" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Nhập lại mật khẩu</label>
                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="8" required>
                </div>

                <button class="btn btn-primary w-100" type="submit">Đăng ký</button>
            </form>

            <p class="mt-3 mb-0">
                Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>
@endsection