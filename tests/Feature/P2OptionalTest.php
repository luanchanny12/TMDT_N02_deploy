<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmedMail;
use App\Mail\OrderStatusChangedMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class P2OptionalTest extends TestCase
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

    // ─── T20: Queue email ────────────────────────────────────────────────────

    public function test_order_confirmed_mail_is_queued_not_sent(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        Mail::to($user->email)->queue(new OrderConfirmedMail($order));

        Mail::assertQueued(OrderConfirmedMail::class, 1);
        Mail::assertNotSent(OrderConfirmedMail::class);
    }

    public function test_order_status_changed_mail_is_queued(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        Mail::to($user->email)->queue(new OrderStatusChangedMail($order, 'pending'));

        Mail::assertQueued(OrderStatusChangedMail::class, 1);
    }

    public function test_mailables_implement_should_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new OrderConfirmedMail(
            Order::factory()->create(['user_id' => User::factory()->create()->id])
        ));
        $this->assertInstanceOf(ShouldQueue::class, new OrderStatusChangedMail(
            Order::factory()->create(['user_id' => User::factory()->create()->id]),
            'pending'
        ));
    }

    // ─── T22: Product views tracking ─────────────────────────────────────────

    public function test_product_show_increments_views_count(): void
    {
        $product = $this->createProduct(['views_count' => 0]);

        $this->get(route('products.show', $product))->assertOk();

        $this->assertSame(1, (int) $product->fresh()->views_count);

        $this->get(route('products.show', $product))->assertOk();
        $this->assertSame(2, (int) $product->fresh()->views_count);
    }

    public function test_admin_dashboard_shows_top_viewed_products(): void
    {
        $p1 = $this->createProduct(['name' => 'SP Xem Nhieu Nhat', 'views_count' => 99]);
        $p2 = $this->createProduct(['name' => 'SP Xem It', 'views_count' => 1]);

        $admin = User::factory()->admin()->create();
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Sản phẩm xem nhiều')
            ->assertSee('SP Xem Nhieu Nhat');
    }

    // ─── T23: Variants ───────────────────────────────────────────────────────

    public function test_product_with_variants_shows_selectors_on_detail(): void
    {
        $product = $this->createProduct(['stock' => 0]);
        ProductVariant::create(['product_id' => $product->id, 'size' => 'M', 'color' => 'Đen', 'stock' => 5]);
        ProductVariant::create(['product_id' => $product->id, 'size' => 'L', 'color' => 'Trắng', 'stock' => 3]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee('Phân loại:')
            ->assertSee('name="variant_id"', false);
    }

    public function test_add_product_with_variant_to_cart(): void
    {
        $product = $this->createProduct(['stock' => 0]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Đen',
            'stock' => 5,
        ]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect(route('cart.index'));

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_cannot_exceed_variant_stock(): void
    {
        $product = $this->createProduct(['stock' => 0]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'S',
            'color' => 'Xanh',
            'stock' => 1,
        ]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_guest_can_add_variant_to_cart(): void
    {
        $product = $this->createProduct(['stock' => 0]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'XL',
            'color' => 'Hồng',
            'stock' => 4,
        ]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect(route('cart.index'));
        $this->assertSame(1, session('guest_cart')[$product->id.':'.$variant->id] ?? 0);
    }

    public function test_checkout_decrements_variant_stock(): void
    {
        $product = $this->createProduct(['stock' => 0]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Đen',
            'stock' => 5,
        ]);

        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 100000,
        ]);

        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'Test',
            'shipping_phone' => '0900000000',
            'shipping_address' => 'HN', 'shipping_province' => 'Quảng Ninh',
            'payment_method' => 'cod',
        ])->assertRedirect(route('checkout.review.show'));

        $this->actingAs($user)->post('/checkout/confirm', [
            'agree_terms' => '1',
        ])->assertRedirect();

        $this->assertSame(3, (int) $variant->fresh()->stock);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'size' => 'M',
            'color' => 'Đen',
            'quantity' => 2,
        ]);
    }

    public function test_admin_can_sync_variants_on_product_update(): void
    {
        $product = $this->createProduct();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'sale_price' => null,
            'category_id' => $product->category_id,
            'stock' => 10,
            'brand' => $product->brand,
            'status' => 'active',
            'variants' => [
                ['id' => null, 'size' => 'M', 'color' => 'Đen', 'stock' => 4, 'price' => null],
                ['id' => null, 'size' => 'L', 'color' => 'Trắng', 'stock' => 6, 'price' => null],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Đen',
            'stock' => 4,
        ]);
        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(10, (int) $product->fresh()->stock);
    }
}
