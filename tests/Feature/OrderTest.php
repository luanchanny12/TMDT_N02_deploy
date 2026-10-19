<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $attrs = []): Product
    {
        $cat = Category::factory()->create();

        return Product::factory()->create(array_merge([
            'category_id' => $cat->id,
            'status' => 'active',
            'price' => 100000,
            'sale_price' => null,
            'stock' => 10,
        ], $attrs));
    }

    private function fillCart(User $user, Product $product, int $qty, float $price): Cart
    {
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'price' => $price,
        ]);

        return $cart;
    }

    private function postReview(User $user, array $overrides = [])
    {
        return $this->actingAs($user)->post('/checkout/review', array_merge([
            'shipping_name' => 'John Doe',
            'shipping_phone' => '0987654321',
            'shipping_address' => '123 Test St', 'shipping_province' => 'Quảng Ninh',
            'payment_method' => 'cod',
        ], $overrides));
    }

    private function postConfirm(User $user, array $overrides = [])
    {
        return $this->actingAs($user)->post('/checkout/confirm', array_merge([
            'agree_terms' => '1',
        ], $overrides));
    }

    public function test_guest_checkout_requires_shipping_info_and_later_steps_require_account(): void
    {
        // Khách vãng lai được gửi thông tin giao hàng (bắt buộc email + địa chỉ)...
        $this->post('/checkout/review')->assertSessionHasErrors(['guest_email', 'shipping_name', 'shipping_province']);
        $this->assertGuest();

        // ...còn bước xem lại / xác nhận chỉ dành cho người đã có tài khoản (tạo tự động ở bước trên)
        $this->get('/checkout/review')->assertRedirect('/login');
        $this->post('/checkout/confirm')->assertRedirect('/login');
    }

    public function test_cannot_checkout_with_empty_cart(): void
    {
        $user = User::factory()->create();

        $response = $this->postReview($user);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Giỏ hàng của bạn đang trống.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_requires_two_steps_and_agree_terms(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 5, 'price' => 200000]);
        $this->fillCart($user, $product, 2, 200000);

        // Confirm without review session → redirected to input
        $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1'])
            ->assertRedirect(route('checkout.index'));
        $this->assertDatabaseCount('orders', 0);

        // Review step saves session
        $this->postReview($user)->assertRedirect(route('checkout.review.show'));

        // Review page shows
        $this->actingAs($user)->get('/checkout/review')
            ->assertOk()
            ->assertSee('Xác nhận đơn hàng')
            ->assertSee('John Doe');

        // Confirm without agree_terms → error, no order
        $this->actingAs($user)->post('/checkout/confirm', [])
            ->assertRedirect(route('checkout.review.show'))
            ->assertSessionHas('error', 'Vui lòng đồng ý Điều kiện giao dịch chung');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_created_successfully_and_stock_decremented(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 5, 'price' => 200000]);
        $cart = $this->fillCart($user, $product, 2, 200000);

        $this->postReview($user)->assertRedirect(route('checkout.review.show'));
        $response = $this->postConfirm($user);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'subtotal' => 400000,
            'shipping_fee' => 30000, // < 500k -> 30k fee
            'total' => 430000,
            'payment_method' => 'cod',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3, // 5 - 2
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
        ]);

        // checkout session cleared after confirm
        $this->assertNull(session('checkout'));
    }

    public function test_free_shipping_applied_when_subtotal_exceeds_threshold(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 5, 'price' => 600000]);
        $this->fillCart($user, $product, 1, 600000);

        $this->postReview($user);
        $this->postConfirm($user);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'subtotal' => 600000,
            'shipping_fee' => 0, // >= 500k -> 0 fee
            'total' => 600000,
        ]);
    }

    public function test_cannot_checkout_if_stock_insufficient(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 2]); // only 2 in stock
        $this->fillCart($user, $product, 3, 100000); // ordering 3

        $this->postReview($user);
        $response = $this->postConfirm($user);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 2,
        ]);
    }

    public function test_user_can_view_orders_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/orders')->assertOk()->assertViewIs('orders.index');
    }

    public function test_user_can_view_order_details(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->get("/orders/{$order->id}")->assertOk()->assertViewIs('orders.show');
    }

    public function test_user_can_view_order_success_page(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->get("/orders/{$order->id}/success")->assertOk()->assertViewIs('orders.success');
    }

    public function test_show_review_redirects_if_cart_empty_after_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['checkout' => ['payment_method' => 'cod']])->get('/checkout/review')->assertRedirect(route('cart.index'));
    }

    public function test_show_review_warns_when_coupon_is_invalid(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->fillCart($user, $product, 1, 100000);
        $this->actingAs($user)
            ->withSession([
                'checkout' => [
                    'payment_method' => 'cod',
                    'shipping_name' => 'Name',
                    'shipping_phone' => 'Phone',
                    'shipping_address' => 'Address', 'shipping_province' => 'Quảng Ninh',
                ],
                'coupon_code' => 'INVALID',
            ])
            ->get('/checkout/review')
            ->assertOk()
            // Báo rõ thay vì âm thầm gỡ mã; tổng tiền hiển thị không trừ mã
            ->assertSee('Mã giảm giá không còn áp dụng được');
    }

    public function test_place_order_with_vnpay_redirects_to_vnpay(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 5, 'price' => 200000]);
        $this->fillCart($user, $product, 1, 200000);

        $this->postReview($user, ['payment_method' => 'vnpay'])->assertRedirect();

        $response = $this->postConfirm($user);
        $response->assertRedirect();
        $this->assertStringContainsString('vnpay', $response->headers->get('Location'));
    }
}
