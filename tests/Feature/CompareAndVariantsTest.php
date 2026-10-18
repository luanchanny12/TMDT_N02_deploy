<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareAndVariantsTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => Category::factory()->create()->id,
            'status' => 'active',
            'sale_price' => null,
            'stock' => 10,
        ], $attrs));
    }

    public function test_compare_highlights_cheapest_and_differences(): void
    {
        $cheap = $this->product(['name' => 'Áo Rẻ', 'price' => 150000, 'brand' => 'A']);
        $pricey = $this->product(['name' => 'Áo Đắt', 'price' => 900000, 'sale_price' => 600000, 'brand' => 'B']);
        ProductVariant::create(['product_id' => $pricey->id, 'size' => 'M', 'color' => 'Đen', 'stock' => 3]);

        $this->get(route('compare.index', ['products' => [$cheap->id, $pricey->id]]))
            ->assertOk()
            ->assertSee('data-testid="compare-verdict"', false)
            ->assertSeeInOrder(['Giá tốt nhất', 'Áo Rẻ'])
            ->assertSeeInOrder(['Giảm nhiều nhất', 'Áo Đắt'])
            ->assertSee('-33%')
            ->assertSee('Chỉ hiện điểm khác nhau')
            ->assertSee('Chọn size/màu');
    }

    public function test_compare_add_to_cart_form_sends_product_id(): void
    {
        $p = $this->product(['name' => 'Túi Tote']);

        $html = $this->get(route('compare.index', ['products' => [$p->id]]))->assertOk()->getContent();
        $this->assertStringContainsString('name="product_id" value="'.$p->id.'"', $html);

        // Form trên trang so sánh thêm vào giỏ được thật
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1])->assertRedirect(route('cart.index'));
    }

    public function test_compare_suggests_similar_products(): void
    {
        $cat = Category::factory()->create();
        $a = $this->product(['category_id' => $cat->id, 'name' => 'Giày A']);
        $this->product(['category_id' => $cat->id, 'name' => 'Giày B gợi ý']);

        $this->get(route('compare.index', ['products' => [$a->id]]))
            ->assertOk()
            ->assertSee('Thêm ít nhất')
            ->assertSee('Giày B gợi ý');
    }

    public function test_compare_bar_shows_count(): void
    {
        $p = $this->product();
        $this->postJson(route('compare.add', $p))->assertOk();

        $this->get(route('home'))->assertSee('data-testid="compare-bar"', false);
    }

    public function test_admin_creates_product_with_variants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()->assertSee('Tạo tổ hợp');

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Áo Thun Basic',
            'price' => 199000,
            'category_id' => Category::factory()->create()->id,
            'stock' => 0,
            'variants_submitted' => '1',
            'variants' => [
                ['size' => 'S', 'color' => 'Trắng', 'stock' => 4, 'price' => ''],
                ['size' => 'M', 'color' => 'Trắng', 'stock' => 6, 'price' => '209000'],
                ['size' => '', 'color' => '', 'stock' => 9], // dòng trống bị bỏ qua
            ],
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Áo Thun Basic')->firstOrFail();
        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(10, $product->fresh()->stock); // tồn kho = tổng biến thể
        $this->assertSame(209000, (int) $product->variants()->where('size', 'M')->value('price'));
    }

    public function test_admin_can_remove_all_variants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['name' => 'Quần Jean']);
        ProductVariant::create(['product_id' => $product->id, 'size' => '30', 'color' => 'Xanh', 'stock' => 2]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => 'Quần Jean', 'price' => $product->price, 'category_id' => $product->category_id,
            'stock' => 5, 'variants_submitted' => '1',
        ])->assertRedirect();

        $this->assertSame(0, $product->variants()->count());
    }

    public function test_variant_stock_is_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Sai', 'price' => 1000, 'category_id' => Category::factory()->create()->id, 'stock' => 0,
            'variants' => [['size' => 'S', 'color' => '', 'stock' => -5]],
        ])->assertSessionHasErrors('variants.0.stock');
    }
}
