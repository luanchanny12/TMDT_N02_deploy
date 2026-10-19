<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_margin_helpers(): void
    {
        $p = new Product(['price' => 500000, 'sale_price' => null, 'cost_price' => 300000]);

        $this->assertSame(200000, $p->grossProfit());
        $this->assertSame(40.0, $p->grossMarginPercentage());
        $this->assertNull((new Product(['price' => 1000]))->grossMarginPercentage());
    }

    public function test_order_item_snapshots_cost_price(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'status' => 'active', 'price' => 400000, 'sale_price' => null, 'cost_price' => 240000, 'stock' => 5]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2, 'price' => 400000]);

        $this->actingAs($user)->post('/checkout/review', ['shipping_name' => 'A', 'shipping_phone' => '0901234567', 'shipping_address' => 'số 1', 'shipping_province' => 'Quảng Ninh', 'payment_method' => 'cod']);
        $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1']);

        $item = Order::sole()->items()->sole();
        $this->assertSame(240000, $item->cost_price);

        // Đổi giá vốn sau khi bán: dòng đơn cũ giữ nguyên → báo cáo lịch sử không đổi
        $product->update(['cost_price' => 300000]);
        $this->assertSame(320000, $item->fresh()->grossProfit());
    }

    public function test_admin_can_set_cost_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'price' => 500000]);

        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()->assertSee('Giá vốn');
        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name, 'price' => 500000, 'cost_price' => 280000,
            'category_id' => $product->category_id, 'stock' => 10,
        ])->assertRedirect();

        $this->assertSame(280000, $product->fresh()->cost_price);
    }

    public function test_seeded_catalog_has_cost_price_with_35_to_45_percent_margin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Product::whereNull('cost_price')->count());
        Product::all()->each(fn (Product $p) => $this->assertTrue(
            $p->cost_price >= $p->price * 0.54 && $p->cost_price <= $p->price * 0.66,
            "{$p->name}: giá vốn {$p->cost_price} ngoài 55–65% giá {$p->price}"
        ));
    }
}
