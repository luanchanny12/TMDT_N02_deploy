#!/bin/sh
set -e

# Ensure SQLite file exists and is writable
if [ ! -f /app/database/database.sqlite ]; then
    touch /app/database/database.sqlite
fi
chown -R www-data:www-data /app/database /app/storage /app/bootstrap/cache

# Run database migrations and seed demo data
php artisan migrate --force
php artisan db:seed --force || true

# Optimize configuration and route caching
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Tác vụ nền (host chỉ có một container, không có cron riêng): tự chạy lại nếu tiến trình thoát
#  - scheduler: hủy đơn VNPay quá hạn (nhả tồn kho), kết thúc Flash Sale, email giữ chân, dọn dữ liệu
#  - queue worker: gửi email xác nhận đơn / cập nhật trạng thái / email giữ chân (bỏ qua khi QUEUE_CONNECTION=sync)
(while true; do php artisan schedule:work >> /app/storage/logs/scheduler.log 2>&1; sleep 5; done) &

if [ "${QUEUE_CONNECTION:-database}" != "sync" ]; then
    (while true; do php artisan queue:work --tries=3 --sleep=3 --max-time=3600 >> /app/storage/logs/queue.log 2>&1; sleep 5; done) &
fi

# Start FrankenPHP server listening on PORT
exec frankenphp php-server --listen ":${PORT:-8000}" --root /app/public
