<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class LogicReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => Category::factory()->create()->id,
            'status' => 'active',
            'price' => 200000,
            'sale_price' => null,
            'stock' => 20,
        ], $attrs));
    }

    private function checkout(User $user): void
    {
        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'A', 'shipping_phone' => '0901234567', 'shipping_address' => 'số 1',
            'shipping_province' => 'Quảng Ninh', 'payment_method' => 'cod',
        ]);
        $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1']);
    }

    public function test_old_cart_item_without_size_cannot_be_ordered_once_product_has_variants(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 200000]);

        // Admin thêm size sau khi khách đã bỏ sản phẩm vào giỏ
        ProductVariant::create(['product_id' => $product->id, 'size' => 'M', 'color' => 'Đen', 'stock' => 5]);

        $this->checkout($user);

        $this->assertSame(0, Order::count());
        $this->assertStringContainsString('Vui lòng chọn size/màu', (string) session('error'));
    }

    public function test_variant_order_decrements_variant_and_product_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['stock' => 5]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'size' => 'M', 'color' => 'Đen', 'stock' => 5]);
        $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2]);

        $this->checkout($user);

        $item = Order::sole()->items()->sole();
        $this->assertSame('M', $item->size);
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_logged_in_guest_checkout_customer_can_set_password_from_email(): void
    {
        $user = User::factory()->create(['email' => 'khach@example.com']);
        $token = Password::createToken($user);

        // Khách vãng lai được đăng nhập tự động sau checkout rồi mới mở link trong email
        $this->actingAs($user)->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();

        $this->actingAs($user)->post(route('password.update'), [
            'token' => $token,
            'email' => 'khach@example.com',
            'password' => 'MatKhauMoi123',
            'password_confirmation' => 'MatKhauMoi123',
        ])->assertRedirect(route('orders.index'));

        $this->assertTrue(\Hash::check('MatKhauMoi123', $user->fresh()->password));
    }

    public function test_notification_link_to_lookalike_domain_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->notify(new AdminAlert('x', 't', 'm', rtrim(url('/'), '/').'.evil.example/phish'));

        $this->actingAs($admin)
            ->get(route('admin.notifications.open', $admin->notifications()->first()->id))
            ->assertRedirect(route('admin.notifications.index'));
    }

    public function test_low_stock_alert_for_single_variant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $product = $this->product(['stock' => 40]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'size' => 'M', 'color' => 'Đen', 'stock' => 3]);
        ProductVariant::create(['product_id' => $product->id, 'size' => 'L', 'color' => 'Đen', 'stock' => 37]);
        $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1]);

        $this->checkout($user);

        $this->assertContains('Sắp hết size/màu', $admin->fresh()->notifications->pluck('data.title')->all());
    }

    public function test_admin_viewing_product_does_not_inflate_view_count(): void
    {
        $product = $this->product(['views_count' => 10]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('products.show', $product))->assertOk();
        $this->assertSame(10, $product->fresh()->views_count);

        $this->actingAs(User::factory()->create())->get(route('products.show', $product))->assertOk();
        $this->assertSame(11, $product->fresh()->views_count);
    }

    public function test_edit_from_review_prefills_checkout_form(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'Nguyễn Thị Mai', 'shipping_phone' => '0912345678', 'shipping_address' => '45 Lê Lợi',
            'shipping_province' => 'Huế', 'payment_method' => 'vnpay',
        ]);

        $this->actingAs($user)->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('value="Nguyễn Thị Mai"', false)
            ->assertSee('0912345678')
            ->assertSee('45 Lê Lợi');
    }
}
