<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Services\Order\PricingService;
use Database\Seeders\PromoCouponSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoCouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_and_keeps_admin_changes(): void
    {
        $this->seed(PromoCouponSeeder::class);
        Coupon::where('code', 'WELCOME10')->update(['max_discount' => 70000]);
        $this->seed(PromoCouponSeeder::class);

        $this->assertSame(1, Coupon::where('code', 'WELCOME10')->count());
        $this->assertSame(70000, Coupon::where('code', 'WELCOME10')->value('max_discount'));
    }

    public function test_welcome10_advertised_in_banner_can_be_applied(): void
    {
        $this->seed(PromoCouponSeeder::class);
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'status' => 'active', 'price' => 1125000, 'sale_price' => null, 'stock' => 5]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 1125000]);

        $this->actingAs($user)->get(route('checkout.index'))->assertSee('WELCOME10');

        $this->actingAs($user)->postJson(route('checkout.apply-coupon'), ['code' => 'welcome10'])
            ->assertOk()->assertJson(['success' => true]);
    }

    public function test_banner_shows_real_free_shipping_threshold_and_hides_unusable_code(): void
    {
        $this->seed(PromoCouponSeeder::class);

        $threshold = number_format(app(PricingService::class)->nationwideFreeShippingThreshold(), 0, ',', '.');
        $this->get('/')->assertSee("Miễn phí vận chuyển cho đơn hàng từ {$threshold}₫", false)->assertSee('WELCOME10');

        Coupon::where('code', 'WELCOME10')->update(['status' => 'inactive']);
        $this->get('/')->assertDontSee('WELCOME10');
    }
}
