<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductVariantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_clothes_and_shoes_get_sample_variants(): void
    {
        $this->seed(DatabaseSeeder::class);

        $polo = Product::where('slug', 'ao-polo-nam')->firstOrFail();
        $this->assertSame(12, $polo->variants()->count()); // 4 size × 3 màu
        $this->assertSame((int) $polo->variants()->sum('stock'), $polo->stock);
        $this->assertSame($polo->price + 20000, (int) $polo->variants()->where('size', 'XL')->value('price'));
        $this->assertNull($polo->variants()->where('size', 'M')->value('price'));

        $sneaker = Product::where('slug', 'nike-air-max-90')->firstOrFail();
        $this->assertEqualsCanonicalizing(['38', '39', '40', '41', '42', '43'], $sneaker->variants()->distinct()->pluck('size')->all());

        // Túi xách, đồng hồ, mỹ phẩm không có biến thể
        $this->assertFalse(Product::where('slug', 'coach-tabby-bag')->firstOrFail()->hasVariants());

        $this->get(route('products.show', $polo))->assertOk()->assertSee('Xanh navy')->assertSee('XL');
    }

    public function test_seeder_can_rerun_without_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $before = ProductVariant::count();

        $this->seed(ProductVariantSeeder::class);

        $this->assertSame($before, ProductVariant::count());
    }

    public function test_product_with_variants_requires_size_choice(): void
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'status' => 'active', 'stock' => 10]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'size' => 'M', 'color' => 'Đen', 'stock' => 5]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Vui lòng chọn size/màu cho "'.$product->name.'".']);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1])
            ->assertOk();

        // Thẻ sản phẩm dẫn sang trang chi tiết thay vì thêm giỏ không có size
        $this->get(route('products.index'))->assertSee('Chọn size/màu');
    }
}
