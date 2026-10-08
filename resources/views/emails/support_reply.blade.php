<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XFAN Store - Phản hồi yêu cầu hỗ trợ</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: Arial, sans-serif; color: #333333;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f6f9; padding: 30px 0;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); padding: 25px 30px; text-align: center; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: bold;">🌀 XFAN STORE</h1>
                            <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;">Hệ Thống Chăm Sóc & Hỗ Trợ Khách Hàng</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #0d6efd; font-size: 18px; margin-top: 0;">Kính gửi {{ $supportRequest->name }},</h2>
                            <p style="font-size: 15px; line-height: 1.6; color: #4a5568;">
                                Ban quản trị <strong>XFAN Store</strong> đã xử lý và gửi phản hồi cho yêu cầu hỗ trợ mã số <strong style="color: #0d6efd;">#{{ $supportRequest->id }}</strong> của bạn.
                            </p>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin: 20px 0; padding: 15px;">
                                <tr>
                                    <td style="font-size: 14px; color: #64748b; padding-bottom: 8px;">
                                        <strong>Tiêu đề thư gửi:</strong> {{ $supportRequest->subject }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; color: #64748b;">
                                        <strong>Nội dung câu hỏi của bạn:</strong>
                                        <div style="background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; margin-top: 5px; color: #334155; font-style: italic;">
                                            "{{ $supportRequest->message ?? $supportRequest->content }}"
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <div style="background-color: #f0f9ff; border-left: 4px solid #0284c7; padding: 16px; border-radius: 0 8px 8px 0; margin-bottom: 25px;">
                                <h3 style="margin: 0 0 8px 0; font-size: 15px; color: #0369a1;">
                                    💬 Lời nhắn từ Bộ phận Hỗ trợ:
                                </h3>
                                <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #0f172a; white-space: pre-line;">{!! e($replyMessage) !!}</p>
                            </div>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 10px 0 20px 0;">
                                        <a href="{{ url('/ho-tro/lich-su') }}" target="_blank" style="background-color: #0d6efd; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 25px; font-weight: bold; font-size: 14px; display: inline-block;">
                                            XEM CHI TIẾT TRÊN WEBSITE →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>