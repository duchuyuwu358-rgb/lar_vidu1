@extends('layouts.app')

@section('title', 'Đăng nhập - XFAN Store')

@section('content')
<div class="row justify-content-center w-100 m-0">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4 p-0">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                
                <!-- HEADER CHÀO MỪNG -->
                <div class="text-center mb-4">
                    <div class="avatar-icon bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-user-circle fs-2"></i>
                    </div>
                    <h2 class="fw-bold h4 mb-1">Đăng nhập tài khoản</h2>
                    <p class="text-muted small m-0">Nhập thông tin để tiếp tục trải nghiệm XFAN Store</p>
                </div>

                <!-- THÔNG BÁO THÀNH CÔNG (NẾU CÓ) -->
                @if(session('status'))
                    <div class="alert alert-success border-0 small rounded-3 mb-3 d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2 fs-6"></i>
                        <div>{{ session('status') }}</div>
                    </div>
                @endif

                <!-- THÔNG BÁO LỖI TỔNG HỢP -->
                @if($errors->any() && !$errors->has('email') && !$errors->has('password'))
                    <div class="alert alert-danger border-0 small rounded-3 mb-3 d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <!-- FORM ĐĂNG NHẬP THƯỜNG -->
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- GMAIL -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary" for="email">Địa chỉ Gmail</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input class="form-control bg-light border-start-0 rounded-end-3 @error('email') is-invalid @enderror" 
                                   type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   placeholder="" 
                                   required 
                                   autofocus 
                                   autocomplete="username">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- MẬT KHẨU -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary" for="password">Mật khẩu</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input class="form-control bg-light border-start-0 border-end-0 @error('password') is-invalid @enderror" 
                                   type="password" 
                                   id="password" 
                                   name="password" 
                                   placeholder="" 
                                   required 
                                   autocomplete="current-password">
                            <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePasswordBtn">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- GHI NHỚ & QUÊN MẬT KHẨU -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small text-secondary" for="remember">Ghi nhớ đăng nhập</label>
                        </div>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="small text-primary text-decoration-none fw-semibold">Quên mật khẩu?</a>
                        @endif
                    </div>

                    <!-- NÚT BẤM ĐĂNG NHẬP THƯỜNG -->
                    <button class="btn btn-primary w-100 py-2.5 fw-bold rounded-3 shadow-sm" type="submit">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                    </button>
                </form>

                <!-- ĐƯỜNG PHÂN CÁCH HOẶC -->
                <div class="position-relative text-center my-4">
                    <hr class="text-secondary opacity-25 m-0">
                    <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 small text-muted">HOẶC</span>
                </div>

                <!-- NÚT ĐĂNG NHẬP BẰNG GOOGLE -->
                <a href="{{ route('auth.google') }}" class="btn btn-outline-danger w-100 py-2.5 fw-semibold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                    <i class="fab fa-google fs-5"></i> Đăng nhập bằng Google
                </a>

                <!-- FOOTER ĐĂNG KÝ -->
                <div class="text-center mt-4 pt-3 border-top">
                    <span class="text-muted small">Chưa có tài khoản?</span>
                    <a href="{{ route('register') }}" class="fw-bold text-primary text-decoration-none small ms-1">Đăng ký ngay</a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                toggleIcon.classList.toggle('bi-eye');
                toggleIcon.classList.toggle('bi-eye-slash');
            });
        }
    });
</script>
@endpush