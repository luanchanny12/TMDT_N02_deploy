<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmedMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\VNPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class OrderInvoiceEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_email_renders_expected_content_and_structure(): void
    {
        $user = User::factory()->create([
            'name' => 'Nguyễn Văn A',
            'email' => 'khachhang@example.com',
        ]);

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Đầm Dạ Hội Luxury',
            'price' => 1250000,
            'sale_price' => null,
            'stock' => 10,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Đỏ Ruby',
            'stock' => 10,
            'price' => 1250000,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-20261019-TEST',
            'subtotal' => 1250000,
            'discount' => 100000,
            'shipping_fee' => 30000,
            'tax_rate' => 8.0,
            'tax_amount' => 92000,
            'total' => 1272000,
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'shipping_name' => 'Nguyễn Văn A',
            'shipping_phone' => '0912345678',
            'shipping_address' => '123 Đường Cầu Giấy',
            'shipping_province' => 'Hà Nội',
            'note' => 'Giao giờ hành chính',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'size' => $variant->size,
            'color' => $variant->color,
            'price' => 1250000,
            'quantity' => 1,
            'subtotal' => 1250000,
        ]);

        $order->load(['items.product', 'items.variant', 'user']);
        $mailable = new OrderConfirmedMail($order);

        // Kiểm tra Envelope Subject khi chưa thanh toán (COD)
        $this->assertEquals(
            '[SocialShop] Hóa đơn & Xác nhận đơn hàng #ORD-20261019-TEST',
            $mailable->envelope()->subject
        );

        $html = $mailable->render();

        // Kiểm tra nội dung hóa đơn
        $this->assertStringContainsString('SocialShop', $html);
        $this->assertStringContainsString('HÓA ĐƠN BÁN HÀNG & BIÊN LAI ĐIỆN TỬ', $html);
        $this->assertStringContainsString('ORD-20261019-TEST', $html);
        $this->assertStringContainsString('Nguyễn Văn A', $html);
        $this->assertStringContainsString('0912345678', $html);
        $this->assertStringContainsString('123 Đường Cầu Giấy, Hà Nội', $html);
        $this->assertStringContainsString('Giao giờ hành chính', $html);
        $this->assertStringContainsString('THANH TOÁN KHI NHẬN HÀNG', $html);

        // Kiểm tra chi tiết sản phẩm và biến thể
        $this->assertStringContainsString('Đầm Dạ Hội Luxury', $html);
        $this->assertStringContainsString('Size M / Màu Đỏ Ruby', $html);
        $this->assertStringContainsString('1.250.000₫', $html);

        // Kiểm tra bảng tài chính: tạm tính, giảm giá, ship, VAT, tổng tiền
        $this->assertStringContainsString('1.250.000₫', $html);
        $this->assertStringContainsString('-100.000₫', $html);
        $this->assertStringContainsString('30.000₫', $html);
        $this->assertStringContainsString('Thuế VAT (8%):', $html);
        $this->assertStringContainsString('92.000₫', $html);
        $this->assertStringContainsString('1.272.000₫', $html);

        // Kiểm tra chính sách đổi trả 7 ngày và nút tra cứu
        $this->assertStringContainsString('7 ngày', $html);
        $this->assertStringContainsString('Tra Cứu Chi Tiết Đơn Hàng', $html);
        $this->assertStringContainsString(route('orders.show', $order->id), $html);
    }

    public function test_invoice_email_subject_and_badge_when_paid(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_code' => 'ORD-PAID-999',
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
        ]);

        $mailable = new OrderConfirmedMail($order);

        $this->assertEquals(
            '[SocialShop] Hóa đơn điện tử & Xác nhận thanh toán #ORD-PAID-999',
            $mailable->envelope()->subject
        );

        $html = $mailable->render();
        $this->assertStringContainsString('✓ ĐÃ THANH TOÁN (VNPAY)', $html);
    }

    public function test_vnpay_ipn_queues_invoice_email_on_success(): void
    {
        Mail::fake();

        $vnpayMock = Mockery::mock(VNPayService::class);
        $vnpayMock->shouldReceive('verifyCallback')->andReturn(true);
        $paymentService = new PaymentService($vnpayMock, app(OrderService::class));

        $user = User::factory()->create(['email' => 'buyer@example.com']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_code' => 'ORD-VNP-001',
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
            'total' => 200000,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'transaction_code' => 'TXN_VNP_001',
            'amount' => 200000,
            'method' => 'vnpay',
            'status' => 'pending',
        ]);

        $response = $paymentService->processVNPayIpn([
            'vnp_TxnRef' => 'TXN_VNP_001',
            'vnp_Amount' => 200000 * 100,
            'vnp_ResponseCode' => '00',
        ]);

        $this->assertEquals('00', $response['RspCode']);
        $this->assertEquals('paid', $order->fresh()->payment_status);

        Mail::assertQueued(OrderConfirmedMail::class, function ($mail) use ($order) {
            return $mail->order->id === $order->id;
        });
    }

    public function test_vnpay_order_placement_does_not_queue_email_prematurely(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'active', 'stock' => 10, 'price' => 150000, 'sale_price' => null]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 150000,
        ]);

        $orderService = app(OrderService::class);
        $order = $orderService->placeOrder($user, [
            'payment_method' => 'vnpay',
            'shipping_name' => 'Khách VNPay',
            'shipping_phone' => '0988776655',
            'shipping_address' => '456 Lê Lợi',
        ]);

        $this->assertNotNull($order);
        // Chưa thanh toán VNPay thì chưa gửi email xác nhận thanh toán
        Mail::assertNotQueued(OrderConfirmedMail::class);
    }
}
