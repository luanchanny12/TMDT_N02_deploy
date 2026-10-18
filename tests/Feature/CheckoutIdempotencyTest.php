<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function userWithCart(): User
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'status' => 'active',
            'stock' => 10,
            'price' => 200000,
            'sale_price' => null,
        ]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 200000]);

        return $user;
    }

    private function review(User $user): void
    {
        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'John Doe',
            'shipping_phone' => '0987654321',
            'shipping_address' => '123 Test St', 'shipping_province' => 'Quảng Ninh',
            'payment_method' => 'cod',
        ]);
    }

    public function test_double_submit_creates_only_one_order_and_redirects_to_it(): void
    {
        Mail::fake();
        $user = $this->userWithCart();
        $this->review($user);

        $first = $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1']);
        $second = $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1']);

        $order = Order::sole();
        $first->assertRedirect(route('orders.success', $order));
        $second->assertRedirect(route('orders.success', $order->id));
    }

    public function test_checkout_confirm_is_throttled(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1']);
        }

        $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1'])
            ->assertStatus(429);
    }
}
