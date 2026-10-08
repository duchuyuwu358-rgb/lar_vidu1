@extends('layouts.app')

@section('title', 'Gửi Thư Hỗ Trợ Khách Hàng - XFAN Store')

@section('content')
<div class="container py-4" style="max-width: 800px;">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-dark text-white p-3">
            <h5 class="mb-0"><i class="bi bi-envelope-paper-fill text-warning me-2"></i> Gửi Thư Hỗ Trợ & Thông Báo Khách Hàng</h5>
        </div>
        <div class="card-body p-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('admin.promotion.send') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Người nhận thư:</label>
                    <select name="target" class="form-select" required>
                        <option value="all">📢 Gửi tất cả Khách hàng ({{ count($customers) }} tài khoản)</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->email }}">{{ $c->name }} ({{ $c->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Tiêu đề thư hỗ trợ:</label>
                    <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" placeholder="VD: Thư hỗ trợ / Thông báo từ Ban quản trị XFAN Store" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Tập tin / Tài liệu đính kèm (PDF, Word, Hình ảnh...):</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <div class="form-text text-muted">Hỗ trợ các định dạng: <b>.pdf, .doc, .docx, .jpg, .png</b> (Dung lượng tối đa 10MB).</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nội dung thư hỗ trợ:</label>
                    <textarea name="content" class="form-control" rows="6" placeholder="Nhập nội dung thư hỗ trợ gửi khách hàng..." required>{{ old('content') }}</textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('admin.portal') }}" class="btn btn-outline-secondary">Quay lại Dashboard</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send-fill me-1"></i> Gửi Thư Hỗ Trợ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection