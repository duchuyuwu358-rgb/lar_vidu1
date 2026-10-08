@extends('layouts.app')

@section('title', 'Đăng ký tài khoản - XFAN Store')

@section('content')
<div class="row justify-content-center w-100 m-0">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4 p-0">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                
                <!-- HEADER CHÀO MỪNG -->
                <div class="text-center mb-4">
                    <div class="avatar-icon bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-user-plus fs-3"></i>
                    </div>
                    <h2 class="fw-bold h4 mb-1">Đăng ký tài khoản</h2>
                    <p class="text-muted small m-0">Tạo tài khoản để trải nghiệm dịch vụ tại XFAN Store</p>
                </div>

                <!-- THÔNG BÁO LỖI TỔNG HỢP -->
                @if($errors->any() && !$errors->has('name') && !$errors->has('email') && !$errors->has('password') && !$errors->has('password_confirmation'))
                    <div class="alert alert-danger border-0 small rounded-3 mb-3 d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <!-- FORM ĐĂNG KÝ THƯỜNG -->
                <form method="POST" action="{{ route('register.store') }}" novalidate>
                    @csrf

                    <!-- HỌ TÊN -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary" for="name">Họ và tên</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                            <input class="form-control bg-light border-start-0 rounded-end-3 @error('name') is-invalid @enderror" 
                                   type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="" 
                                   required 
                                   autofocus 
                                   autocomplete="name">
                        </div>
                        @error('name')
                            <div class="text-danger small mt-1 ms-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- GMAIL -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary" for="email">Địa chỉ Gmail</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input class="form-control bg-light border-start-0 rounded-end-3 @error('email') is-invalid @enderror" 
                                   type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   placeholder="" 
                                   required 
                                   autocomplete="email">
                        </div>
                        @error('email')
                            <div class="text-danger small mt-1 ms-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- MẬT KHẨU -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary" for="password">Mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input class="form-control bg-light border-start-0 border-end-0 @error('password') is-invalid @enderror" 
                                   type="password" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Tối thiểu 8 ký tự" 
                                   required 
                                   autocomplete="new-password">
                            <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePasswordBtn">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="text-danger small mt-1 ms-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- NHẬP LẠI MẬT KHẨU -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-secondary" for="password_confirmation">Nhập lại mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-lock"></i></span>
                            <input class="form-control bg-light border-start-0 border-end-0 @error('password_confirmation') is-invalid @enderror" 
                                   type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   placeholder="Xác nhận lại mật khẩu" 
                                   required 
                                   autocomplete="new-password">
                            <button class="btn btn-light border border-start-0 text-muted" type="button" id="toggleConfirmPasswordBtn">
                                <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <div class="text-danger small mt-1 ms-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- NÚT ĐĂNG KÝ THƯỜNG -->
                    <button class="btn btn-primary w-100 py-2.5 fw-bold rounded-3 shadow-sm" type="submit">
                        <i class="bi bi-person-plus-fill me-1"></i> Đăng ký
                    </button>
                </form>

                <!-- ĐƯỜNG PHÂN CÁCH HOẶC -->
                <div class="position-relative text-center my-4">
                    <hr class="text-secondary opacity-25 m-0">
                    <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 small text-muted">HOẶC</span>
                </div>

                <!-- NÚT ĐĂNG KÝ BẰNG GOOGLE -->
                <a href="{{ route('auth.google') }}" class="btn btn-outline-danger w-100 py-2.5 fw-semibold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                    <i class="fab fa-google fs-5"></i> Đăng ký bằng Google
                </a>

                <!-- FOOTER CHUYỂN SANG ĐĂNG NHẬP -->
                <div class="text-center mt-4 pt-3 border-top">
                    <span class="text-muted small">Đã có tài khoản?</span>
                    <a href="{{ route('login') }}" class="fw-bold text-primary text-decoration-none small ms-1">Đăng nhập ngay</a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function setupToggle(btnId, inputId, iconId) {
            const btn = document.getElementById(btnId);
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (btn && input && icon) {
                btn.addEventListener('click', function () {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    icon.classList.toggle('bi-eye');
                    icon.classList.toggle('bi-eye-slash');
                });
            }
        }

        setupToggle('togglePasswordBtn', 'password', 'togglePasswordIcon');
        setupToggle('toggleConfirmPasswordBtn', 'password_confirmation', 'toggleConfirmPasswordIcon');
    });
</script>
@endpush