<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\VNPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function sign(array $data, ?string $secret = null): array
    {
        $data += ['vnp_TmnCode' => config('vnpay.tmn_code')];
        ksort($data);
        $hashData = collect($data)
            ->map(fn ($v, $k) => urlencode($k).'='.urlencode($v))
            ->implode('&');
        $data['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $secret ?? config('vnpay.hash_secret'));

        return $data;
    }

    private function pendingVnpayOrder(int $amount = 200000): array
    {
        $order = Order::factory()->create([
            'user_id' => User::factory()->create()->id,
            'total' => $amount,
            'status' => 'pending',
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'vnpay',
            'transaction_code' => 'VNP-HARD-'.uniqid(),
            'amount' => $amount,
            'status' => 'pending',
        ]);

        return [$order, $payment];
    }

    public function test_missing_hash_secret_rejects_every_callback(): void
    {
        config(['vnpay.hash_secret' => '']);

        // Kẻ tấn công tự ký bằng secret rỗng
        $forged = $this->sign(['vnp_TxnRef' => 'X', 'vnp_Amount' => '100', 'vnp_ResponseCode' => '00'], '');

        $this->assertFalse(app(VNPayService::class)->verifyCallback($forged));
    }

    public function test_callback_for_another_merchant_is_rejected(): void
    {
        $data = $this->sign(['vnp_TmnCode' => 'OTHERSHOP', 'vnp_TxnRef' => 'X', 'vnp_ResponseCode' => '00']);

        $this->assertFalse(app(VNPayService::class)->verifyCallback($data));
    }

    public function test_response_code_00_with_failed_transaction_status_is_not_paid(): void
    {
        [$order, $payment] = $this->pendingVnpayOrder();

        $result = app(PaymentService::class)->processVNPayIpn($this->sign([
            'vnp_TxnRef' => $payment->transaction_code,
            'vnp_Amount' => (string) ($payment->amount * 100),
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '02',
        ]));

        $this->assertSame('00', $result['RspCode']);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertNotSame('paid', $order->fresh()->payment_status);
    }

    public function test_payment_after_customer_cancel_does_not_reopen_order_and_flags_refund(): void
    {
        [$order, $payment] = $this->pendingVnpayOrder();

        app(OrderService::class)->cancelByCustomer($order);
        $this->assertSame('failed', $payment->fresh()->status);

        // Khách vẫn trả tiền bằng link cũ
        $result = app(PaymentService::class)->processVNPayIpn($this->sign([
            'vnp_TxnRef' => $payment->transaction_code,
            'vnp_Amount' => (string) ($payment->amount * 100),
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TransactionNo' => '14000000',
        ]));

        $this->assertSame('02', $result['RspCode']);
        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertNotSame('paid', $order->payment_status);
        $this->assertTrue(AuditLog::where('action', 'payment_refund_required')->where('entity_id', $payment->id)->exists());
    }

    public function test_payment_url_expires_with_order(): void
    {
        config(['vnpay.expire_minutes' => 30]);
        [$order, $payment] = $this->pendingVnpayOrder();

        $url = app(VNPayService::class)->buildPaymentUrl($order, $payment->transaction_code, '127.0.0.1');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('vnp_ExpireDate', $query);
        $this->assertSame(now('Asia/Ho_Chi_Minh')->addMinutes(30)->format('YmdH'), substr($query['vnp_ExpireDate'], 0, 10));
        $this->assertSame((string) ($order->total * 100), $query['vnp_Amount']);
        $this->assertTrue(app(VNPayService::class)->verifyCallback($query));
    }
}
