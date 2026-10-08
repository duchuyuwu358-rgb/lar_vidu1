<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailTitle ?? $subjectTitle ?? $subject ?? 'Thông báo từ XFAN Store' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f6f9; padding: 30px 0;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: left;">
                    
                    <!-- Header Bar -->
                    <tr>
                        <td align="center" style="background-color: #0f172a; color: #ffffff; padding: 24px;">
                            <h2 style="margin: 0; font-size: 22px; font-weight: bold; letter-spacing: 1px; color: #ffffff;">XFAN STORE</h2>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 13px; color: #cbd5e1;">Thông báo Hỗ trợ & Tri ân Khách hàng</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 28px 24px; color: #334155; line-height: 1.6; font-size: 15px;">
                            <h3 style="color: #0f172a; font-size: 18px; margin-top: 0; margin-bottom: 16px;">
                                {{ $mailTitle ?? $subjectTitle ?? $subject ?? 'Thông báo từ XFAN Store' }}
                            </h3>
                            
                            <div style="color: #334155; font-size: 15px; margin-bottom: 20px;">
                                {!! nl2br(e($mailContent ?? $contentMessage ?? $content ?? $message ?? '')) !!}
                            </div>

                            @if(!empty($couponCode))
                                <div style="background-color: #eff6ff; border: 2px dashed #2563eb; padding: 16px; text-align: center; border-radius: 8px; margin: 20px 0;">
                                    <div style="font-size: 14px; color: #1e293b; margin-bottom: 6px;">Mã ưu đãi dành riêng cho bạn:</div>
                                    <div style="font-size: 24px; font-weight: bold; color: #2563eb; letter-spacing: 2px;">{{ $couponCode }}</div>
                                    <small style="color: #64748b; display: block; margin-top: 6px;">Nhập mã này tại bước thanh toán để nhận giảm giá!</small>
                                </div>
                            @endif

                            <div style="text-align: center; margin-top: 25px; margin-bottom: 10px;">
                                <a href="{{ url('/storefront') }}" style="display: inline-block; background-color: #2563eb; color: #ffffff !important; text-decoration: none; padding: 12px 26px; border-radius: 6px; font-weight: bold; font-size: 14px;">Khám Phá Cửa Hàng Ngay</a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="text-align: center; padding: 20px; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; background-color: #ffffff;">
                            <p style="margin: 0 0 6px 0; color: #64748b;">Trân trọng,<br><strong style="color: #334155;">Đội ngũ XFAN Store</strong></p>
                            <p style="margin: 0; color: #94a3b8;">© {{ date('Y') }} XFAN Store. Tất cả các quyền được bảo lưu.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>