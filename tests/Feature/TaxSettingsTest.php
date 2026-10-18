<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmedMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Order\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function customerWithCart(int $price = 400000, int $qty = 1): User
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'status' => 'active',
            'price' => $price,
            'sale_price' => null,
            'stock' => 10,
        ]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => $qty, 'price' => $price]);

        return $user;
    }

    private function checkout(User $user): Order
    {
        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'Nguyễn Văn A',
            'shipping_phone' => '0981234567',
            'shipping_address' => 'Hà Nội', 'shipping_province' => 'Quảng Ninh',
            'payment_method' => 'cod',
        ]);
        $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1']);

        return Order::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    public function test_admin_can_enable_vat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()->assertSee('Thuế giá trị gia tăng');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'tax_enabled' => '1',
            'tax_rate' => '8',
            'tax_label' => 'Thuế VAT',
            'shipping_fee' => '30000',
            'free_shipping_threshold' => '500000',
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertSame(8.0, app(PricingService::class)->taxRate());
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings_updated', 'user_id' => $admin->id]);
    }

    public function test_customer_cannot_change_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.update'), ['tax_enabled' => '1', 'tax_rate' => '50'])
            ->assertForbidden();

        $this->assertSame(0.0, app(PricingService::class)->taxRate());
    }

    public function test_tax_rate_is_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'tax_enabled' => '1', 'tax_rate' => '150', 'tax_label' => 'VAT',
            'shipping_fee' => '30000', 'free_shipping_threshold' => '500000',
        ])->assertSessionHasErrors('tax_rate');
    }

    public function test_no_tax_by_default(): void
    {
        $order = $this->checkout($this->customerWithCart(400000));

        $this->assertSame(0, $order->tax_amount);
        $this->assertSame(430000, $order->total);
    }

    public function test_order_includes_vat_on_goods_after_discount(): void
    {
        Setting::put(['tax_enabled' => 1, 'tax_rate' => 10]);
        $user = $this->customerWithCart(400000);

        $this->actingAs($user)->get(route('checkout.index'))->assertOk()
            ->assertViewHas('pricingConfig', fn ($c) => $c['taxRate'] === 10.0 && $c['taxLabel'] === 'Thuế VAT');

        $order = $this->checkout($user);

        // 400.000 + ship 30.000 + VAT 10% của 400.000
        $this->assertSame(40000, $order->tax_amount);
        $this->assertSame(10.0, $order->tax_rate);
        $this->assertSame(470000, $order->total);
        $this->assertSame(470000, (int) Payment::where('order_id', $order->id)->value('amount'));

        $this->actingAs($user)->get(route('orders.show', $order))->assertSee('Thuế VAT (10%)');
    }

    public function test_changing_rate_does_not_change_existing_orders(): void
    {
        Setting::put(['tax_enabled' => 1, 'tax_rate' => 8]);
        $order = $this->checkout($this->customerWithCart(1000000));
        $this->assertSame(80000, $order->tax_amount);

        Setting::put(['tax_rate' => 10]);

        $order->refresh();
        $this->assertSame(80000, $order->tax_amount);
        $this->assertSame(1080000, $order->total);
    }

    public function test_free_shipping_threshold_is_configurable(): void
    {
        Setting::put(['shipping_fee' => 25000, 'free_shipping_threshold' => 300000]);

        $this->assertSame(0, app(PricingService::class)->shippingFor(300000));
        $this->assertSame(25000, app(PricingService::class)->shippingFor(299000));
    }

    public function test_confirmation_email_shows_real_total_and_vat(): void
    {
        Setting::put(['tax_enabled' => 1, 'tax_rate' => 8]);
        $order = $this->checkout($this->customerWithCart(1000000));

        $html = (new OrderConfirmedMail($order))->render();

        $this->assertStringContainsString('1.080.000₫', $html);
        $this->assertStringContainsString('Thuế VAT (8%)', $html);
    }
}
