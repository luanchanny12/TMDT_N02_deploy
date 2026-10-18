<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_login_success_writes_audit_log(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login_success',
        ]);
    }

    public function test_login_failed_writes_audit_log_via_auth_service(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login_failed',
        ]);
    }

    public function test_logout_writes_audit_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
        ]);
    }

    public function test_order_created_writes_audit_log(): void
    {
        $user = User::factory()->create();
        $cat = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $cat->id,
            'status' => 'active',
            'price' => 100000,
            'stock' => 10,
        ]);

        Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => Cart::where('user_id', $user->id)->first()->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100000,
        ]);

        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'A',
            'shipping_phone' => '098',
            'shipping_address' => 'HN', 'shipping_province' => 'Quảng Ninh',
            'payment_method' => 'cod',
        ]);

        $this->actingAs($user)->post('/checkout/confirm', ['agree_terms' => '1']);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Order',
            'entity_id' => $order->id,
            'action' => 'order_created',
        ]);
    }

    public function test_admin_order_status_change_writes_audit_log(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $cat = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $cat->id, 'status' => 'active']);

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-AUDIT-1',
            'subtotal' => 100000,
            'discount' => 0,
            'shipping_fee' => 30000,
            'total' => 130000,
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'shipping_name' => 'A',
            'shipping_phone' => '098',
            'shipping_address' => 'HN', 'shipping_province' => 'Quảng Ninh',
        ]);

        $this->actingAs($admin)
            ->patch("/admin/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Order',
            'entity_id' => $order->id,
            'action' => 'order_status_changed',
        ]);
    }

    public function test_admin_toggle_user_status_writes_audit_log(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch("/admin/users/{$customer->id}/toggle-status")
            ->assertStatus(302);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'User',
            'entity_id' => $customer->id,
            'action' => 'user_blocked',
        ]);
    }

    public function test_admin_review_approve_reject_write_audit_log(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $cat = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $cat->id, 'status' => 'active']);

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-REV-1',
            'subtotal' => 100000,
            'discount' => 0,
            'shipping_fee' => 0,
            'total' => 100000,
            'status' => 'delivered',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'shipping_name' => 'A',
            'shipping_phone' => '098',
            'shipping_address' => 'HN', 'shipping_province' => 'Quảng Ninh',
        ]);

        $review = Review::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'Good',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch("/admin/reviews/{$review->id}/approve")
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'review_approved',
            'entity_type' => 'Review',
            'entity_id' => $review->id,
        ]);

        $this->actingAs($admin)
            ->patch("/admin/reviews/{$review->id}/reject")
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'review_rejected',
            'entity_type' => 'Review',
            'entity_id' => $review->id,
        ]);
    }

    public function test_admin_can_view_audit_logs_page(): void
    {
        $admin = $this->makeAdmin();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'login_success',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get('/admin/audit-logs')
            ->assertOk()
            ->assertSee('login_success');
    }

    public function test_customer_cannot_view_audit_logs(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get('/admin/audit-logs')
            ->assertForbidden();
    }

    public function test_admin_can_filter_audit_logs_by_action(): void
    {
        $admin = $this->makeAdmin();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'test_action_A',
            'ip_address' => '127.0.0.1',
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'test_action_B',
            'ip_address' => '127.0.0.2',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/audit-logs?action=test_action_B')
            ->assertOk();

        $response->assertViewHas('logs', function ($logs) {
            return $logs->count() === 1 && $logs->first()->action === 'test_action_B';
        });
    }

    public function test_admin_can_search_audit_logs(): void
    {
        $admin = $this->makeAdmin();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'search_action_A',
            'ip_address' => '192.168.1.1',
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'search_action_B',
            'ip_address' => '10.0.0.1',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/audit-logs?search=192.168')
            ->assertOk();

        $response->assertViewHas('logs', function ($logs) {
            return $logs->count() === 1 && $logs->first()->action === 'search_action_A';
        });
    }
}
