<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // DB bền (Postgres) thì deploy nào cũng gọi db:seed — đã seed demo rồi thì bỏ qua, tránh tạo trùng
        if (User::where('email', 'admin@socialshop.vn')->exists()) {
            return;
        }

        // Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@socialshop.vn',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'referral_code' => 'ADMIN001',
        ]);

        // User test
        User::create([
            'name' => 'Nguyễn Văn A',
            'email' => 'user@socialshop.vn',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'referral_code' => 'USER0001',
        ]);

        // Danh mục: SocialShop chuyên thời trang & phụ kiện. Danh mục con (cấp 2) để demo danh mục nhiều cấp.
        $tree = [
            ['Thời trang nam', 'thoi-trang-nam', [
                ['Áo nam', 'ao-nam'],
                ['Quần nam', 'quan-nam'],
            ]],
            ['Thời trang nữ', 'thoi-trang-nu', [
                ['Áo nữ', 'ao-nu'],
                ['Quần & Váy đầm', 'quan-vay-dam'],
            ]],
            ['Giày dép', 'giay-dep', [
                ['Sneaker', 'giay-sneaker'],
                ['Giày boot', 'giay-boot'],
            ]],
            ['Túi xách', 'tui-xach', []],
            ['Đồng hồ', 'dong-ho', []],
            ['Mỹ phẩm & Nước hoa', 'my-pham', []],
        ];

        $categoryIds = [];
        foreach ($tree as $i => [$name, $slug, $children]) {
            $catImg = file_exists(public_path("category-images/{$slug}.jpg")) ? "category-images/{$slug}.jpg" : null;
            $parent = Category::create(['name' => $name, 'slug' => $slug, 'image' => $catImg, 'sort_order' => $i + 1, 'status' => 'active']);
            $categoryIds[$slug] = $parent->id;

            foreach ($children as $j => [$childName, $childSlug]) {
                $categoryIds[$childSlug] = Category::create([
                    'name' => $childName,
                    'slug' => $childSlug,
                    'parent_id' => $parent->id,
                    'sort_order' => $j + 1,
                    'status' => 'active',
                ])->id;
            }
        }

        $products = [
            // Thời trang nam
            ['category' => 'ao-nam', 'name' => 'Áo Polo Nam', 'slug' => 'ao-polo-nam', 'description' => 'Áo Polo nam cotton cao cấp, phong cách thể thao năng động.', 'price' => 350000, 'stock' => 200],
            ['category' => 'ao-nam', 'name' => 'Áo Khoác Jacket Nam', 'slug' => 'ao-khoac-jacket-nam', 'description' => 'Áo khoác jacket nam phong cách streetwear, giữ ấm tốt.', 'price' => 599000, 'stock' => 80],
            ['category' => 'ao-nam', 'name' => 'Áo Thun Unisex', 'slug' => 'ao-thun-unisex', 'description' => 'Áo thun unisex form rộng, chất liệu cotton 100%, nhiều màu sắc.', 'price' => 150000, 'stock' => 300],
            ['category' => 'quan-nam', 'name' => 'Quần Short Nam', 'slug' => 'quan-short-nam', 'description' => 'Quần short nam thể thao, chất liệu cotton thoáng mát.', 'price' => 199000, 'stock' => 200],

            // Thời trang nữ
            ['category' => 'ao-nu', 'name' => 'Áo Sơ Mi Nữ', 'slug' => 'ao-so-mi-nu', 'description' => 'Áo sơ mi nữ công sở, chất liệu lụa mềm mại, phong cách thanh lịch.', 'price' => 290000, 'stock' => 150],
            ['category' => 'quan-vay-dam', 'name' => 'Quần Jean Nữ', 'slug' => 'quan-jean-nu', 'description' => 'Quần Jean nữ ống rộng, chất liệu co giãn thoải mái.', 'price' => 450000, 'stock' => 150],
            ['category' => 'quan-vay-dam', 'name' => 'Đầm Dạ Hội', 'slug' => 'dam-da-hoi', 'description' => 'Đầm dạ hội dài, thiết kế sang trọng, phù hợp tiệc và sự kiện.', 'price' => 1200000, 'stock' => 50],

            // Giày dép
            ['category' => 'giay-sneaker', 'name' => 'Nike Air Max 90', 'slug' => 'nike-air-max-90', 'description' => 'Giày Nike Air Max 90, thiết kế huyền thoại, đệm khí thoải mái.', 'price' => 3200000, 'stock' => 40],
            ['category' => 'giay-sneaker', 'name' => 'Adidas Ultraboost Light', 'slug' => 'adidas-ultraboost-light', 'description' => 'Giày Adidas Ultraboost Light, công nghệ BOOST, êm ái tối đa.', 'price' => 4500000, 'stock' => 30],
            ['category' => 'giay-sneaker', 'name' => 'Converse Chuck Taylor', 'slug' => 'converse-chuck-taylor', 'description' => 'Giày Converse Chuck Taylor All Star Classic, phong cách đường phố.', 'price' => 1500000, 'stock' => 60],
            ['category' => 'giay-sneaker', 'name' => 'Vans Old Skool', 'slug' => 'vans-old-skool', 'description' => 'Giày Vans Old Skool, thiết kế side stripe đặc trưng.', 'price' => 1800000, 'stock' => 50],
            ['category' => 'giay-sneaker', 'name' => 'Nike Jordan 1 Retro High', 'slug' => 'nike-jordan-1-retro-high', 'description' => 'Giày Nike Air Jordan 1 Retro High OG, biểu tượng sneaker.', 'price' => 4990000, 'stock' => 15],
            ['category' => 'giay-boot', 'name' => 'Dr. Martens 1460', 'slug' => 'dr-martens-1460', 'description' => 'Giày boot Dr. Martens 1460, da thật, phong cách punk cổ điển.', 'price' => 4200000, 'stock' => 25],

            // Túi xách
            ['category' => 'tui-xach', 'name' => 'Coach Tabby Bag', 'slug' => 'coach-tabby-bag', 'description' => 'Túi Coach Tabby Shoulder Bag, da cao cấp, thiết kế cổ điển.', 'price' => 8500000, 'stock' => 20],
            ['category' => 'tui-xach', 'name' => 'Michael Kors Jet Set', 'slug' => 'michael-kors-jet-set', 'description' => 'Túi Michael Kors Jet Set Travel Tote, phong cách năng động.', 'price' => 6990000, 'stock' => 25],
            ['category' => 'tui-xach', 'name' => 'Furla Metropolis', 'slug' => 'furla-metropolis', 'description' => 'Túi Furla Metropolis Mini, thiết kế Italy thanh lịch.', 'price' => 7500000, 'stock' => 15],
            ['category' => 'tui-xach', 'name' => 'Longchamp Le Pliage', 'slug' => 'longchamp-le-pliage', 'description' => 'Túi Longchamp Le Pliage, vải nylon chống nước, gập gọn tiện lợi.', 'price' => 3500000, 'stock' => 30],
            ['category' => 'tui-xach', 'name' => 'Tote Bag Canvas Nam', 'slug' => 'tote-bag-canvas-nam', 'description' => 'Tote bag canvas nam, chất liệu dày dặn, phong cách tối giản.', 'price' => 250000, 'stock' => 100],
            ['category' => 'tui-xach', 'name' => 'Balo Thời Trang Chống Trộm', 'slug' => 'balo-laptop-anti-theft', 'description' => 'Balo thời trang chống trộm, ngăn khóa ẩn, vải chống nước.', 'price' => 890000, 'stock' => 40],

            // Đồng hồ
            ['category' => 'dong-ho', 'name' => 'Casio G-Shock GA-2100', 'slug' => 'casio-g-shock-ga-2100', 'description' => 'Đồng hồ Casio G-Shock GA-2100, chống sốc, chống nước 200m.', 'price' => 3500000, 'stock' => 30],
            ['category' => 'dong-ho', 'name' => 'Seiko Presage SRPD', 'slug' => 'seiko-presage-srpd', 'description' => 'Đồng hồ Seiko Presage SRPD, automatic, mặt số tinh tế.', 'price' => 8990000, 'stock' => 15],
            ['category' => 'dong-ho', 'name' => 'Orient Bambino', 'slug' => 'orient-bambino', 'description' => 'Đồng hồ Orient Bambino, automatic, thiết kế cổ điển thanh lịch.', 'price' => 5990000, 'stock' => 20],
            ['category' => 'dong-ho', 'name' => 'Tissot PRX', 'slug' => 'tissot-prx', 'description' => 'Đồng hồ Tissot PRX automatic, phong cách retro hiện đại.', 'price' => 15990000, 'stock' => 10],
            ['category' => 'dong-ho', 'name' => 'Citizen Eco-Drive', 'slug' => 'citizen-eco-drive', 'description' => 'Đồng hồ Citizen Eco-Drive, năng lượng ánh sáng, không cần pin.', 'price' => 7990000, 'stock' => 18],

            // Mỹ phẩm & Nước hoa
            ['category' => 'my-pham', 'name' => 'Son MAC Ruby Woo', 'slug' => 'son-mac-ruby-woo', 'description' => 'Son môi MAC Ruby Woo, màu đỏ cổ điển, finish matte.', 'price' => 450000, 'stock' => 100],
            ['category' => 'my-pham', 'name' => 'Nước Hoa Chanel No.5', 'slug' => 'nuoc-hoa-chanel-no-5', 'description' => 'Nước hoa Chanel No.5 Eau de Parfum 100ml, hương hoa cổ điển.', 'price' => 3500000, 'stock' => 20],
            ['category' => 'my-pham', 'name' => 'Kem Chống Nắng Anessa', 'slug' => 'kem-chong-nang-anessa', 'description' => 'Kem chống nắng Anessa Perfect UV SPF50+, chống nước, không trắng.', 'price' => 480000, 'stock' => 80],
            ['category' => 'my-pham', 'name' => 'Serum The Ordinary Niacinamide', 'slug' => 'serum-the-ordinary-niacinamide', 'description' => 'Serum The Ordinary Niacinamide 10% + Zinc 1%, se khít lỗ chân lông.', 'price' => 250000, 'stock' => 120],
            ['category' => 'my-pham', 'name' => 'Phấn Nước MAC', 'slug' => 'phan-nuoc-mac', 'description' => 'Phấn nước MAC Studio Fix Fluid, che phủ hoàn hảo, lâu trôi.', 'price' => 950000, 'stock' => 50],
            ['category' => 'my-pham', 'name' => 'Mascara Maybelline Lash Sensational', 'slug' => 'mascara-maybelline-lash', 'description' => 'Mascara Maybelline Lash Sensational, làm dày và cong mi vượt trội.', 'price' => 180000, 'stock' => 90],
        ];

        foreach ($products as $prod) {
            $product = Product::create([
                'category_id' => $categoryIds[$prod['category']],
                'name' => $prod['name'],
                'slug' => $prod['slug'],
                'description' => $prod['description'],
                'price' => $prod['price'],
                'stock' => $prod['stock'],
                'status' => 'active',
            ]);

            if (file_exists(public_path("product-images/{$prod['slug']}.jpg"))) {
                $product->images()->create([
                    'image_path' => "product-images/{$prod['slug']}.jpg",
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);
            }
        }

        // Chính sách vận chuyển của shop: freeship toàn quốc cho đơn từ 300.000đ (chỉnh tại Admin → Cài đặt)
        Setting::put(['free_shipping_threshold' => 300000, 'shipping_fee' => 30000]);

        $this->call(TaxSettingSeeder::class); // VAT 8% mặc định

        // Coupons
        $this->call(ExtraFashionProductSeeder::class); // +10 sản phẩm → 40 (TH11)
        $this->call(CostPriceSeeder::class); // giá vốn 55–65% giá niêm yết
        $this->call(FlashSaleSeeder::class); // ưu đãi có mốc kết thúc thật

        $this->call(CouponSeeder::class);
        $this->call(PromoCouponSeeder::class); // WELCOME10 trên thanh ưu đãi

        // Chatbot FAQs
        $this->call(ChatbotFaqSeeder::class);

        // Đơn hàng + đánh giá demo (dashboard doanh thu, hàng đợi kiểm duyệt)
        $this->call(DemoOrderSeeder::class);

        // Biến thể size/màu mẫu cho quần áo, giày dép (sau đơn demo để tồn kho = tổng biến thể)
        $this->call(ProductVariantSeeder::class);

        // Chiến dịch quảng cáo + lượt truy cập demo (trang Phân tích dữ liệu)
        $this->call(CampaignSeeder::class);
    }
}
