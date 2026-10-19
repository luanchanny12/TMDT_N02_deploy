<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

/**
 * Mã khuyến mãi được quảng bá trên thanh ưu đãi đầu trang.
 * Chạy lại nhiều lần an toàn (mỗi lần khởi động container app): chỉ tạo nếu chưa có, không ghi đè chỉnh sửa của admin.
 */
class PromoCouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::firstOrCreate(['code' => Coupon::WELCOME_CODE], [
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 0,
            'max_discount' => 100000,
            'start_at' => null,
            'end_at' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'status' => 'active',
        ]);
    }
}
