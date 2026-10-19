<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thời điểm giá khuyến mãi hết hiệu lực: đồng hồ Flash Sale đếm tới mốc thật này,
     * hết giờ giá tự về giá gốc (tránh dark pattern "đếm ngược giả" — LT08).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('sale_ends_at')->nullable()->after('sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sale_ends_at');
        });
    }
};
