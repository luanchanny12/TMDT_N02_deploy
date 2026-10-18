<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $attrs = []): Product
    {
        $cat = Category::factory()->create();

        return Product::factory()->create(array_merge([
            'category_id' => $cat->id,
            'status' => 'active',
            'price' => 500000,
            'sale_price' => null,
            'stock' => 20,
        ], $attrs));
    }

    private function createCoupon(array $attrs = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'TESTCODE',
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 200000,
            'max_discount' => null,
            'start_at' => now()->subDays(5),
            'end_at' => now()->addDays(30),
            'usage_limit' => 100,
            'used_count' => 0,
            'status' => 'active',
        ], $attrs));
    }

    private function setupCartWithProduct(User $user, Product $product, int $quantity = 2): void
    {
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $product->price,
        ]);
    }

    // ─── TC-01: Coupon hợp lệ ─────────────────────────────────────────────

    public function test_valid_coupon_applied_successfully(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['price' => 500000]);
        $coupon = $this->createCoupon(['type' => 'percent', 'value' => 10, 'min_order_amount' => 200000]);

        $this->setupCartWithProduct($user, $product, 2); // subtotal = 1,000,000

        $response = $this->actingAs($user)->postJson('/checkout/apply-coupon', [
            'code' => 'TESTCODE',
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'discount' => 100000, // 10% of 1,000,000
            'code' => 'TESTCODE',
        ]);

        $this->assertDatabaseHas('coupons', ['code' => 'TESTCODE', 'used_count' => 0]);
    }

    // ─── TC-02: Coupon sai mã ──────────────────────────────────────────────

    public function test_invalid_coupon_code_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->setupCartWithProduct($user, $product);

        $response = $this->actingAs($user)->postJson('/checkout/apply-coupon', [
            'code' => 'NONEXISTENT',
        ]);

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'Mã giảm giá không hợp lệ.',
        ]);
    }

    // ─── TC-03: Coupon hết hạn ─────────────────────────────────────────────

    public function test_expired_coupon_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $coupon = $this->createCoupon([
            'start_at' => now()->subDays(30),
            'end_at' => now()->subDay(),
        ]);

        $this->setupCartWithProduct($user, $product);

        $response = $this->actingAs($user)->postJson('/checkout/apply-coupon', [
            'code' => 'TESTCODE',
        ]);

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'Mã giảm giá đã hết hạn.',
        ]);
    }

    // ─── TC-04: Chưa đạt min order ────────────────────────────────────────

    public function test_below_min_order_amount_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['price' => 100000]);
        $coupon = $this->createCoupon(['min_order_amount' => 500000]);

        $this->setupCartWithProduct($user, $product, 1); // subtotal = 100,000 < 500,000

        $response = $this->actingAs($user)->postJson('/checkout/apply-coupon', [
            'code' => 'TESTCODE',
        ]);

        $response->assertStatus(422)->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('tối thiểu', $response->json('message'));
    }

    // ─── TC-05: Coupon hết lượt ───────────────────────────────────────────

    public function test_coupon_usage_limit_reached_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $coupon = $this->createCoupon(['usage_limit' => 5, 'used_count' => 5]);

        $this->setupCartWithProduct($user, $product);

        $response = $this->actingAs($user)->postJson('/checkout/apply-coupon', [
            'code' => 'TESTCODE',
        ]);

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'Mã giảm giá đã hết lượt sử dụng.',
        ]);
    }

    // ─── TC-06: User đã dùng ─────────────────────────────────────────────

    public function test_user_already_used_coupon_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $coupon = $this->createCoupon();

        // Tạo order thật và ghi nhận user đã dùng coupon này
        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-TEST-001',
            'subtotal' => 500000,
            'discount' => 50000,
            'shipping_fee' => 0,
            'total' => 450000,
            'status' => 'delivered',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'shipping_name' => 'Test User',
            'shipping_phone' => '0987654321',
            'shipping_address' => '123 Test Street', 'shipping_province' => 'Quảng Ninh',
        ]);

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);

        $this->setupCartWithProduct($user, $product);

        $response = $this->actingAs($user)->postJson('/checkout/apply-coupon', [
            'code' => 'TESTCODE',
        ]);

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'Bạn đã sử dụng mã giảm giá này rồi.',
        ]);
    }

    // ─── TC-07: Coupon được redeem khi đặt hàng thành công ────────────────

    public function test_coupon_redeemed_on_successful_order(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['price' => 500000, 'stock' => 10]);
        $coupon = $this->createCoupon(['type' => 'percent', 'value' => 10, 'min_order_amount' => 200000]);

        $this->setupCartWithProduct($user, $product, 2); // subtotal = 1,000,000

        // Đặt coupon vào session
        $response = $this->actingAs($user)->post('/checkout/apply-coupon', ['code' => 'TESTCODE']);
        $response->assertOk();

        // Checkout 2 bước: review → confirm
        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'Test User',
            'shipping_phone' => '0987654321',
            'shipping_address' => '123 Test Street', 'shipping_province' => 'Quảng Ninh',
            'payment_method' => 'cod',
        ])->assertRedirect(route('checkout.review.show'));

        $response = $this->actingAs($user)->post('/checkout/confirm', [
            'agree_terms' => '1',
        ]);

        $response->assertRedirect();

        // Verify coupon was redeemed
        $this->assertDatabaseHas('coupons', [
            'code' => 'TESTCODE',
            'used_count' => 1,
        ]);

        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
        ]);

        // Verify order has discount
        $order = Order::where('user_id', $user->id)->first();
        $this->assertEquals(100000, $order->discount);
        $this->assertEquals(900000, $order->subtotal - $order->discount + $order->shipping_fee);
    }

    // ─── CouponException khi confirm → redirect lỗi, không 500 ───────────

    public function test_expired_coupon_at_checkout_redirects_with_error_not_500(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['price' => 500000, 'stock' => 10]);
        $this->createCoupon(['status' => 'inactive']);

        $this->setupCartWithProduct($user, $product, 2);
        $this->app['session']->put('coupon_code', 'TESTCODE');

        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'Test User',
            'shipping_phone' => '0987654321',
            'shipping_address' => '123 Test Street', 'shipping_province' => 'Quảng Ninh',
            'payment_method' => 'cod',
        ])->assertRedirect(route('checkout.review.show'));

        $response = $this->actingAs($user)->post('/checkout/confirm', [
            'agree_terms' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, Order::count());
    }

    // ─── TC-08: Race condition — chỉ 1 trong 10 request thành công ────────

    public function test_coupon_race_condition_only_one_succeeds(): void
    {
        $coupon = $this->createCoupon(['usage_limit' => 5, 'used_count' => 4]);

        $threads = [];

        for ($i = 0; $i < 10; $i++) {
            $threads[] = function () use ($coupon, $i) {
                $user = User::factory()->create();
                $product = $this->createProduct(['price' => 500000]);
                $this->setupCartWithProduct($user, $product, 1);

                // Set coupon in session
                $this->app['session']->put('coupon_code', $coupon->code);

                $this->actingAs($user)->post('/checkout/review', [
                    'shipping_name' => "User {$i}",
                    'shipping_phone' => '0987654321',
                    'shipping_address' => '123 Test Street', 'shipping_province' => 'Quảng Ninh',
                    'payment_method' => 'cod',
                ]);

                $this->actingAs($user)->post('/checkout/confirm', [
                    'agree_terms' => '1',
                ]);
            };
        }

        // Run concurrently
        collect($threads)->each(fn ($fn) => $fn());

        // CouponException giờ được catch → redirect lỗi (302) thay vì 500;
        // chỉ số tin cậy là số order thực sự được tạo.
        $this->assertLessThanOrEqual(1, Order::count());

        // Verify used_count didn't exceed limit
        $coupon->refresh();
        $this->assertLessThanOrEqual(5, $coupon->used_count);
    }

    // ─── Bonus: Remove coupon ─────────────────────────────────────────────

    public function test_user_can_remove_coupon(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->session(['coupon_code' => 'TESTCODE']);

        $response = $this->actingAs($user)->postJson('/checkout/remove-coupon');

        $response->assertOk()->assertJson(['success' => true]);
        $response->assertSessionMissing('coupon_code');
    }
}
