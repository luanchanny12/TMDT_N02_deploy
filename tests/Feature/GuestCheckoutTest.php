<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function guestCart(int $price = 200000): Product
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'status' => 'active',
            'price' => $price,
            'sale_price' => null,
            'stock' => 10,
        ]);
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);

        return $product;
    }

    private function shipping(array $overrides = []): array
    {
        return array_merge([
            'guest_email' => 'khach@example.com',
            'shipping_name' => 'Trần Thị Khách',
            'shipping_phone' => '0901234567',
            'shipping_province' => 'Quảng Ninh',
            'shipping_address' => '12 Trần Hưng Đạo, Hạ Long',
            'payment_method' => 'cod',
        ], $overrides);
    }

    public function test_guest_can_see_checkout_with_email_field(): void
    {
        $this->guestCart();

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('name="guest_email"', false)
            ->assertSee('Đăng nhập</a> để dùng mã giảm giá', false);
    }

    public function test_guest_completes_order_without_registering(): void
    {
        Notification::fake();
        Mail::fake();
        $product = $this->guestCart(200000);

        $this->post(route('checkout.review'), $this->shipping())->assertRedirect(route('checkout.review.show'));

        $user = User::where('email', 'khach@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Trần Thị Khách', $user->name);
        Notification::assertSentTo($user, ResetPassword::class);

        $this->post(route('checkout.confirm'), ['agree_terms' => '1'])->assertRedirect();

        $order = Order::where('user_id', $user->id)->sole();
        $this->assertSame(2, $order->items()->sum('quantity'));
        $this->assertSame('Quảng Ninh', $order->shipping_province);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertDatabaseHas('audit_logs', ['action' => 'guest_checkout_account_created', 'user_id' => $user->id]);
    }

    public function test_existing_email_must_log_in_instead(): void
    {
        User::factory()->create(['email' => 'khach@example.com']);
        $this->guestCart();

        $this->post(route('checkout.review'), $this->shipping())
            ->assertSessionHasErrors('guest_email');

        $this->assertGuest();
        $this->assertSame(0, Order::count());
    }

    public function test_guest_email_is_required_and_valid(): void
    {
        $this->guestCart();

        $this->post(route('checkout.review'), $this->shipping(['guest_email' => 'not-an-email']))
            ->assertSessionHasErrors('guest_email');
        $this->assertGuest();
    }

    public function test_guest_with_empty_cart_cannot_create_account(): void
    {
        $this->post(route('checkout.review'), $this->shipping())->assertRedirect(route('cart.index'));

        $this->assertSame(0, User::count());
    }

    public function test_province_must_be_valid(): void
    {
        $this->guestCart();

        $this->post(route('checkout.review'), $this->shipping(['shipping_province' => 'Tỉnh Không Tồn Tại']))
            ->assertSessionHasErrors('shipping_province');
    }
}
