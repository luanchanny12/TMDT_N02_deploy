<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cấu hình cửa hàng do admin chỉnh (thuế VAT, phí vận chuyển...)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Lưu thuế tại thời điểm đặt hàng: đổi cấu hình sau này không làm sai đơn cũ
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('shipping_fee');
            $table->unsignedBigInteger('tax_amount')->default(0)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'tax_amount']);
        });

        Schema::dropIfExists('settings');
    }
};
