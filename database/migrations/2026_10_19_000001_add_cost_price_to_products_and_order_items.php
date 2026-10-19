<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Giá vốn (TH11): trên sản phẩm (giá hiện hành) và snapshot trên từng dòng đơn
     * để báo cáo lợi nhuận gộp các tháng trước không đổi khi giá vốn thay đổi.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_price')->nullable()->after('sale_price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_price')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
