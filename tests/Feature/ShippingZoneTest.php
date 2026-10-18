<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\Order\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ShippingZoneTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(string $province, int $price): Order
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'status' => 'active', 'price' => $price, 'sale_price' => null, 'stock' => 5]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => $price]);

        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'A', 'shipping_phone' => '0901234567', 'shipping_address' => 'số 1',
            'shipping_province' => $province, 'payment_method' => 'cod',
        ])->assertRedirect(route('checkout.review.show'));
        $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1'])->assertRedirect();

        return Order::where('user_id', $user->id)->sole();
    }

    public function test_vietnam_has_34_provinces_and_default_zones_cover_all(): void
    {
        $this->assertCount(34, ShippingZone::allProvinces());
        $this->assertSame(4, ShippingZone::count());
        $this->assertCount(34, ShippingZone::provinceMap());
    }

    public function test_fee_depends_on_province_zone(): void
    {
        $pricing = app(PricingService::class);

        $this->assertSame(20000, $pricing->shippingFor(200000, 'Hà Nội'));
        $this->assertSame(0, $pricing->shippingFor(300000, 'TP. Hồ Chí Minh')); // nội thành freeship từ 300k
        $this->assertSame(35000, $pricing->shippingFor(300000, 'Đà Nẵng'));
        $this->assertSame(30000, $pricing->shippingFor(300000, 'Cà Mau'));
        $this->assertSame(0, $pricing->shippingFor(500000, 'Cà Mau'));
    }

    public function test_order_stores_province_and_zone_fee(): void
    {
        $order = $this->orderFor('Đà Nẵng', 200000);

        $this->assertSame('Đà Nẵng', $order->shipping_province);
        $this->assertSame(35000, $order->shipping_fee);
        $this->assertSame(235000, $order->total);
    }

    public function test_vat_can_include_shipping(): void
    {
        Setting::put(['tax_enabled' => 1, 'tax_rate' => 10, 'tax_on_shipping' => 1]);

        $order = $this->orderFor('Đà Nẵng', 200000);

        // VAT 10% trên (200.000 + 35.000)
        $this->assertSame(23500, $order->tax_amount);
        $this->assertSame(258500, $order->total);
    }

    public function test_admin_creates_zone_and_province_cannot_belong_to_two_zones(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ShippingZone::query()->delete();

        $this->actingAs($admin)->post(route('admin.shipping-zones.store'), [
            'name' => 'Hải đảo', 'provinces' => ['Quảng Ninh', 'Khánh Hòa'], 'fee' => 50000, 'is_active' => '1',
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertSame(50000, app(PricingService::class)->shippingFor(100000, 'Khánh Hòa'));

        $this->actingAs($admin)->post(route('admin.shipping-zones.store'), [
            'name' => 'Trùng', 'provinces' => ['Khánh Hòa'], 'fee' => 1000, 'is_active' => '1',
        ])->assertSessionHasErrors('provinces');

        $this->actingAs($admin)->post(route('admin.shipping-zones.store'), [
            'name' => 'Sai tỉnh', 'provinces' => ['Hà Tây'], 'fee' => 1000,
        ])->assertSessionHasErrors('provinces.0');

        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()->assertSee('Hải đảo')->assertSee('Tính VAT trên cả phí vận chuyển');
    }

    public function test_review_page_explains_free_shipping(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'status' => 'active', 'price' => 8990000, 'sale_price' => null, 'stock' => 5]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 8990000]);

        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'A', 'shipping_phone' => '0901234567', 'shipping_address' => 'số 1',
            'shipping_province' => 'Lạng Sơn', 'payment_method' => 'cod',
        ]);

        $this->actingAs($user)->get(route('checkout.review.show'))
            ->assertOk()
            ->assertSee('Miền Bắc · phí 30.000 ₫')
            ->assertSee('miễn phí cho đơn từ 500.000 ₫');
    }

    public function test_unzoned_province_uses_default_fee(): void
    {
        ShippingZone::query()->delete();
        Setting::put(['shipping_fee' => 42000]);

        $this->assertSame(42000, app(PricingService::class)->shippingFor(100000, 'Sơn La'));
    }
}
