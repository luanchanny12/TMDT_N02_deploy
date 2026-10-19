<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

/**
 * Biến thể size/màu mẫu cho quần áo và giày dép.
 * Chạy lại an toàn: bỏ qua sản phẩm đã có biến thể (không đụng dữ liệu admin đã nhập).
 *   php artisan db:seed --class=ProductVariantSeeder
 */
class ProductVariantSeeder extends Seeder
{
    private const TOPS = ['S', 'M', 'L', 'XL'];

    private const SHOES = ['38', '39', '40', '41', '42', '43'];

    /**
     * slug => [danh sách size, danh sách màu, phụ thu size lớn (₫)]
     */
    private const VARIANTS = [
        // Thời trang nam
        'ao-polo-nam' => [self::TOPS, ['Trắng', 'Đen', 'Xanh navy'], 20000],
        'ao-khoac-jacket-nam' => [['M', 'L', 'XL'], ['Đen', 'Xanh rêu'], 30000],
        'ao-thun-unisex' => [self::TOPS, ['Trắng', 'Đen', 'Be'], 20000],
        'quan-short-nam' => [['M', 'L', 'XL'], ['Đen', 'Xám'], 0],
        // Thời trang nữ
        'ao-so-mi-nu' => [['S', 'M', 'L'], ['Trắng', 'Hồng phấn'], 0],
        'quan-jean-nu' => [['26', '27', '28', '29', '30'], ['Xanh nhạt', 'Xanh đậm'], 0],
        'dam-da-hoi' => [['S', 'M', 'L'], ['Đỏ', 'Đen'], 0],
        'ao-so-mi-nu-tay-bong' => [['S', 'M', 'L'], ['Trắng'], 0],
        'ao-so-mi-nu-that-no' => [['S', 'M', 'L'], ['Hồng phấn'], 0],
        'ao-thun-nu-co-tron' => [['S', 'M', 'L'], ['Xanh navy'], 0],
        'quan-jean-nu-rach-goi' => [['26', '27', '28', '29', '30'], ['Xanh nhạt'], 0],
        'ao-phao-nam-co-mu' => [['M', 'L', 'XL'], ['Đen'], 30000],
        'ao-polo-nam-vien-co' => [self::TOPS, ['Xanh navy'], 20000],
        'ao-thun-nam-basic' => [self::TOPS, ['Xám'], 20000],
        // Giày dép
        'nike-air-max-90' => [self::SHOES, ['Trắng', 'Đen'], 0],
        'adidas-ultraboost-light' => [self::SHOES, ['Trắng', 'Đen'], 0],
        'converse-chuck-taylor' => [self::SHOES, ['Trắng', 'Đen'], 0],
        'vans-old-skool' => [self::SHOES, ['Đen'], 0],
        'nike-jordan-1-retro-high' => [self::SHOES, ['Đỏ/Trắng'], 0],
        'dr-martens-1460' => [self::SHOES, ['Đen'], 0],
    ];

    private const UPCHARGE_SIZES = ['XL'];

    public function run(): void
    {
        mt_srand(1460);

        foreach (self::VARIANTS as $slug => [$sizes, $colors, $upcharge]) {
            $product = Product::where('slug', $slug)->first();
            if (! $product || $product->variants()->exists()) {
                continue;
            }

            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'size' => $size,
                        'color' => $color,
                        // Size giữa bán chạy nên nhiều hàng hơn; thỉnh thoảng có size hết hàng để demo
                        'stock' => mt_rand(1, 10) === 1 ? 0 : mt_rand(3, 15),
                        'price' => $upcharge && in_array($size, self::UPCHARGE_SIZES, true)
                            ? $product->effectivePrice() + $upcharge
                            : null,
                    ]);
                }
            }

            // Tồn kho sản phẩm = tổng tồn kho các biến thể (giống khi admin lưu biến thể)
            $product->update(['stock' => (int) $product->variants()->sum('stock')]);
        }
    }
}
