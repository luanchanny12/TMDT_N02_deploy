<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy tất cả subcategories (có parent_id)
        $categoryIds = Category::whereNotNull('parent_id')->pluck('id')->toArray();

        $fashionImages = [
            'https://images.unsplash.com/photo-1586363104862-3a5e2ab60d99?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1598554747436-c9293d6a588f?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1607522370275-f14206abe5d3?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1552346154-21d32810aba3?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1544816155-12df9643f363?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1533139502658-0198f920d8e8?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1614164185128-e4ec99c436d7?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1541643600914-78b084683601?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=800&h=800&fit=crop&q=85',
            'https://images.unsplash.com/photo-1560750588-73207b1ef5b8?w=800&h=800&fit=crop&q=85',
        ];

        // Tạo 50 sản phẩm
        Product::factory()
            ->count(50)
            ->state(function () use ($categoryIds) {
                return ['category_id' => fake()->randomElement($categoryIds)];
            })
            ->create()
            ->each(function (Product $product) use ($fashionImages) {
                // Mỗi sản phẩm có 2–3 ảnh thật chất lượng cao
                $imageCount = fake()->numberBetween(2, 3);
                $shuffled = collect($fashionImages)->shuffle();
                for ($i = 0; $i < $imageCount; $i++) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $shuffled->get($i) ?? $fashionImages[0],
                        'is_primary' => $i === 0,
                        'sort_order' => $i,
                    ]);
                }
            });
    }
}
