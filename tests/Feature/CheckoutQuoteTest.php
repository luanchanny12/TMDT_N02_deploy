<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutQuoteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'status' => 'active', 'price' => 400000, 'sale_price' => null, 'stock' => 10,
        ]);
    }

    private function toReview(?int $variantId = null): void
    {
        $this->actingAs($this->user)->post(route('cart.add'), ['product_id' => $this->product->id, 'variant_id' => $variantId, 'quantity' => 1]);
        $this->actingAs($this->user)->post('/checkout/review', [
            'shipping_name' => 'A', 'shipping_phone' => '0901234567', 'shipping_address' => 'số 1',
            'shipping_province' => 'Quảng Ninh', 'payment_method' => 'cod',
        ])->assertRedirect(route('checkout.review.show'));
    }

    public function test_price_raised_after_review_is_not_charged_silently(): void
    {
        $this->toReview();
        $this->actingAs($this->user)->get(route('checkout.review.show'))->assertSee('430.000')->assertSee('Giá trên được giữ đến');

        // Admin tăng giá trong lúc khách đang ở trang xác nhận
        $this->product->update(['price' => 450000]);

        $this->actingAs($this->user)->post(route('checkout.confirm'), ['agree_terms' => '1'])
            ->assertRedirect(route('checkout.review.show'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, '430.000₫ thành 480.000₫'));
        $this->assertSame(0, Order::count());

        // Khách xem lại giá mới rồi xác nhận → đơn đúng giá đã xem lần 2
        $this->actingAs($this->user)->get(route('checkout.review.show'))->assertSee('480.000');
        $this->actingAs($this->user)->post(route('checkout.confirm'), ['agree_terms' => '1']);
        $this->assertSame(480000, Order::sole()->total);
    }

    public function test_tax_change_after_review_requires_reconfirmation(): void
    {
        $this->toReview();
        Setting::put(['tax_enabled' => 1, 'tax_rate' => 10]);

        $this->actingAs($this->user)->post(route('checkout.confirm'), ['agree_terms' => '1'])
            ->assertRedirect(route('checkout.review.show'));
        $this->assertSame(0, Order::count());
    }

    public function test_quote_expires_after_15_minutes(): void
    {
        $this->toReview();
        $this->travel(16)->minutes();

        $this->actingAs($this->user)->post(route('checkout.confirm'), ['agree_terms' => '1'])
            ->assertRedirect(route('checkout.review.show'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'quá 15 phút'));
        $this->assertSame(0, Order::count());
    }

    public function test_unchanged_quote_places_order(): void
    {
        $this->toReview();
        $this->travel(10)->minutes();

        $this->actingAs($this->user)->post(route('checkout.confirm'), ['agree_terms' => '1'])->assertRedirect();
        $this->assertSame(430000, Order::sole()->total);
    }

    public function test_review_shows_variant_upcharge_like_the_final_order(): void
    {
        $xl = ProductVariant::create(['product_id' => $this->product->id, 'size' => 'XL', 'color' => 'Đen', 'stock' => 5, 'price' => 420000]);
        $this->toReview($xl->id);

        // Trước đây trang xác nhận hiện giá gốc 400.000, còn đơn tính 420.000
        $this->actingAs($this->user)->get(route('checkout.review.show'))->assertSee('420.000')->assertSee('450.000');
        $this->actingAs($this->user)->post(route('checkout.confirm'), ['agree_terms' => '1']);
        $this->assertSame(450000, Order::sole()->total);
    }
}
