<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\Payment\VNPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VNPayTest extends TestCase
{
    use RefreshDatabase;

    private VNPayService $vnpay;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vnpay = app(VNPayService::class);
        $this->paymentService = app(PaymentService::class);
    }

    private function sign(array $data): array
    {
        // Callback thật của VNPay luôn kèm mã merchant
        $data += ['vnp_TmnCode' => config('vnpay.tmn_code')];
        ksort($data);
        $hashData = '';
        $i = 0;
        foreach ($data as $key => $value) {
            if ($i === 1) {
                $hashData .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashData .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
        }

        $data['vnp_SecureHash'] = hash_hmac('sha512', $hashData, config('vnpay.hash_secret'));

        return $data;
    }

    private function makePaidFlowPayment(int $amount): array
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => $amount,
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'vnpay',
            'transaction_code' => 'VNP-TEST-'.uniqid(),
            'amount' => $amount,
            'status' => 'pending',
        ]);

        return [$payment, $order];
    }

    public function test_valid_checksum_is_accepted(): void
    {
        $data = $this->sign([
            'vnp_TxnRef' => 'VNP-123',
            'vnp_Amount' => '100000000',
            'vnp_ResponseCode' => '00',
            'vnp_OrderInfo' => 'Thanh toan',
        ]);

        $this->assertTrue($this->vnpay->verifyCallback($data));
    }

    public function test_invalid_checksum_is_rejected(): void
    {
        $data = $this->sign([
            'vnp_TxnRef' => 'VNP-123',
            'vnp_Amount' => '100000000',
            'vnp_ResponseCode' => '00',
        ]);
        $data['vnp_SecureHash'] = str_repeat('a', 128);

        $this->assertFalse($this->vnpay->verifyCallback($data));
    }

    public function test_ipn_amount_mismatch_returns_04(): void
    {
        [$payment] = $this->makePaidFlowPayment(100000);

        $data = $this->sign([
            'vnp_TxnRef' => $payment->transaction_code,
            'vnp_Amount' => '99999900',
            'vnp_ResponseCode' => '00',
        ]);

        $result = $this->paymentService->processVNPayIpn($data);

        $this->assertSame('04', $result['RspCode']);
        $payment->refresh();
        $this->assertSame('pending', $payment->status);
    }

    public function test_ipn_idempotent_second_call_returns_02(): void
    {
        [$payment, $order] = $this->makePaidFlowPayment(200000);

        $data = $this->sign([
            'vnp_TxnRef' => $payment->transaction_code,
            'vnp_Amount' => '20000000',
            'vnp_ResponseCode' => '00',
        ]);

        $first = $this->paymentService->processVNPayIpn($data);
        $this->assertSame('00', $first['RspCode']);

        $payment->refresh();
        $order->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('paid', $order->payment_status);

        $second = $this->paymentService->processVNPayIpn($data);
        $this->assertSame('02', $second['RspCode']);
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_ipn_invalid_signature_returns_97(): void
    {
        [$payment] = $this->makePaidFlowPayment(150000);

        $data = [
            'vnp_TxnRef' => $payment->transaction_code,
            'vnp_Amount' => '15000000',
            'vnp_ResponseCode' => '00',
            'vnp_SecureHash' => str_repeat('b', 128),
        ];

        $result = $this->paymentService->processVNPayIpn($data);

        $this->assertSame('97', $result['RspCode']);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_ipn_unknown_transaction_returns_01(): void
    {
        $data = $this->sign([
            'vnp_TxnRef' => 'VNP-UNKNOWN-999',
            'vnp_Amount' => '10000000',
            'vnp_ResponseCode' => '00',
        ]);

        $result = $this->paymentService->processVNPayIpn($data);

        $this->assertSame('01', $result['RspCode']);
    }

    public function test_return_url_with_valid_success_signature_redirects_to_order_success(): void
    {
        [$payment, $order] = $this->makePaidFlowPayment(300000);

        $data = $this->sign([
            'vnp_TxnRef' => $payment->transaction_code,
            'vnp_Amount' => '30000000',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ]);

        $response = $this->get(route('payment.vnpay.return', $data));

        $response->assertRedirect(route('orders.success', $order->id));
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_return_url_with_invalid_signature_shows_error(): void
    {
        $response = $this->get(route('payment.vnpay.return', [
            'vnp_TxnRef' => 'VNP-X',
            'vnp_Amount' => '10000000',
            'vnp_ResponseCode' => '00',
            'vnp_SecureHash' => str_repeat('c', 128),
        ]));

        $response->assertOk()->assertSee('Chữ ký không hợp lệ');
    }

    public function test_build_payment_url_contains_signed_params(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 100000,
            'payment_method' => 'vnpay',
        ]);

        $url = $this->vnpay->buildPaymentUrl($order, 'VNP-URL-TEST', '127.0.0.1');

        $this->assertStringContainsString('vnp_Amount=10000000', $url);
        $this->assertStringContainsString('vnp_SecureHash=', $url);
        $this->assertStringContainsString('vnp_TxnRef=VNP-URL-TEST', $url);
        $this->assertStringContainsString('vnp_TmnCode=', $url);

        // Không gửi vnp_BankCode → VNPay hiện màn hình chọn ngân hàng
        $this->assertStringNotContainsString('vnp_BankCode=', $url);
    }

    public function test_return_url_with_failed_response_code_shows_error(): void
    {
        [$payment, $order] = $this->makePaidFlowPayment(300000);

        $data = $this->sign([
            'vnp_TxnRef' => $payment->transaction_code,
            'vnp_Amount' => '30000000',
            'vnp_ResponseCode' => '24', // Hủy giao dịch
            'vnp_TransactionStatus' => '24',
        ]);

        $response = $this->get(route('payment.vnpay.return', $data));

        $response->assertOk()->assertSee('Giao dịch không thành công hoặc đã bị hủy');
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_ipn_endpoint_returns_json_result(): void
    {
        [$payment, $order] = $this->makePaidFlowPayment(200000);

        $data = $this->sign([
            'vnp_TxnRef' => $payment->transaction_code,
            'vnp_Amount' => '20000000',
            'vnp_ResponseCode' => '00',
        ]);

        // Using the controller endpoint directly
        $response = $this->getJson(route('payment.vnpay.ipn', $data));

        $response->assertOk()
            ->assertJson([
                'RspCode' => '00',
                'Message' => 'Confirm Success',
            ]);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}
