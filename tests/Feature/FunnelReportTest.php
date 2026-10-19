<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Campaign;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Analytics\FunnelReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunnelReportTest extends TestCase
{
    use RefreshDatabase;

    private function range(): array
    {
        return [CarbonImmutable::now()->subDays(29)->startOfDay(), CarbonImmutable::now()->endOfDay()];
    }

    private function sessionEvents(string $hash, array $types, array $attrs = []): void
    {
        foreach ($types as $type) {
            AnalyticsEvent::create(array_merge(['type' => $type, 'session_hash' => $hash], $attrs));
        }
    }

    public function test_funnel_is_split_by_channel(): void
    {
        $campaign = Campaign::create([
            'name' => 'FB', 'platform' => 'facebook', 'utm_source' => 'facebook', 'utm_medium' => 'cpc',
            'utm_campaign' => 'fb', 'landing_path' => '/', 'spend' => 0, 'status' => 'active',
        ]);

        $this->sessionEvents('p1', ['visit', 'product_view', 'add_to_cart', 'begin_checkout'], ['campaign_id' => $campaign->id, 'source' => 'facebook']);
        $this->sessionEvents('o1', ['visit', 'product_view'], ['source' => 'google']);
        $this->sessionEvents('s1', ['visit'], ['source' => 'zalo']);
        $this->sessionEvents('r1', ['visit'], ['source' => 'webtretho.com']);
        $this->sessionEvents('d1', ['visit', 'visit']);

        $funnel = app(FunnelReportService::class)->funnelByChannel(...$this->range());

        $this->assertSame(1, $funnel['paid']['steps']['begin_checkout']);
        $this->assertSame(1, $funnel['organic']['steps']['product_view']);
        $this->assertSame(1, $funnel['social']['steps']['visit']);
        $this->assertSame(1, $funnel['referral']['steps']['visit']);
        $this->assertSame(1, $funnel['direct']['steps']['visit'], 'Đếm phiên duy nhất, không đếm số sự kiện');
        $this->assertSame(5, $funnel['all']['steps']['visit']);
        $this->assertSame(2, $funnel['all']['steps']['product_view']);
    }

    public function test_drop_off_flags_step_with_most_extra_orders(): void
    {
        $rows = app(FunnelReportService::class)->dropOffDiagnostic([
            'visit' => 1000, 'product_view' => 600, 'add_to_cart' => 60, 'begin_checkout' => 30, 'purchase' => 15,
        ], 5);

        // Xem SP → Thêm giỏ: 600 × 5% × (15/60) = 7,5 đơn — lớn nhất
        $this->assertEqualsWithDelta(7.5, $rows[1]['extra_orders'], 0.01);
        $this->assertTrue($rows[1]['priority']);
        $this->assertSame(1, collect($rows)->where('priority', true)->count());
        // Truy cập → Xem SP: 1000 × 5% × (15/600) = 1,25
        $this->assertEqualsWithDelta(1.3, $rows[0]['extra_orders'], 0.01);
    }

    public function test_unit_economics_uses_cost_price_snapshot(): void
    {
        $campaign = Campaign::create([
            'name' => 'FB', 'platform' => 'facebook', 'utm_source' => 'facebook', 'utm_medium' => 'cpc',
            'utm_campaign' => 'fb', 'landing_path' => '/', 'spend' => 200000, 'status' => 'active',
        ]);
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'price' => 500000, 'cost_price' => 300000]);

        foreach ([['campaign_id' => $campaign->id], ['campaign_id' => null]] as $i => $attrs) {
            $user = User::factory()->create(['role' => 'customer']);
            $order = Order::factory()->create(array_merge(['user_id' => $user->id, 'total' => 500000, 'status' => 'pending'], $attrs));
            OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Áo', 'price' => 500000, 'cost_price' => 300000, 'quantity' => 1, 'subtotal' => 500000]);
            AnalyticsEvent::create(['type' => 'purchase', 'session_hash' => "s{$i}", 'order_id' => $order->id, 'value' => 500000]);
        }

        // Admin đổi giá vốn về sau không làm sai số liệu đã bán
        $product->update(['cost_price' => 100000]);

        $e = app(FunnelReportService::class)->unitEconomics(...[...$this->range(), ['visit' => 100, 'purchase' => 2]]);

        $this->assertEqualsWithDelta(0.4, $e['gross_margin'], 0.0001);
        $this->assertEqualsWithDelta(2.5, $e['break_even_roas'], 0.0001);
        $this->assertSame(500000, $e['aov']);
        $this->assertEqualsWithDelta(200000, $e['ltv'], 0.01);
        $this->assertSame(1, $e['acquired_customers']);
        $this->assertEqualsWithDelta(200000, $e['cac'], 0.01);
        $this->assertEqualsWithDelta(1.0, $e['ltv_cac'], 0.0001);
        // CPC hòa vốn = 500.000 × 40% × 2% = 4.000
        $this->assertEqualsWithDelta(4000, $e['break_even_cpc'], 0.01);
    }

    public function test_review_step_records_begin_checkout_event(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'status' => 'active', 'price' => 300000, 'sale_price' => null, 'stock' => 5]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 300000]);

        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'A', 'shipping_phone' => '0901234567', 'shipping_address' => 'số 1',
            'shipping_province' => 'Quảng Ninh', 'payment_method' => 'cod',
        ])->assertRedirect(route('checkout.review.show'));

        $event = AnalyticsEvent::where('type', 'begin_checkout')->sole();
        $this->assertSame($user->id, $event->user_id);
        $this->assertGreaterThan(0, $event->value);
    }

    public function test_admin_analytics_page_shows_funnel_and_unit_economics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Phễu chuyển đổi theo kênh')
            ->assertSee('Chẩn đoán điểm rơi')
            ->assertSee('ROAS hòa vốn')
            ->assertSee('LTV / CAC');
    }
}
