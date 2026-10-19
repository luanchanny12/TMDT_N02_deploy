<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Kịch bản kiểm thử 6 luồng thanh toán (docs/payment-flows.md) chạy qua đúng luồng đặt hàng của website.
 */
class PaymentFlowsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['vnpay.hash_secret' => 'test-secret', 'vnpay.tmn_code' => 'TESTTMN']);

        $this->user = User::factory()->create(['role' => 'customer']);
        $this->product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'status' => 'active', 'price' => 400000, 'sale_price' => null, 'stock' => 10,
        ]);
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $this->product->id, 'quantity' => 2, 'price' => 400000]);
    }

    private function checkout(string $method)
    {
        $this->actingAs($this->user)->post('/checkout/review', [
            'shipping_name' => 'Lan', 'shipping_phone' => '0901234567', 'shipping_address' => 'số 1',
            'shipping_province' => 'Quảng Ninh', 'payment_method' => $method,
        ]);

        return $this->actingAs($this->user)->post(route('checkout.confirm'), ['agree_terms' => '1']);
    }

    private function vnpResult(Payment $payment, string $code = '00', ?int $amount = null): array
    {
        $data = [
            'vnp_TmnCode' => 'TESTTMN',
            'vnp_TxnRef' => $payment->transaction_code,
            'vnp_Amount' => (string) (($amount ?? $payment->amount) * 100),
            'vnp_ResponseCode' => $code,
            'vnp_TransactionStatus' => $code === '00' ? '00' : '02',
            'vnp_TransactionNo' => '14000001',
        ];
        ksort($data);
        $data['vnp_SecureHash'] = hash_hmac('sha512', collect($data)->map(fn ($v, $k) => urlencode($k).'='.urlencode($v))->implode('&'), 'test-secret');

        return $data;
    }

    private function cartQuantity(): int
    {
        return (int) CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $this->user->id))->sum('quantity');
    }

    public function test_flow_1_cod_order_is_placed_and_cart_cleared(): void
    {
        $this->checkout('cod')->assertRedirect();

        $order = Order::sole();
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('pending', $order->status);
        $this->assertSame(8, $this->product->fresh()->stock);
        $this->assertSame(0, $this->cartQuantity());
    }

    public function test_flow_2_vnpay_success_marks_order_paid(): void
    {
        $this->checkout('vnpay')->assertRedirect();
        $payment = Payment::sole();

        $this->get(route('payment.vnpay.return', $this->vnpResult($payment)))->assertRedirect(route('orders.success', $payment->order_id));

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', Order::sole()->payment_status);
    }

    public function test_flow_3_customer_cancels_on_vnpay_restores_stock_and_cart(): void
    {
        $this->checkout('vnpay');
        $this->assertSame(0, $this->cartQuantity());

        $this->get(route('payment.vnpay.return', $this->vnpResult(Payment::sole(), '24')))
            ->assertOk()->assertSee('trả lại giỏ hàng');

        $this->assertSame('cancelled', Order::sole()->status);
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame(2, $this->cartQuantity(), 'Hủy giữa chừng: giỏ hàng còn nguyên');
    }

    public function test_flow_4_abandoned_payment_page_expires_and_cart_returns(): void
    {
        $this->checkout('vnpay');

        $this->travel(31)->minutes();
        $this->artisan('orders:expire-unpaid')->assertSuccessful();

        $this->assertSame('cancelled', Order::sole()->status);
        $this->assertSame('failed', Payment::sole()->status);
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame(2, $this->cartQuantity());
    }

    public function test_flow_5_late_and_repeated_results_are_idempotent(): void
    {
        $this->checkout('vnpay');
        $payment = Payment::sole();
        $success = $this->vnpResult($payment);

        // Trình duyệt quay về trước, IPN tới muộn và VNPay gửi lặp lại cùng mã tham chiếu
        $this->get(route('payment.vnpay.return', $success));
        $this->getJson(route('payment.vnpay.ipn', $success))->assertJson(['RspCode' => '02']);
        $this->getJson(route('payment.vnpay.ipn', $success))->assertJson(['RspCode' => '02']);

        $this->assertSame(1, AuditLog::where('action', 'payment_paid')->count(), 'Chỉ ghi nhận thanh toán một lần');
        $this->assertSame(8, $this->product->fresh()->stock);

        // Kết quả thành công tới sau khi đơn đã hết hạn: không mở lại đơn, báo admin hoàn tiền
        $this->product->update(['stock' => 10]);
        Order::query()->delete();
        CartItem::create(['cart_id' => Cart::where('user_id', $this->user->id)->value('id'), 'product_id' => $this->product->id, 'quantity' => 1, 'price' => 400000]);
        $this->checkout('vnpay');
        $late = Payment::latest('id')->first();
        $this->travel(31)->minutes();
        $this->artisan('orders:expire-unpaid');

        $this->getJson(route('payment.vnpay.ipn', $this->vnpResult($late)))->assertJson(['RspCode' => '02']);
        $this->assertSame('cancelled', $late->order->fresh()->status);
        $this->assertSame(1, AuditLog::where('action', 'payment_refund_required')->count());
    }

    public function test_flow_6_forged_signature_or_wrong_amount_is_rejected(): void
    {
        $this->checkout('vnpay');
        $payment = Payment::sole();

        $forged = $this->vnpResult($payment);
        $forged['vnp_SecureHash'] = str_repeat('0', 128);
        $this->getJson(route('payment.vnpay.ipn', $forged))->assertJson(['RspCode' => '97']);

        $this->getJson(route('payment.vnpay.ipn', $this->vnpResult($payment, '00', 1000)))->assertJson(['RspCode' => '04']);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', Order::sole()->payment_status);
    }

    public function test_double_submit_creates_single_order(): void
    {
        $this->checkout('cod');
        $this->actingAs($this->user)->post(route('checkout.confirm'), ['agree_terms' => '1']);

        $this->assertSame(1, Order::count());
    }
}
