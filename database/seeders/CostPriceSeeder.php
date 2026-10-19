<?php

namespace Database\Seeders;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Giá vốn mẫu: 55–65% giá niêm yết (biên lợi nhuận gộp 35–45%), cố định theo slug để chạy lại vẫn ra cùng số.
 * Chỉ điền chỗ còn trống (không ghi đè giá vốn admin đã nhập); dòng đơn cũ lấy giá vốn hiện tại của sản phẩm.
 *   php artisan db:seed --class=CostPriceSeeder
 */
class CostPriceSeeder extends Seeder
{
    public function run(): void
    {
        Product::whereNull('cost_price')->each(function (Product $product) {
            $ratio = 55 + crc32($product->slug) % 11; // 55..65 %
            $product->update(['cost_price' => (int) (round($product->price * $ratio / 100 / 1000) * 1000)]);
        });

        OrderItem::whereNull('cost_price')->with('product:id,cost_price')->each(function (OrderItem $item) {
            if ($item->product?->cost_price !== null) {
                $item->update(['cost_price' => $item->product->cost_price]);
            }
        });
    }
}
