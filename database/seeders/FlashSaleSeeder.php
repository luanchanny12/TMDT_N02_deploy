<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Flash Sale mẫu có mốc kết thúc thật (23:59:59 sau 3 ngày): giảm 15–25% cho 4 sản phẩm.
 * Bỏ qua khi đang có Flash Sale chạy. Hết hạn → lệnh products:end-expired-sales đưa về giá gốc.
 *   php artisan db:seed --class=FlashSaleSeeder
 */
class FlashSaleSeeder extends Seeder
{
    private const DEALS = [
        'tote-bag-canvas-nam' => 20,
        'ao-thun-unisex' => 15,
        'converse-chuck-taylor' => 25,
        'kem-chong-nang-anessa' => 20,
    ];

    public function run(): void
    {
        if (Product::flashSale()->exists()) {
            return;
        }

        $endsAt = now()->addDays(3)->setTime(23, 59, 59);

        foreach (self::DEALS as $slug => $percent) {
            $product = Product::where('slug', $slug)->first();
            $product?->update([
                'sale_price' => (int) (round($product->price * (100 - $percent) / 100 / 1000) * 1000),
                'sale_ends_at' => $endsAt,
            ]);
        }
    }
}
