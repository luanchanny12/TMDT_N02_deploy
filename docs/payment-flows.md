# Kiểm thử 6 luồng thanh toán

Phạm vi: cổng VNPay Sandbox và COD trên SocialShop. Mỗi luồng có kịch bản thao tác tay trên sandbox và một bài test tự động tương ứng trong `tests/Feature/PaymentFlowsTest.php`.

Chạy toàn bộ minh chứng:

```sh
php artisan test --filter PaymentFlowsTest
```

## Cơ chế dùng chung

| Cơ chế | Vị trí | Tác dụng |
|---|---|---|
| Mã tham chiếu giao dịch `transaction_code` (= `vnp_TxnRef`), duy nhất cho mỗi lần thanh toán | `payments.transaction_code` | Khóa idempotency: mọi kết quả VNPay gửi về đều tra theo mã này |
| `lockForUpdate()` trên dòng `payments` | `PaymentService::processVNPayIpn` | Hai callback tới cùng lúc không thể cùng ghi nhận "đã thanh toán" |
| Chỉ xử lý khi `payments.status = pending` | như trên | Kết quả gửi lặp hoặc tới muộn chỉ nhận về `RspCode 02`, không ghi lại |
| Kiểm chữ ký HMAC-SHA512 và số tiền (`vnp_Amount = amount × 100`) | `VNPayService::verifyCallback`, `PaymentService` | Chặn callback giả mạo hoặc bị sửa số tiền |
| Báo giá khóa 15 phút + `expected_total` | `OrderController::confirm`, `OrderService::placeOrder` | Khách chỉ trả đúng số tiền đã thấy ở bước xác nhận |
| Hết hạn thanh toán sau `VNPAY_EXPIRE_MINUTES` (mặc định 30 phút) | `orders:expire-unpaid` chạy mỗi 5 phút | Đơn bỏ dở được hủy, nhả tồn kho, trả lượt mã giảm giá |
| Trả sản phẩm về giỏ khi thanh toán thất bại / hết hạn | `CartService::restoreFromOrder` | Khách hủy giữa chừng thì giỏ hàng còn nguyên để đặt lại |

## Các luồng

| # | Luồng | Thao tác trên sandbox | Kết quả mong đợi | Test tự động |
|---|---|---|---|---|
| 1 | COD thành công | Giỏ hàng → Thanh toán → chọn COD → Xác nhận | Đơn `pending`, tồn kho trừ đúng số lượng, giỏ hàng trống, email xác nhận | `test_flow_1_cod_order_is_placed_and_cart_cleared` |
| 2 | VNPay thành công | Chọn VNPay → thẻ NCB `9704198526191432198`, tên `NGUYEN VAN A`, ngày `07/15`, OTP `123456` | Về trang đặt hàng thành công; `payments.status = paid`, `orders.payment_status = paid` | `test_flow_2_vnpay_success_marks_order_paid` |
| 3 | Khách hủy trên trang VNPay | Ở trang VNPay bấm "Hủy giao dịch" (mã 24) | Thông báo giao dịch không thành công; đơn `cancelled`, tồn kho hoàn lại, **sản phẩm trở lại giỏ hàng** | `test_flow_3_customer_cancels_on_vnpay_restores_stock_and_cart` |
| 4 | Đóng trang giữa chừng | Ở trang VNPay đóng tab, chờ quá 30 phút (hoặc chạy `php artisan orders:expire-unpaid --minutes=0`) | Đơn tự hủy, payment `failed`, tồn kho hoàn lại, **giỏ hàng còn nguyên** | `test_flow_4_abandoned_payment_page_expires_and_cart_returns` |
| 5 | **Kết quả tới muộn / gửi lặp (idempotency)** | Thanh toán thành công, sau đó gọi lại URL IPN đã nhận (VNPay gửi lặp) | Lần đầu ghi nhận `paid` một lần duy nhất; các lần sau trả `RspCode 02`, không ghi trùng, không trừ kho lần hai. Nếu kết quả thành công tới **sau** khi đơn đã hết hạn: không mở lại đơn, ghi `payment_refund_required` và báo admin hoàn tiền | `test_flow_5_late_and_repeated_results_are_idempotent` |
| 6 | Callback giả mạo / sai số tiền | Sửa tham số `vnp_Amount` hoặc `vnp_SecureHash` trên URL trả về | `RspCode 97` (sai chữ ký) / `04` (sai số tiền); đơn và payment giữ nguyên `pending` | `test_flow_6_forged_signature_or_wrong_amount_is_rejected` |

Bổ sung: bấm "Xác nhận đặt hàng" hai lần liên tiếp chỉ tạo một đơn (`test_double_submit_creates_single_order`).

## Luồng 5 chi tiết

```text
Trình duyệt ──return URL──▶ processVNPayIpn(TxnRef=X)  → pending → paid   (RspCode 00)
VNPay ───────IPN─────────▶ processVNPayIpn(TxnRef=X)  → đã paid         (RspCode 02, bỏ qua)
VNPay ───IPN gửi lại─────▶ processVNPayIpn(TxnRef=X)  → đã paid         (RspCode 02, bỏ qua)

Đơn hết hạn (30 phút) ───▶ payment failed, đơn cancelled, giỏ hàng được trả lại
VNPay ─IPN thành công muộn▶ processVNPayIpn(TxnRef=Y)  → đã failed       (RspCode 02)
                                                     └▶ audit payment_refund_required + thông báo admin hoàn tiền
```

## Bảng ghi kết quả chạy tay (điền khi demo)

| # | Ngày giờ | Mã đơn | vnp_TxnRef | Kết quả quan sát | Ảnh chụp |
|---|---|---|---|---|---|
| 1 | | | — | | |
| 2 | | | | | |
| 3 | | | | | |
| 4 | | | | | |
| 5 | | | | | |
| 6 | | | | | |
