<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
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

    public function test_guest_can_view_empty_cart(): void
    {
        $this->get('/cart')->assertStatus(200)->assertViewIs('cart.index');
    }

    public function test_guest_can_add_product_to_cart(): void
    {
        $product = $this->createProduct();

        $response = $this->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $this->assertEquals(2, $this->getGuestCartCount());
    }

    public function test_guest_cart_merges_on_login(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();

        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 2]);
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    private function getGuestCartCount(): int
    {
        return (int) array_sum(session()->get('guest_cart', []));
    }

    public function test_guest_cart_merges_on_register(): void
    {
        $product = $this->createProduct();

        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 2]);
        $this->post('/register', [
            'name' => 'Người Mới',
            'email' => 'moitai@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'moitai@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_authenticated_user_can_view_cart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/cart')->assertStatus(200)->assertViewIs('cart.index');
    }

    public function test_user_can_add_product_to_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $response = $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_same_product_increases_quantity(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 10]);

        $this->actingAs($user)->post('/cart/add', ['product_id' => $product->id, 'quantity' => 2]);
        $this->actingAs($user)->post('/cart/add', ['product_id' => $product->id, 'quantity' => 3]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_user_can_remove_item_from_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $this->actingAs($user)->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

        $cartItem = Cart::where('user_id', $user->id)->first()->items()->first();

        $this->actingAs($user)->post('/cart/remove', ['rowId' => $cartItem->id]);

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_user_can_clear_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $this->actingAs($user)->post('/cart/add', ['product_id' => $product->id, 'quantity' => 2]);
        $this->actingAs($user)->post('/cart/clear');

        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }

    public function test_guest_can_update_item(): void
    {
        $product = $this->createProduct();
        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

        $this->post('/cart/update', [
            'rowId' => $product->id,
            'quantity' => 5,
        ])->assertRedirect();

        $this->assertEquals(5, session('guest_cart')[$product->id]);
    }

    public function test_user_can_update_item(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->actingAs($user)->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

        $cartItem = Cart::where('user_id', $user->id)->first()->items()->first();

        $this->post('/cart/update', [
            'rowId' => $cartItem->id,
            'quantity' => 5,
        ])->assertRedirect();

        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 5]);
    }

    public function test_guest_can_remove_item(): void
    {
        $product = $this->createProduct();
        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/cart/remove', ['rowId' => $product->id])->assertRedirect();
        $this->assertEmpty(session('guest_cart'));
    }

    public function test_guest_can_clear_cart(): void
    {
        $product = $this->createProduct();
        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/cart/clear')->assertRedirect();
        $this->assertEmpty(session('guest_cart'));
    }

    public function test_add_returns_json(): void
    {
        $product = $this->createProduct();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])
            ->assertSuccessful()
            ->assertJson(['success' => true]);

        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])
            ->assertSuccessful()
            ->assertJson(['success' => true]);
    }

    public function test_add_invalid_variant(): void
    {
        $product = $this->createProduct();
        $this->post('/cart/add', ['product_id' => $product->id, 'variant_id' => 9999, 'quantity' => 1])
            ->assertRedirect(); // error

        $this->postJson('/cart/add', ['product_id' => $product->id, 'variant_id' => 9999, 'quantity' => 1])
            ->assertStatus(422);
    }

    public function test_guest_with_empty_cart_is_sent_back_to_cart(): void
    {
        // Khách vãng lai được vào checkout (không bắt đăng nhập), nhưng giỏ trống thì quay lại giỏ hàng
        $this->get('/checkout')->assertRedirect('/cart');
    }

    public function test_checkout_redirects_empty_cart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/checkout')->assertRedirect('/cart');
    }

    public function test_checkout_loads_with_items(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->actingAs($user)->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

        $this->get('/checkout')->assertSuccessful()->assertViewIs('checkout.index');
    }

    public function test_checkout_with_coupon(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->actingAs($user)->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

        Coupon::forceCreate([
            'code' => 'TEST50',
            'type' => 'fixed',
            'value' => 50000,
            'min_order_amount' => 0,
            'status' => 'active',
            'usage_limit' => 10,
            'used_count' => 0,
        ]);

        session()->put('coupon_code', 'TEST50');

        $this->get('/checkout')->assertSuccessful()->assertViewHas('discount', 50000);
    }

    public function test_update_returns_error_on_exception(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 5]);
        $this->actingAs($user)->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

        $cartItem = Cart::where('user_id', $user->id)->first()->items()->first();

        $this->post('/cart/update', [
            'rowId' => $cartItem->id,
            'quantity' => 100, // Exceeds stock
        ])->assertRedirect()->assertSessionHas('error');
    }
}
