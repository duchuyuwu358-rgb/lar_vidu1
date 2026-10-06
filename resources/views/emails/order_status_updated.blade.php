<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .header { background-color: #0d1b2a; padding: 20px; text-align: center; color: #ffffff; }
        .content { padding: 25px; }
        .status-box { background-color: #f8f9fa; border: 1px dashed #ced4da; border-radius: 8px; text-align: center; padding: 15px; margin: 20px 0; }
        .badge { display: inline-block; padding: 6px 16px; border-radius: 50px; font-weight: bold; font-size: 14px; background-color: #fff3cd; color: #856404; text-transform: uppercase; }
        .badge-paid { background-color: #d4edda; color: #155724; }
        .badge-processing { background-color: #cce5ff; color: #004085; }
        .section-title { font-size: 16px; font-weight: bold; border-bottom: 2px solid #0d6efd; padding-bottom: 8px; margin-bottom: 15px; text-transform: uppercase; color: #111; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 8px 0; font-size: 14px; }
        .info-label { color: #6c757d; width: 160px; }
        .info-value { font-weight: 600; color: #212529; }
        .highlight-code { color: #0d6efd; font-weight: bold; }
        .trans-code { background-color: #e9ecef; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-size: 13px; }
        .sequence-tag { background-color: #e7f1ff; color: #0c63e4; padding: 2px 10px; border-radius: 12px; font-size: 13px; font-weight: bold; }
        .product-table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px; }
        .product-table th { background-color: #f8f9fa; text-align: left; padding: 10px; border-bottom: 1px solid #dee2e6; color: #6c757d; }
        .product-table td { padding: 10px; border-bottom: 1px solid #f1f1f1; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #6c757d; background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin:0; font-size: 20px;">XFAN STORE</h2>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $order->name ?? $order->user->name ?? 'Khách hàng' }}</strong>,</p>
            <p>Đơn hàng <strong class="highlight-code">{{ $orderCode }}</strong> tại <strong>XFAN Store</strong> đã có cập nhật trạng thái mới.</p>

            <!-- Trạng thái -->
            <div class="status-box">
                <div style="font-size: 12px; color: #6c757d; margin-bottom: 5px; text-transform: uppercase;">Trạng thái hiện tại:</div>
                <span class="badge {{ strtolower($order->payment_status) === 'paid' ? 'badge-paid' : 'badge-processing' }}">
                    {{ mb_strtoupper($order->status_label ?? $order->status ?? 'PROCESSING') }}
                </span>
            </div>

            <!-- Thông tin đơn hàng -->
            <div class="section-title">THÔNG TIN ĐƠN HÀNG</div>
            <table class="info-table">
                <tr>
                    <td class="info-label">Mã đơn hàng:</td>
                    <td class="info-value highlight-code">{{ $orderCode }}</td>
                </tr>
                @if($order->momo_transaction_id || $order->transaction_id)
                <tr>
                    <td class="info-label">Mã giao dịch MoMo:</td>
                    <td class="info-value">
                        <span class="trans-code">{{ $order->momo_transaction_id ?? $order->transaction_id }}</span>
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="info-label">Thứ tự mua hàng:</td>
                    <td class="info-value">
                        <span class="sequence-tag">#{{ $orderSequence ?? 1 }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="info-label">Thời gian đặt:</td>
                    <td class="info-value">{{ $order->created_at ? $order->created_at->format('H:i - d/m/Y') : 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Người nhận:</td>
                    <td class="info-value">{{ $order->name ?? $order->user->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Số điện thoại:</td>
                    <td class="info-value">{{ $order->phone ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Địa chỉ nhận hàng:</td>
                    <td class="info-value">{{ $order->address ?? 'N/A' }}</td>
                </tr>
            </table>

            <!-- Danh sách sản phẩm -->
            <div class="section-title">DANH SÁCH SẢN PHẨM</div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>SẢN PHẨM</th>
                        <th style="text-align: center;">SL</th>
                        <th style="text-align: right;">ĐƠN GIÁ</th>
                        <th style="text-align: right;">THÀNH TIỀN</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $items = ($order->orderItems && $order->orderItems->isNotEmpty()) ? $order->orderItems : ($order->items ?? collect());
                    @endphp
                    @foreach($items as $item)
                        @php
                            $pName = $item->hood->name ?? $item->product->name ?? $item->product_name ?? 'Sản phẩm';
                            $qty = $item->quantity ?? 1;
                            $price = $item->price ?? 0;
                        @endphp
                        <tr>
                            <td><strong>{{ $pName }}</strong></td>
                            <td style="text-align: center;">{{ $qty }}</td>
                            <td style="text-align: right;">{{ number_format($price) }}đ</td>
                            <td style="text-align: right; font-weight: bold;">{{ number_format($price * $qty) }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; padding-top: 15px; font-weight: bold;">TỔNG TIỀN:</td>
                        <td style="text-align: right; padding-top: 15px; font-weight: bold; color: #d9534f; font-size: 16px;">
                            {{ number_format($order->total_price ?? $order->total_amount ?? 0) }}đ
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="footer">
            <p>Cảm ơn bạn đã mua sắm tại <strong>XFAN Store</strong>!</p>
        </div>
    </div>
</body>
</html>