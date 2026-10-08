<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectTitle ?? 'Thông báo từ XFAN Store' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; -webkit-font-smoothing: antialiased;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #f4f6f9; padding: 40px 0;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);">
                    
                    <!-- Header Bar -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 30px 20px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 1px;">
                                ⚡ XFAN STORE
                            </h1>
                            <p style="color: #94a3b8; margin: 5px 0 0 0; font-size: 13px; font-weight: 400;">
                                Hỗ Trợ & Khách Hàng
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 40px 35px; color: #334155; font-size: 15px; line-height: 1.6;">
                            <p style="margin-top: 0; font-size: 16px;">
                                Xin chào <strong>{{ $recipientName ?? 'Khách hàng' }}</strong>,
                            </p>

                            <!-- Subject Highlight Box -->
                            <div style="background-color: #f8fafc; border-left: 4px solid #2563eb; padding: 15px 20px; border-radius: 0 8px 8px 0; margin: 20px 0;">
                                <h3 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 600;">
                                    {{ $subjectTitle }}
                                </h3>
                            </div>

                            <!-- Message Content -->
                            <div style="margin: 25px 0; color: #475569; white-space: pre-line;">
                                {!! nl2br(e($contentMessage)) !!}
                            </div>

                            <!-- CTA / Divider -->
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;">

                            <p style="margin-bottom: 0; font-size: 14px; color: #64748b;">
                                Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ lại với chúng tôi qua hệ thống Hỗ trợ trên trang web.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 20px; border-top: 1px solid #f1f5f9; color: #94a3b8; font-size: 12px;">
                            <p style="margin: 0 0 5px 0;">
                                © {{ date('Y') }} <strong>XFAN Store</strong>. All rights reserved.
                            </p>
                            <p style="margin: 0;">
                                Đây là email tự động, vui lòng không trả lời trực tiếp vào email này.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>