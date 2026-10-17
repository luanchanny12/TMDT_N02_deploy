<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tạo đơn đã trừ kho (giống sau khi placeOrder) với 1 sản phẩm x2.
     *
     * @return array{0: Order, 1: Product}
     */
    private function makeOrderWithReservedStock(array $orderAttributes = []): array
    {
        $product = Product::factory()->create(['stock' => 3, 'status' => 'active']);
        $order = Order::factory()->create(array_merge([
            'status' => 'pending',
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
            'total' => 200000,
        ], $orderAttributes));
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100000,
            'quantity' => 2,
            'subtotal' => 200000,
        ]);

        return [$order, $product];
    }

    private function sign(array $data): array
    {
        $data += ['vnp_TmnCode' => config('vnpay.tmn_code')];
        ksort($data);
        $hashData = collect($data)
            ->map(fn ($v, $k) => urlencode($k).'='.urlencode($v))
            ->implode('&');
        $data['vnp_SecureHash'] = hash_hmac('sha512', $hashData, config('vnpay.hash_secret'));

        return $data;
    }

    public function test_failed_vnpay_payment_cancels_order_and_restores_stock_and_coupon(): void
    {
        config(['vnpay.hash_secret' => 'test-secret']);
        [$order, $product] = $this->makeOrderWithReservedStock();
        $coupon = Coupon::factory()->create(['used_count' => 1]);
        CouponUsage::create(['coupon_id' => $coupon->id, 'user_id' => $order->user_id, 'order_id' => $order->id]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'vnpay',
            'transaction_code' => 'VNP-FAIL-1',
            'amount' => 200000,
            'status' => 'pending',
        ]);

        $result = app(PaymentService::class)->processVNPayIpn($this->sign([
            'vnp_TxnRef' => 'VNP-FAIL-1',
            'vnp_Amount' => '20000000',
            'vnp_ResponseCode' => '24',
        ]));

        $this->assertSame('00', $result['RspCode']);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(0, $coupon->fresh()->used_count);
        $this->assertDatabaseMissing('coupon_usages', ['order_id' => $order->id]);
    }

    public function test_expire_command_cancels_only_stale_unpaid_vnpay_orders(): void
    {
        [$stale, $staleProduct] = $this->makeOrderWithReservedStock();
        $stale->forceFill(['created_at' => now()->subHour()])->save();
        Payment::create(['order_id' => $stale->id, 'method' => 'vnpay', 'transaction_code' => 'VNP-OLD', 'amount' => 200000, 'status' => 'pending']);

        [$fresh, $freshProduct] = $this->makeOrderWithReservedStock();

        [$cod, $codProduct] = $this->makeOrderWithReservedStock(['payment_method' => 'cod']);
        $cod->forceFill(['created_at' => now()->subHour()])->save();

        $this->artisan('orders:expire-unpaid', ['--minutes' => 30])->assertSuccessful();

        $this->assertSame('cancelled', $stale->fresh()->status);
        $this->assertSame('failed', $stale->fresh()->payment_status);
        $this->assertSame('failed', Payment::where('order_id', $stale->id)->value('status'));
        $this->assertSame(5, $staleProduct->fresh()->stock);

        $this->assertSame('pending', $fresh->fresh()->status);
        $this->assertSame(3, $freshProduct->fresh()->stock);
        $this->assertSame('pending', $cod->fresh()->status);
        $this->assertSame(3, $codProduct->fresh()->stock);
    }

    public function test_late_ipn_after_expiry_does_not_reopen_order(): void
    {
        config(['vnpay.hash_secret' => 'test-secret']);
        [$order] = $this->makeOrderWithReservedStock();
        $order->forceFill(['created_at' => now()->subHour()])->save();
        Payment::create(['order_id' => $order->id, 'method' => 'vnpay', 'transaction_code' => 'VNP-LATE', 'amount' => 200000, 'status' => 'pending']);

        $this->artisan('orders:expire-unpaid')->assertSuccessful();

        $result = app(PaymentService::class)->processVNPayIpn($this->sign([
            'vnp_TxnRef' => 'VNP-LATE',
            'vnp_Amount' => '20000000',
            'vnp_ResponseCode' => '00',
        ]));

        $this->assertSame('02', $result['RspCode']);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_cancelling_twice_restores_stock_only_once(): void
    {
        [$order, $product] = $this->makeOrderWithReservedStock(['payment_method' => 'cod']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch(route('admin.orders.update-status', $order), ['status' => 'cancelled']);
        $this->actingAs($admin)->patch(route('admin.orders.update-status', $order), ['status' => 'cancelled']);

        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_customer_can_cancel_own_pending_order(): void
    {
        [$order, $product] = $this->makeOrderWithReservedStock(['payment_method' => 'cod']);

        $this->actingAs($order->user)
            ->post(route('orders.cancel', $order->id))
            ->assertRedirect(route('orders.show', $order->id))
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_customer_cannot_cancel_confirmed_or_paid_order(): void
    {
        [$confirmed] = $this->makeOrderWithReservedStock(['payment_method' => 'cod', 'status' => 'confirmed']);
        [$paid] = $this->makeOrderWithReservedStock(['payment_status' => 'paid']);

        $this->actingAs($confirmed->user)
            ->post(route('orders.cancel', $confirmed->id))
            ->assertSessionHas('error');
        $this->actingAs($paid->user)
            ->post(route('orders.cancel', $paid->id))
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $confirmed->fresh()->status);
        $this->assertSame('pending', $paid->fresh()->status);
    }

    public function test_customer_cannot_cancel_someone_elses_order(): void
    {
        [$order] = $this->makeOrderWithReservedStock(['payment_method' => 'cod']);

        $this->actingAs(User::factory()->create())
            ->post(route('orders.cancel', $order->id))
            ->assertNotFound();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_order_page_shows_payment_status_and_cancel_button(): void
    {
        [$order] = $this->makeOrderWithReservedStock();

        $this->actingAs($order->user)
            ->get(route('orders.show', $order->id))
            ->assertOk()
            ->assertSee('Chưa thanh toán')
            ->assertSee('Hủy đơn hàng');
    }

    public function test_admin_cannot_confirm_unpaid_vnpay_order(): void
    {
        [$order] = $this->makeOrderWithReservedStock();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.update-status', $order), ['status' => 'shipping'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_admin_can_confirm_paid_vnpay_order(): void
    {
        [$order] = $this->makeOrderWithReservedStock(['payment_status' => 'paid']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.update-status', $order), ['status' => 'confirmed'])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'confirmed']);
    }

    public function test_admin_order_page_shows_payment_status(): void
    {
        [$order] = $this->makeOrderWithReservedStock();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Chưa thanh toán')
            ->assertSee('không thể xác nhận/giao hàng');
    }
}
