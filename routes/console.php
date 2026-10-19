<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sao lưu DB hằng ngày lúc 02:00 (cần `php artisan schedule:work` hoặc Task Scheduler)
Schedule::command('db:backup')->dailyAt('02:00');

// Khuyến mãi hết hạn → về giá gốc (đồng hồ Flash Sale đếm tới đúng mốc này)
Schedule::command('products:end-expired-sales')->everyMinute()->withoutOverlapping();

// Xóa thống kê truy cập (24 tháng) và nhật ký bảo mật (12 tháng) quá hạn — đúng thời hạn công bố
Schedule::command('data:prune')->dailyAt('03:00');

// Hủy đơn VNPay bỏ dở để nhả tồn kho
Schedule::command('orders:expire-unpaid')->everyFiveMinutes()->withoutOverlapping();
