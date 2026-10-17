# ai-service — Microservice chatbot AI

Dịch vụ độc lập (Node.js 22, không thư viện ngoài) phụ trách gọi Google Gemini cho chatbot của SocialShop.
Web Laravel gửi lịch sử hội thoại + ngữ cảnh (FAQ, sản phẩm, đơn hàng) tới dịch vụ; dịch vụ giữ khóa Gemini,
gọi API streaming và chuyển tiếp luồng SSE về Laravel.

```
Trình duyệt ──SSE──> Laravel (app) ──HTTP nội bộ + Bearer token──> ai-service ──HTTPS──> Gemini API
```

Lợi ích: tách khóa API khỏi web chính, mở rộng/khởi động lại chatbot độc lập, giới hạn tần suất riêng,
lỗi AI không ảnh hưởng mua hàng (Laravel tự quay về gọi Gemini trực tiếp hoặc FAQ khi dịch vụ lỗi).

## API

| Method | Path | Mô tả |
|---|---|---|
| GET | `/health` | Trạng thái dịch vụ (dùng cho Docker healthcheck) |
| POST | `/v1/chat/stream` | Header `Authorization: Bearer <AI_SERVICE_TOKEN>`; body `{ systemInstruction, contents, generationConfig }` (định dạng Gemini, tối đa 64 KB); trả về `text/event-stream` |

Mã lỗi: 401 sai token · 413 body quá lớn · 422 sai định dạng · 429 vượt giới hạn · 502/504 Gemini lỗi/không phản hồi · 503 thiếu khóa Gemini.

## Biến môi trường

| Biến | Mặc định | Ghi chú |
|---|---|---|
| `AI_SERVICE_TOKEN` | — (bắt buộc) | Token dùng chung với Laravel |
| `GEMINI_API_KEY` | — | Khóa Google AI Studio |
| `GEMINI_MODEL` | `gemini-3.5-flash` | |
| `PORT` | `3000` | |
| `RATE_LIMIT_PER_MINUTE` | `120` | Theo địa chỉ gọi tới |
| `UPSTREAM_TIMEOUT_MS` | `20000` | |

## Chạy & kiểm thử

```bash
docker compose up -d ai-service        # chạy cùng hệ thống (mạng nội bộ, không mở cổng ra ngoài)
docker compose exec ai-service wget -qO- http://127.0.0.1:3000/health

cd services/ai-service && npm test      # 7 ca kiểm thử (Gemini giả lập), không cần mạng
```
