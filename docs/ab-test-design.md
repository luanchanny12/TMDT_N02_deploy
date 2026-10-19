# Thiết kế A/B test: khối "Mua sắm an tâm" ở trang giỏ hàng

## 1. Vấn đề

Phễu trên trang Admin → Phân tích cho thấy bước **Thêm vào giỏ → Bắt đầu thanh toán** là điểm rơi lớn. Lý do thường gặp với hàng thời trang mua online: lo không vừa size, lo phí ship, lo khó đổi trả.

## 2. Giả thuyết

Hiện trang giỏ hàng chỉ có dòng chữ nhỏ màu xám "Bảo mật 100% • Đổi trả 7 ngày" nằm **dưới** nút "Tiến hành thanh toán", dễ bị bỏ qua.

> Nếu thay dòng chữ nhỏ đó bằng một khối "Mua sắm an tâm" nổi bật đặt **ngay trên** nút thanh toán, nói rõ *Đổi size, đổi mẫu trong 7 ngày · Miễn phí giao hàng cho đơn từ 300.000₫ (còn thiếu X₫) · Thanh toán khi nhận hàng*, thì tỷ lệ khách từ giỏ hàng bấm sang thanh toán sẽ tăng, vì nỗi lo về size và đổi trả được giải đáp ngay lúc khách ra quyết định.

## 3. Phương án

| | Nhóm A (đối chứng) | Nhóm B (thử nghiệm) |
|---|---|---|
| Trang giỏ hàng | Như hiện tại (huy hiệu nhỏ dưới nút thanh toán) | Khối "Mua sắm an tâm" 3 dòng đặt trên nút thanh toán, có link Chính sách đổi trả; bỏ dòng huy hiệu nhỏ |
| Các trang khác | Giữ nguyên | Giữ nguyên |

Chỉ thay đổi **một** yếu tố để quy được kết quả cho đúng thay đổi đó.

## 4. Chia nhóm

- Đơn vị chia: phiên (`analytics_events.session_hash`), chia 50/50 theo `crc32(session_hash) % 2`, nên một phiên luôn thấy cùng một phương án.
- Ghi phương án vào sự kiện `add_to_cart` và `begin_checkout` (thêm trường `variant = A|B`) để tính theo nhóm.
- Loại khỏi phân tích: phiên của quản trị viên (hệ thống đã không ghi) và dữ liệu mẫu (`is_demo = true`).

## 5. Chỉ số

| Loại | Chỉ số | Công thức |
|---|---|---|
| **Chính** | CVR giỏ hàng → thanh toán | Số phiên có `begin_checkout` / Số phiên có `add_to_cart` |
| Phụ | CVR giỏ hàng → mua | Số phiên có `purchase` / Số phiên có `add_to_cart` |
| Phụ | Giá trị đơn trung bình (AOV) | Doanh thu / Số đơn |
| Bảo vệ | Tỷ lệ hủy / trả hàng trong 14 ngày | Không được tăng quá 1 điểm % so với nhóm A |

## 6. Cỡ mẫu

Giả định: CVR giỏ → thanh toán hiện tại p₁ = 35%; mức cải thiện tối thiểu muốn phát hiện là +5 điểm % (p₂ = 40%); α = 0,05 (hai phía); power = 80%.

```text
n mỗi nhóm = (z(1−α/2) + z(1−β))² × [p₁(1−p₁) + p₂(1−p₂)] / (p₂ − p₁)²
           = (1,96 + 0,84)² × (0,35×0,65 + 0,40×0,60) / 0,05²
           = 7,84 × 0,4675 / 0,0025
           ≈ 1.467 phiên có thêm vào giỏ mỗi nhóm  (tổng ≈ 2.934)
```

Thời gian chạy = 2.934 / số phiên thêm giỏ mỗi ngày (xem bảng phễu). Ví dụ 100 phiên/ngày thì cần khoảng 30 ngày. Dù đủ mẫu sớm vẫn chạy **tối thiểu 14 ngày** để bao trọn hai chu kỳ tuần (cuối tuần mua sắm khác ngày thường).

## 7. Điều kiện dừng và ra quyết định

1. **Không xem kết quả để dừng sớm** trước khi đủ cỡ mẫu và 14 ngày (tránh "peeking" làm tăng sai lầm loại I).
2. Dừng khẩn cấp nếu chỉ số bảo vệ xấu đi rõ rệt hoặc phát sinh lỗi hiển thị/thanh toán ở nhóm B.
3. Khi đủ mẫu: kiểm định z hai tỷ lệ.
   - p < 0,05 và B > A → áp dụng B cho toàn bộ khách.
   - p ≥ 0,05 → không đủ bằng chứng; giữ A, ghi nhận kết quả và thử giả thuyết tiếp theo (ví dụ bảng hướng dẫn chọn size ở trang sản phẩm).
4. Ghi lại: ngày bắt đầu/kết thúc, n mỗi nhóm, CVR mỗi nhóm, khoảng tin cậy 95% của chênh lệch, quyết định.

## 8. Mẫu báo cáo kết quả

| Nhóm | Phiên thêm giỏ | Phiên bắt đầu thanh toán | CVR | Đơn | AOV |
|---|---|---|---|---|---|
| A | | | | | |
| B | | | | | |
| Chênh lệch (B − A) | | | | khoảng tin cậy 95%: | p-value: |
