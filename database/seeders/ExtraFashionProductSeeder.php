<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * 10 sản phẩm bổ sung (áo nữ, quần nữ, áo nam, đồng hồ) để catalog đạt 40 sản phẩm (tiêu chí TH11).
 * Ảnh nằm ở public/product-images/{slug}.jpg. Chạy lại an toàn: bỏ qua slug đã tồn tại.
 *   php artisan db:seed --class=ExtraFashionProductSeeder
 */
class ExtraFashionProductSeeder extends Seeder
{
    private const PRODUCTS = [
        // Thời trang nữ — Áo nữ
        ['ao-nu', 'Áo Sơ Mi Nữ Tay Bồng Trắng', 'ao-so-mi-nu-tay-bong', 'Áo sơ mi nữ trắng tay bồng bo chun, cổ đức, form suông nhẹ nhàng — dễ phối với quần jean hoặc chân váy.', 350000, 60],
        ['ao-nu', 'Áo Sơ Mi Nữ Thắt Nơ Cổ', 'ao-so-mi-nu-that-no', 'Áo sơ mi voan nữ màu hồng phấn, cổ thắt nơ, tay dài bo cổ tay — thanh lịch cho công sở và dạo phố.', 420000, 45],
        ['ao-nu', 'Áo Thun Nữ Cổ Tròn', 'ao-thun-nu-co-tron', 'Áo thun nữ cotton cổ tròn tay ngắn màu xanh navy, ôm vừa, mềm mát — món cơ bản cho tủ đồ hằng ngày.', 179000, 120],
        // Thời trang nữ — Quần & Váy đầm
        ['quan-vay-dam', 'Quần Jean Nữ Rách Gối', 'quan-jean-nu-rach-goi', 'Quần jean nữ dáng baggy lưng cao, màu xanh nhạt, chi tiết rách gối cá tính, gấu cắt tua.', 390000, 70],
        // Thời trang nam — Áo nam
        ['ao-nam', 'Áo Phao Nam Mũ Hoodie Nỉ', 'ao-phao-nam-co-mu', 'Áo phao nam màu đen phối mũ hoodie nỉ xám có dây rút, khóa kéo, bo tay chun — ấm áp, trẻ trung cho mùa đông.', 690000, 40],
        ['ao-nam', 'Áo Polo Nam Phối Viền Cổ', 'ao-polo-nam-vien-co', 'Áo polo nam màu xanh navy, cổ và tay phối viền trắng, vải cá sấu thoáng khí — lịch sự mà vẫn năng động.', 320000, 90],
        ['ao-nam', 'Áo Thun Nam Basic Xám', 'ao-thun-nam-basic', 'Áo thun nam cổ tròn màu xám, form regular, cotton dày dặn không xù — dễ mặc, dễ phối.', 199000, 150],
        // Đồng hồ
        ['dong-ho', 'Đồng Hồ Chronograph Dây Da Viền Vàng', 'dong-ho-day-da-vien-vang', 'Đồng hồ chronograph dây da nâu vân nổi, vỏ mạ vàng, mặt trắng ngà với 3 mặt phụ và lịch ngày, chống nước 10 ATM — lịch lãm cho công sở.', 2490000, 25],
        ['dong-ho', 'Đồng Hồ Nam Dây Kim Loại Mặt Xanh', 'dong-ho-nam-day-kim-loai-mat-xanh', 'Đồng hồ nam dây thép không gỉ, mặt xanh navy có lịch ngày, viền khía — sang trọng cho công sở và sự kiện.', 3290000, 15],
        ['dong-ho', 'Đồng Hồ Thông Minh Dây Silicone Trắng', 'dong-ho-day-silicone-trang', 'Đồng hồ thông minh vỏ thép mạ vàng, dây silicone trắng mềm; mặt hiển thị tùy chỉnh, theo dõi vận động và nhận thông báo — vừa thời trang vừa tiện dụng.', 5490000, 30],
    ];

    public function run(): void
    {
        foreach (self::PRODUCTS as [$categorySlug, $name, $slug, $description, $price, $stock]) {
            $categoryId = Category::where('slug', $categorySlug)->value('id');
            if (! $categoryId || Product::where('slug', $slug)->exists()) {
                continue;
            }

            $product = Product::create([
                'category_id' => $categoryId,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'status' => 'active',
            ]);

            if (file_exists(public_path("product-images/{$slug}.jpg"))) {
                $product->images()->create([
                    'image_path' => "product-images/{$slug}.jpg",
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);
            }
        }
    }
}
