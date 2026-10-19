<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Giỏ hàng phải chứa được nhiều size/màu của cùng một sản phẩm (mỗi biến thể một dòng).
     * Ràng buộc cũ "mỗi sản phẩm 1 dòng/giỏ" có từ trước khi có biến thể làm lỗi khi thêm size thứ hai.
     * Vẫn còn chỉ mục (cart_id, product_id, variant_id) cho khóa ngoại cart_id và truy vấn gộp dòng.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['cart_id', 'product_id']);
        });
    }
};
