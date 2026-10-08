<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px; }
        .email-container { max-width: 600px; background: #ffffff; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .email-header { background-color: #2563eb; color: #ffffff; padding: 24px; text-align: center; }
        .email-body { padding: 24px; color: #334155; line-height: 1.6; }
        .coupon-box { background: #eff6ff; border: 2px dashed #2563eb; padding: 16px; text-align: center; border-radius: 8px; margin: 20px 0; }
        .coupon-code { font-size: 24px; font-weight: bold; color: #2563eb; letter-spacing: 2px; }
        .email-footer { text-align: center; padding: 16px; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        .btn-shop { display: inline-block; background-color: #2563eb; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h2 style="margin: 0;">XFAN STORE</h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Thông báo Hỗ trợ & Tri ân Khách hàng</p>
        </div>
        <div class="email-body">
            <h3>{{ $mailTitle }}</h3>
            <div>{!! nl2br(e($mailContent)) !!}</div>

            @if(!empty($couponCode))
                <div class="coupon-box">
                    <div>Mã ưu đãi dành riêng cho bạn:</div>
                    <div class="coupon-code">{{ $couponCode }}</div>
                    <small style="color: #64748b;">Nhập mã này tại bước thanh toán để nhận giảm giá!</small>
                </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ url('/storefront') }}" class="btn-shop">Khám Phá Cửa Hàng Ngay</a>
            </div>
        </div>
        <div class="email-footer">
            <p>Trân trọng,<br><strong>Đội ngũ XFAN Store</strong></p>
            <p>© 2026 XFAN Store. Tất cả các quyền được bảo lưu.</p>
        </div>
    </div>
</body>
</html>