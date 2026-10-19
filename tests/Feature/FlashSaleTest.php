<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => Category::factory()->create()->id,
            'status' => 'active',
            'price' => 500000,
            'sale_price' => null,
            'sale_ends_at' => null,
        ], $attrs));
    }

    public function test_flash_sale_section_hidden_without_real_deal(): void
    {
        $this->product(['name' => 'Áo giá gốc']);
        $this->product(['name' => 'Áo giảm không thời hạn', 'sale_price' => 400000]);

        $this->get(route('home'))->assertOk()->assertDontSee('FLASH SALE');
    }

    public function test_countdown_targets_real_end_time_shared_by_every_visitor(): void
    {
        $endsAt = now()->addHours(5)->startOfMinute();
        $this->product(['name' => 'Túi Flash', 'sale_price' => 350000, 'sale_ends_at' => $endsAt]);

        $html = $this->get(route('home'))->assertOk()->assertSee('FLASH SALE')->getContent();

        // Mốc kết thúc do server cấp (không còn tự đặt 2:45:30 hay lưu localStorage)
        $this->assertStringContainsString('endsAt: '.$endsAt->getTimestampMs(), $html);
        $this->assertStringNotContainsString('localStorage', $html);
        $this->assertStringNotContainsString('SESSION_DURATION', $html);
        $this->assertStringContainsString($endsAt->format('H:i d/m/Y'), $html);
    }

    public function test_expired_sale_returns_to_original_price_immediately(): void
    {
        $product = $this->product(['sale_price' => 300000, 'sale_ends_at' => now()->subMinute()]);

        $this->assertFalse($product->isOnSale());
        $this->assertSame(500000, $product->effectivePrice()); // giá khi đặt hàng
        $this->get(route('home'))->assertDontSee('FLASH SALE');
    }

    public function test_command_clears_expired_sales(): void
    {
        $expired = $this->product(['sale_price' => 300000, 'sale_ends_at' => now()->subMinute()]);
        $running = $this->product(['sale_price' => 300000, 'sale_ends_at' => now()->addHour()]);

        $this->artisan('products:end-expired-sales')->assertSuccessful();

        $this->assertNull($expired->fresh()->sale_price);
        $this->assertSame(300000, $running->fresh()->sale_price);
    }

    public function test_admin_flash_sale_requires_future_end_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product();
        $base = ['name' => $product->name, 'price' => 500000, 'sale_price' => 400000, 'category_id' => $product->category_id, 'stock' => 5];

        $this->actingAs($admin)->put(route('admin.products.update', $product), $base + ['flash_sale' => '1'])
            ->assertSessionHasErrors('sale_ends_at');
        $this->actingAs($admin)->put(route('admin.products.update', $product), $base + ['sale_ends_at' => now()->subDay()->format('Y-m-d\TH:i')])
            ->assertSessionHasErrors('sale_ends_at');

        $this->actingAs($admin)->put(route('admin.products.update', $product), $base + ['flash_sale' => '1', 'sale_ends_at' => now()->addDay()->format('Y-m-d\TH:i')])
            ->assertSessionHasNoErrors();
        $this->assertTrue($product->fresh()->isOnSale());
    }
}
