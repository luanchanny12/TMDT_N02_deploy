<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Khu vực giao hàng: mỗi khu vực gồm nhiều tỉnh/thành, có phí ship và ngưỡng freeship riêng
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('provinces');
            $table->unsignedInteger('fee');
            $table->unsignedBigInteger('free_shipping_threshold')->nullable(); // null = dùng ngưỡng chung
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_province', 100)->nullable()->after('shipping_address');
        });

        // Khu vực mặc định để shop chạy được ngay; admin chỉnh tại Cài đặt → Khu vực giao hàng
        $regions = config('vietnam.regions');
        $now = now();
        $zones = [
            ['Nội thành Hà Nội & TP. Hồ Chí Minh', ['Hà Nội', 'TP. Hồ Chí Minh'], 20000, 300000],
            ['Miền Bắc', array_values(array_diff($regions['Miền Bắc'], ['Hà Nội'])), 30000, null],
            ['Miền Trung', $regions['Miền Trung'], 35000, null],
            ['Miền Nam', array_values(array_diff($regions['Miền Nam'], ['TP. Hồ Chí Minh'])), 30000, null],
        ];

        foreach ($zones as $i => [$name, $provinces, $fee, $threshold]) {
            DB::table('shipping_zones')->insert([
                'name' => $name,
                'provinces' => json_encode($provinces, JSON_UNESCAPED_UNICODE),
                'fee' => $fee,
                'free_shipping_threshold' => $threshold,
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_province');
        });

        Schema::dropIfExists('shipping_zones');
    }
};
