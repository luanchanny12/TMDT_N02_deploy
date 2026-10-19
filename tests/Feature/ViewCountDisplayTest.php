<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewCountDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $views, array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => Category::factory()->create()->id,
            'status' => 'active',
            'views_count' => $views,
        ], $attrs));
    }

    public function test_formatted_views(): void
    {
        $this->assertSame('489', (new Product(['views_count' => 489]))->formattedViews());
        $this->assertSame('1,2K', (new Product(['views_count' => 1250]))->formattedViews());
        $this->assertSame('15K', (new Product(['views_count' => 15400]))->formattedViews());
    }

    public function test_product_page_shows_views_and_sold_count(): void
    {
        $product = $this->product(488);
        $order = Order::factory()->create(['user_id' => User::factory()->create()->id, 'status' => 'delivered']);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => 1000, 'quantity' => 3, 'subtotal' => 3000]);
        $cancelled = Order::factory()->create(['user_id' => User::factory()->create()->id, 'status' => 'cancelled']);
        OrderItem::create(['order_id' => $cancelled->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => 1000, 'quantity' => 50, 'subtotal' => 50000]);

        // Khách mở trang → lượt xem tăng lên 489 và hiển thị ngay
        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('489 lượt xem')
            ->assertSeeInOrder(['Đã bán', '3']) // đơn hủy không tính
            ->assertDontSee('Đã bán <strong class="text-[#3d3d3d]">53', false);
    }

    public function test_low_view_count_is_hidden(): void
    {
        $product = $this->product(3);

        $this->get(route('products.show', $product))->assertOk()->assertDontSee('lượt xem</span>', false);
        $this->get(route('products.index'))->assertDontSee('data-testid="view-count"', false);
    }

    public function test_product_card_shows_compact_views(): void
    {
        $this->product(1250);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('data-testid="view-count"', false)
            ->assertSee('1,2K');
    }
}
