<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn đơn hàng #{{ $order->order_code }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4efe9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #2c2a28; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4efe9; padding: 30px 0; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 650px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); border: 1px solid #efe8e3; }
        .header { background: linear-gradient(135deg, #2c2a28 0%, #3d3a37 100%); color: #ffffff; padding: 32px 30px; text-align: center; }
        .brand-title { font-size: 26px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin: 0; color: #f7ede6; }
        .brand-sub { font-size: 12px; letter-spacing: 3px; text-transform: uppercase; color: #b8847e; margin-top: 5px; margin-bottom: 0; font-weight: 600; }
        .invoice-badge { display: inline-block; margin-top: 18px; padding: 6px 18px; background-color: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 20px; font-size: 13px; font-weight: 600; letter-spacing: 0.5px; }
        .content { padding: 32px 30px; }
        .greeting { font-size: 16px; color: #2c2a28; margin-top: 0; margin-bottom: 20px; line-height: 1.5; }
        .meta-card { background-color: #faf7f4; border: 1px solid #efe8e3; border-radius: 12px; padding: 20px; margin-bottom: 28px; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 6px 0; vertical-align: top; font-size: 14px; }
        .meta-label { color: #8a827c; width: 38%; }
        .meta-value { color: #2c2a28; font-weight: 600; }
        .order-code-highlight { color: #b8847e; font-weight: 700; font-size: 15px; }
        .status-pill { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .status-paid { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-pending { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .section-title { font-size: 15px; font-weight: 700; color: #2c2a28; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 12px 0; border-bottom: 2px solid #b8847e; padding-bottom: 6px; display: inline-block; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .items-table th { background-color: #f5eeea; color: #6e6762; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 10px; text-align: left; border-bottom: 1px solid #efe8e3; }
        .items-table td { padding: 14px 10px; border-bottom: 1px solid #efe8e3; font-size: 14px; vertical-align: middle; }
        .item-name { font-weight: 600; color: #2c2a28; margin: 0 0 3px 0; }
        .item-variant { font-size: 12px; color: #8a827c; margin: 0; }
        .summary-card { background-color: #faf7f4; border: 1px solid #efe8e3; border-radius: 12px; padding: 20px; margin-bottom: 28px; width: 100%; box-sizing: border-box; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { padding: 6px 0; font-size: 14px; }
        .summary-label { color: #6e6762; }
        .summary-value { text-align: right; color: #2c2a28; font-weight: 600; }
        .summary-discount { color: #dc2626; }
        .summary-divider { border-top: 1px dashed #d9cec7; padding-top: 12px; margin-top: 6px; }
        .total-row td { font-size: 16px; font-weight: 700; color: #2c2a28; }
        .total-price { font-size: 20px !important; color: #b8847e !important; font-weight: 800 !important; }
        .cta-container { text-align: center; margin: 30px 0 10px 0; }
        .cta-button { display: inline-block; background-color: #b8847e; color: #ffffff !important; text-decoration: none; padding: 14px 34px; border-radius: 30px; font-weight: 700; font-size: 14px; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(184, 132, 126, 0.35); text-transform: uppercase; }
        .footer { background-color: #faf7f4; border-top: 1px solid #efe8e3; padding: 24px 30px; text-align: center; font-size: 12px; color: #8a827c; line-height: 1.6; }
        .footer a { color: #b8847e; text-decoration: none; }
        .policy-box { background-color: #fff9f8; border: 1px solid #f2dede; border-radius: 8px; padding: 12px 16px; font-size: 12px; color: #7f5f5c; margin: 20px 0; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="main" align="center" cellpadding="0" cellspacing="0">
            <!-- Header -->
            <tr>
                <td class="header">
                    <p class="brand-title">SocialShop</p>
                    <p class="brand-sub">Boutique Fashion & Lifestyle</p>
                    <div class="invoice-badge">
                        HÓA ĐƠN BÁN HÀNG & BIÊN LAI ĐIỆN TỬ
                    </div>
                </td>
            </tr>

            <!-- Content -->
            <tr>
                <td class="content">
                    <p class="greeting">
                        Kính chào quý khách <strong>{{ $order->shipping_name ?? $order->user->name }}</strong>,<br>
                        Cảm ơn bạn đã lựa chọn mua sắm tại <strong>SocialShop</strong>! Đơn hàng của bạn đã được tiếp nhận và xử lý với thông tin chi tiết dưới đây:
                    </p>

                    <!-- Metadata Card -->
                    <div class="meta-card">
                        <table class="meta-table">
                            <tr>
                                <td class="meta-label">Mã đơn hàng:</td>
                                <td class="meta-value"><span class="order-code-highlight">#{{ $order->order_code }}</span></td>
                            </tr>
                            <tr>
                                <td class="meta-label">Thời gian lập:</td>
                                <td class="meta-value">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Phương thức thanh toán:</td>
                                <td class="meta-value">
                                    {{ $order->payment_method === 'cod' ? 'Thanh toán khi nhận hàng (COD)' : 'Cổng thanh toán điện tử VNPay' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-label">Trạng thái thanh toán:</td>
                                <td class="meta-value">
                                    @if($order->payment_status === 'paid')
                                        <span class="status-pill status-paid">✓ ĐÃ THANH TOÁN (VNPAY)</span>
                                    @else
                                        <span class="status-pill status-pending">🕒 THANH TOÁN KHI NHẬN HÀNG</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-label">Người nhận hàng:</td>
                                <td class="meta-value">{{ $order->shipping_name }} — {{ $order->shipping_phone }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Địa chỉ giao hàng:</td>
                                <td class="meta-value">{{ $order->shipping_address }}{{ $order->shipping_province ? ', ' . $order->shipping_province : '' }}</td>
                            </tr>
                            @if(!empty($order->note))
                            <tr>
                                <td class="meta-label">Ghi chú giao hàng:</td>
                                <td class="meta-value" style="font-style: italic; color: #6e6762;">{{ $order->note }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>

                    <!-- Items Table -->
                    <div class="section-title">Chi Tiết Sản Phẩm</div>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th style="text-align: center;">SL</th>
                                <th style="text-align: right;">Đơn giá</th>
                                <th style="text-align: right;">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <p class="item-name">{{ $item->product_name ?? $item->product?->name ?? 'Sản phẩm đã gỡ' }}</p>
                                    @if($item->size || $item->color)
                                        <p class="item-variant">Phân loại: {{ $item->size ? 'Size ' . $item->size : '' }}{{ $item->size && $item->color ? ' / ' : '' }}{{ $item->color ? 'Màu ' . $item->color : '' }}</p>
                                    @endif
                                </td>
                                <td style="text-align: center; font-weight: 600;">{{ $item->quantity }}</td>
                                <td style="text-align: right;">{{ number_format($item->price, 0, ',', '.') }}₫</td>
                                <td style="text-align: right; font-weight: 600;">{{ number_format($item->subtotal ?? ($item->price * $item->quantity), 0, ',', '.') }}₫</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Summary Card -->
                    <div class="summary-card">
                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">Tạm tính:</td>
                                <td class="summary-value">{{ number_format($order->subtotal, 0, ',', '.') }}₫</td>
                            </tr>
                            @if($order->discount > 0)
                            <tr>
                                <td class="summary-label">Giảm giá ưu đãi:</td>
                                <td class="summary-value summary-discount">-{{ number_format($order->discount, 0, ',', '.') }}₫</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="summary-label">Phí vận chuyển:</td>
                                <td class="summary-value">
                                    {{ $order->shipping_fee > 0 ? number_format($order->shipping_fee, 0, ',', '.') . '₫' : 'Miễn phí' }}
                                </td>
                            </tr>
                            @if($order->tax_amount > 0)
                            <tr>
                                <td class="summary-label">Thuế VAT ({{ rtrim(rtrim(number_format($order->tax_rate, 2, ',', ''), '0'), ',') }}%):</td>
                                <td class="summary-value">{{ number_format($order->tax_amount, 0, ',', '.') }}₫</td>
                            </tr>
                            @endif
                            <tr class="summary-divider total-row">
                                <td style="padding-top: 10px;">TỔNG CỘNG THANH TOÁN:</td>
                                <td class="summary-value total-price" style="padding-top: 10px;">
                                    {{ number_format($order->total, 0, ',', '.') }}₫
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Policy Box -->
                    <div class="policy-box">
                        🛡️ <strong>Chính sách đổi trả minh bạch:</strong> Quý khách được quyền kiểm tra hàng khi nhận và đổi trả miễn phí trong vòng <strong>7 ngày</strong> nếu sản phẩm chưa qua sử dụng, còn nguyên tem mác.
                    </div>

                    <!-- CTA Button -->
                    <div class="cta-container">
                        <a href="{{ route('orders.show', $order->id) }}" class="cta-button">
                            Tra Cứu Chi Tiết Đơn Hàng →
                        </a>
                    </div>
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td class="footer">
                    <p style="margin: 0 0 6px 0; font-weight: 700; color: #2c2a28;">SocialShop — Fashion & Lifestyle Boutique</p>
                    <p style="margin: 0 0 6px 0;">Hotline CSKH: <strong>077123456</strong> (8:00 - 21:00) | Email hỗ trợ: <a href="mailto:support@socialshop.vn">support@socialshop.vn</a></p>
                    <p style="margin: 0 0 10px 0;">Địa chỉ: Hà Nội, Việt Nam</p>
                    <p style="margin: 0; font-size: 11px; color: #a39c96;">Hóa đơn điện tử được khởi tạo tự động theo quy định thương mại điện tử và Luật Giao dịch điện tử số 20/2023/QH15.</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
