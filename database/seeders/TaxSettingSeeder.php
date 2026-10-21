<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Thuế VAT 8% mặc định cho DB mới (Render dựng lại DB mỗi lần deploy).
 * Chạy lại an toàn: không ghi đè nếu admin đã bật/tắt thuế tại Admin → Cài đặt.
 */
class TaxSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (Setting::get('tax_enabled') !== null) {
            return;
        }

        Setting::put(['tax_enabled' => 1, 'tax_rate' => 8, 'tax_label' => 'Thuế VAT', 'tax_on_shipping' => 0]);
    }
}
