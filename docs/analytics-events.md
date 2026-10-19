# Sự kiện đo lường (TH13)

Website tự ghi sự kiện vào bảng `analytics_events` (không dùng cookie quảng cáo bên thứ ba, không lưu IP). Tên sự kiện nội bộ ánh xạ sang tên chuẩn GA4 như sau:

| Bước phễu | Sự kiện nội bộ (`type`) | Tên GA4 tương ứng | Ghi ở đâu |
|---|---|---|---|
| Truy cập | `visit` | `session_start` | `TrackVisit` middleware, một lần đầu mỗi phiên hoặc khi khách quay lại qua chiến dịch khác |
| Xem sản phẩm | `product_view` | `view_item` | `ProductController@show` |
| Thêm vào giỏ | `add_to_cart` | `add_to_cart` | `CartController@add` |
| Bắt đầu thanh toán | `begin_checkout` | `begin_checkout` | `OrderController@review`, sau khi khách gửi thông tin giao hàng hợp lệ |
| Mua hàng | `purchase` | `purchase` | `OrderService::placeOrder`, kèm `order_id` và `value` (không tính đơn đã hủy) |

## Nguồn lưu lượng

Mỗi sự kiện mang `campaign_id` và `source` lấy từ UTM (`utm_source`, `utm_campaign`) hoặc trang giới thiệu. Trang **Admin → Phân tích** chia thành 5 kênh:

| Kênh | Quy tắc |
|---|---|
| Quảng cáo trả phí | Có `campaign_id` (link UTM tạo trong Admin → Chiến dịch) |
| Tìm kiếm tự nhiên | `source` ∈ google, bing, coccoc, yahoo, duckduckgo |
| Mạng xã hội | `source` ∈ facebook, instagram, tiktok, youtube, zalo, messenger, twitter, x, threads |
| Giới thiệu | Mọi `source` khác (website khác) |
| Trực tiếp | Không có `source` |

## Chỉ số trên trang Phân tích

- **Chẩn đoán điểm rơi:** Số đơn tăng thêm ≈ Lượng vào bước × Δ tỷ lệ chuyển tiếp (+5 điểm %) × Tỷ lệ chuyển đổi các bước sau. Bước có số đơn tăng thêm lớn nhất được đánh dấu "Ưu tiên can thiệp".
- **Biên lợi nhuận gộp** = (Doanh thu hàng − Giá vốn) / Doanh thu hàng, dùng giá vốn đã chụp vào từng dòng đơn (`order_items.cost_price`).
- **ROAS hòa vốn** = 1 / Biên gộp. Bảng chiến dịch tô đỏ chiến dịch có ROAS thấp hơn ngưỡng này.
- **LTV** = AOV × Biên gộp × Số đơn bình quân mỗi khách.
- **CAC** = Tổng chi phí quảng cáo / Số khách có đơn đầu tiên đến từ chiến dịch.
- **LTV/CAC** so với mục tiêu ≥ 3.
- **CPC hòa vốn** = AOV × Biên gộp × CR; **CPC tối đa** = LTV × CR / 3.
