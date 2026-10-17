<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DM Sans', Arial, sans-serif; color: #3d3d3d; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #b8847e; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #faf7f4; padding: 20px; border: 1px solid #efe8e3; }
        .footer { background-color: #efe8e3; padding: 15px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; }
        .order-code { font-size: 18px; font-weight: bold; color: #b8847e; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #efe8e3; }
        th { background-color: #e8c4c4; }
        .total { font-size: 18px; font-weight: bold; color: #b8847e; text-align: right; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Xác Nhận Đơn Hàng</h1>
        </div>
        <div class="content">
            <p>Xin chào {{ $order->user->name }},</p>
            
            <p>Cảm ơn bạn đã mua sắm tại SocialShop! Đơn hàng của bạn đã được xác nhận.</p>
            
            <p>Mã đơn hàng: <span class="order-code">#{{ $order->order_code }}</span></p>
            
            <table>
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'Sản phẩm đã xóa' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price, 0, ',', '.') }}₫</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <p style="text-align: right; margin: 10px 0 0;">
                Tạm tính: {{ number_format($order->subtotal, 0, ',', '.') }}₫<br>
                @if($order->discount > 0)Giảm giá: -{{ number_format($order->discount, 0, ',', '.') }}₫<br>@endif
                Phí vận chuyển: {{ $order->shipping_fee > 0 ? number_format($order->shipping_fee, 0, ',', '.').'₫' : 'Miễn phí' }}<br>
                @if($order->tax_amount > 0)Thuế VAT ({{ rtrim(rtrim(number_format($order->tax_rate, 2, ',', ''), '0'), ',') }}%): {{ number_format($order->tax_amount, 0, ',', '.') }}₫@endif
            </p>
            <div class="total">
                Tổng cộng: {{ number_format($order->total, 0, ',', '.') }}₫
            </div>
            
            <p>Phương thức thanh toán: {{ $order->payment_method === 'cod' ? 'Thanh toán khi nhận hàng' : 'VNPay' }}</p>
            
            <p>Chúng tôi sẽ gửi thông báo khi đơn hàng được giao cho đơn vị vận chuyển.</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} SocialShop - Fashion & Lifestyle</p>
        </div>
    </div>
</body>
</html>
