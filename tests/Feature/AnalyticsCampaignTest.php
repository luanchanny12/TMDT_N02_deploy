<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Campaign;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsCampaignTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(array $attrs = []): Campaign
    {
        return Campaign::create(array_merge([
            'name' => 'Sale thu Facebook',
            'platform' => 'facebook',
            'utm_source' => 'facebook',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'sale-thu-fb',
            'landing_path' => '/',
            'spend' => 100000,
            'status' => 'active',
        ], $attrs));
    }

    private function product(int $price = 300000): Product
    {
        return Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'status' => 'active',
            'price' => $price,
            'sale_price' => null,
            'stock' => 20,
        ]);
    }

    private function placeOrder(User $user, Product $product): Order
    {
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        CartItem::firstOrCreate(['cart_id' => $cart->id, 'product_id' => $product->id], ['quantity' => 1, 'price' => $product->price]);

        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'A', 'shipping_phone' => '0981234567', 'shipping_address' => 'HN', 'payment_method' => 'cod',
        ]);
        $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1']);

        return Order::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    public function test_utm_visit_is_attributed_to_campaign_once_per_session(): void
    {
        $campaign = $this->campaign();

        $this->get('/?utm_source=facebook&utm_medium=cpc&utm_campaign=sale-thu-fb')->assertOk();
        $this->get('/products')->assertOk();

        $this->assertSame(1, AnalyticsEvent::where('type', 'visit')->count());
        $this->assertDatabaseHas('analytics_events', ['type' => 'visit', 'campaign_id' => $campaign->id, 'source' => 'facebook']);
    }

    public function test_referrer_without_utm_is_recorded_as_source(): void
    {
        $this->withHeader('Referer', 'https://l.facebook.com/l.php?u=abc')->get('/')->assertOk();

        $this->assertDatabaseHas('analytics_events', ['type' => 'visit', 'source' => 'facebook', 'campaign_id' => null]);
    }

    public function test_bots_and_admin_pages_are_not_tracked(): void
    {
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get('/')->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->withHeader('User-Agent', 'Mozilla/5.0')->get('/admin/dashboard')->assertOk();

        $this->assertSame(0, AnalyticsEvent::where('type', 'visit')->count());
    }

    public function test_order_from_campaign_link_is_attributed_and_counts_toward_roas(): void
    {
        $campaign = $this->campaign(['spend' => 100000]);
        $product = $this->product(300000);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/?utm_source=facebook&utm_medium=cpc&utm_campaign=sale-thu-fb');
        $this->actingAs($user)->get(route('products.show', $product))->assertOk();
        $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $order = $this->placeOrder($user, $product);

        $this->assertSame($campaign->id, $order->campaign_id);
        $this->assertSame('facebook', $order->traffic_source);
        foreach (['product_view', 'add_to_cart', 'purchase'] as $type) {
            $this->assertDatabaseHas('analytics_events', ['type' => $type, 'campaign_id' => $campaign->id]);
        }

        // Doanh thu 300.000 (đơn ≤ 500k có phí ship 30.000) → ROAS = 330.000 / 100.000
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.analytics.index', ['days' => 7]))
            ->assertOk()
            ->assertSee('Phễu chuyển đổi')
            ->assertSee('3,30x')
            ->assertSee('Sale thu Facebook');
    }

    public function test_conversion_rate_is_orders_over_visits(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'status' => 'delivered', 'total' => 500000]);
        Order::factory()->create(['user_id' => $user->id, 'status' => 'cancelled', 'total' => 900000]);
        foreach (range(1, 4) as $i) {
            AnalyticsEvent::create(['type' => 'visit', 'session_hash' => "s{$i}"]);
        }

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('25,00%'); // 1 đơn hợp lệ / 4 lượt truy cập
    }

    public function test_admin_creates_campaign_with_generated_utm_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.campaigns.store'), [
            'name' => 'Ra mắt BST Thu Đông',
            'platform' => 'tiktok',
            'landing_path' => '/danh-muc/giay-dep',
            'spend' => 250000,
            'status' => 'active',
        ])->assertRedirect(route('admin.campaigns.index'));

        $campaign = Campaign::firstOrFail();
        $this->assertSame('ra-mat-bst-thu-dong', $campaign->utm_campaign);
        $this->assertSame('tiktok', $campaign->utm_source);
        $this->assertStringContainsString('/danh-muc/giay-dep?utm_source=tiktok&utm_medium=cpc&utm_campaign=ra-mat-bst-thu-dong', $campaign->trackingUrl());

        $this->actingAs($admin)->get(route('admin.campaigns.index'))->assertOk()->assertSee('Ra mắt BST Thu Đông');
    }

    public function test_campaign_landing_must_be_internal_path(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.campaigns.store'), [
                'name' => 'Lừa đảo', 'platform' => 'facebook', 'landing_path' => '//evil.example',
                'spend' => 0, 'status' => 'active',
            ])->assertSessionHasErrors('landing_path');

        $this->assertSame(0, Campaign::count());
    }

    public function test_customer_cannot_access_analytics_or_campaigns(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.campaigns.index'))->assertForbidden();
    }
}
