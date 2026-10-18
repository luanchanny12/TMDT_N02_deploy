<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\AdminAlert;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function placeOrder(int $stock = 20): Order
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'status' => 'active', 'price' => 100000, 'sale_price' => null, 'stock' => $stock, 'name' => 'Áo Polo Test']);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2, 'price' => 100000]);

        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'Khách A', 'shipping_phone' => '0901234567', 'shipping_address' => 'số 1',
            'shipping_province' => 'Quảng Ninh', 'payment_method' => 'cod',
        ]);
        $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1']);

        return Order::where('user_id', $user->id)->sole();
    }

    private function titles(): array
    {
        return $this->admin->fresh()->notifications->pluck('data.title')->all();
    }

    public function test_new_order_notifies_admin(): void
    {
        $order = $this->placeOrder();

        $this->assertContains('Đơn hàng mới '.$order->order_code, $this->titles());
        $this->assertSame(1, $this->admin->unreadNotifications()->count());
    }

    public function test_low_stock_after_order_notifies_admin(): void
    {
        $this->placeOrder(stock: 6); // còn 4 ≤ ngưỡng 5

        $this->assertContains('Sắp hết hàng', $this->titles());
    }

    public function test_customer_cancel_notifies_admin(): void
    {
        $order = $this->placeOrder();

        app(OrderService::class)->cancelByCustomer($order);

        $this->assertContains('Khách hủy đơn '.$order->order_code, $this->titles());
    }

    public function test_new_review_notifies_admin(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'status' => 'active']);
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'delivered']);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => 1000, 'quantity' => 1, 'subtotal' => 1000]);

        $this->actingAs($user)->post(route('reviews.store', $product), ['rating' => 5, 'comment' => 'Áo đẹp, vải mát, đúng mô tả.']);

        $this->assertContains('Đánh giá mới chờ duyệt', $this->titles());
    }

    public function test_bell_feed_and_open_marks_read(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('data-testid="admin-bell"', false);

        $feed = $this->actingAs($this->admin)->getJson(route('admin.notifications.feed'))->assertOk()->json();
        $this->assertSame(1, $feed['unread']);

        $this->actingAs($this->admin)->get($feed['items'][0]['open_url'])
            ->assertRedirect(route('admin.orders.show', $order));
        $this->assertSame(0, $this->admin->unreadNotifications()->count());

        $this->actingAs($this->admin)->get(route('admin.notifications.index'))->assertOk()->assertSee($order->order_code);
    }

    public function test_customer_cannot_read_admin_notifications(): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('admin.notifications.feed'))->assertForbidden();
    }

    public function test_notification_only_redirects_inside_site(): void
    {
        $this->admin->notify(new AdminAlert('x', 'Lừa đảo', 'm', 'https://evil.example/phish'));
        $id = $this->admin->notifications()->first()->id;

        $this->actingAs($this->admin)->get(route('admin.notifications.open', $id))
            ->assertRedirect(route('admin.notifications.index'));
    }
}
