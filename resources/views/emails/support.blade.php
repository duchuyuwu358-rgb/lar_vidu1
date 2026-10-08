<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectTitle ?? 'Thông báo từ XFAN Store' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6; padding: 30px 0;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                    
                    <!-- Header Banner (Giống thiết kế Email Đơn hàng XFAN STORE) -->
                    <tr>
                        <td align="center" style="background-color: #0f172a; padding: 25px 20px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">
                                XFAN STORE
                            </h1>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 35px 30px; color: #1e293b; font-size: 15px; line-height: 1.6;">
                            <p style="margin-top: 0; font-size: 15px;">
                                Xin chào <strong>{{ $recipientName ?? 'Khách hàng' }}</strong>,
                            </p>

                            <!-- Subject / Notification Box -->
                            <div style="background-color: #f8fafc; border-left: 4px solid #2563eb; padding: 12px 16px; border-radius: 0 6px 6px 0; margin: 20px 0;">
                                <p style="margin: 0; font-weight: 700; color: #0f172a; font-size: 15px;">
                                    {{ $subjectTitle }}
                                </p>
                            </div>

                            <!-- Message Content -->
                            <div style="margin: 20px 0; color: #334155; font-size: 14px; white-space: pre-line;">
                                {!! nl2br(e($contentMessage)) !!}
                            </div>

                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 25px 0;">

                            <p style="margin-bottom: 0; font-size: 13px; color: #64748b;">
                                Đây là thông báo chính thức từ ban quản trị <strong>XFAN Store</strong>. Nếu có thắc mắc, vui lòng gửi phản hồi tại mục Hỗ trợ trên website.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 18px; border-top: 1px solid #f1f5f9; color: #94a3b8; font-size: 12px;">
                            <p style="margin: 0;">
                                © {{ date('Y') }} <strong>XFAN Store</strong>. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>