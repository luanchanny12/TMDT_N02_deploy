<?php

namespace App\Services\Payment;

use App\Mail\OrderConfirmedMail;
use App\Models\Order;
use App\Models\Payment;
use App\Services\AdminNotifier;
use App\Services\AuditService;
use App\Services\BaseService;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PaymentService extends BaseService
{
    public function __construct(
        private VNPayService $vnpayService,
        private OrderService $orderService
    ) {}

    public function createPayment(Order $order, string $ipAddress): Payment
    {
        if ($order->payment_method === 'cod') {
            return $this->createCodPayment($order);
        }

        if ($order->payment_method === 'vnpay') {
            return $this->createVNPayPayment($order, $ipAddress);
        }

        throw new \InvalidArgumentException('Unsupported payment method');
    }

    private function createCodPayment(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => 'cod',
            'amount' => $order->total,
            'status' => 'pending',
        ]);
    }

    private function createVNPayPayment(Order $order, string $ipAddress): Payment
    {
        $transactionCode = 'VNP-'.time().'-'.Str::random(4);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'vnpay',
            'transaction_code' => $transactionCode,
            'amount' => $order->total,
            'status' => 'pending',
        ]);

        $url = $this->vnpayService->buildPaymentUrl($order, $transactionCode, $ipAddress);

        // Temporarily store the redirect URL in a non-DB property for the controller to use
        $payment->redirect_url = $url;

        return $payment;
    }

    public function processVNPayIpn(array $inputData): array
    {
        // 1. Verify signature
        if (! $this->vnpayService->verifyCallback($inputData)) {
            return ['RspCode' => '97', 'Message' => 'Invalid signature'];
        }

        $transactionCode = $inputData['vnp_TxnRef'] ?? '';
        $vnpAmount = $inputData['vnp_Amount'] ?? 0;
        $succeeded = VNPayService::isSuccessful($inputData);

        $failedOrder = null;

        $result = DB::transaction(function () use ($transactionCode, $vnpAmount, $succeeded, $inputData, &$failedOrder) {
            // 2. Find Order via Payment
            // Sử dụng lockForUpdate để chống race condition nếu IPN gọi 2 lần cùng lúc
            $payment = Payment::where('transaction_code', $transactionCode)->lockForUpdate()->first();

            if (! $payment) {
                return ['RspCode' => '01', 'Message' => 'Order not found'];
            }

            $order = $payment->order;

            // 3. Kiểm tra số tiền (VNPay nhân 100)
            if (! is_numeric($vnpAmount) || (int) $payment->amount * 100 !== (int) $vnpAmount) {
                return ['RspCode' => '04', 'Message' => 'Invalid amount'];
            }

            // 4. Kiểm tra giao dịch đã xử lý chưa (Idempotency)
            if ($payment->status !== 'pending') {
                // Khách trả tiền cho đơn đã hủy/hết hạn → không mở lại đơn, ghi vết để admin hoàn tiền
                if ($succeeded && $payment->status === 'failed') {
                    AuditService::log('payment_refund_required', 'Payment', $payment->id, [
                        'transaction_code' => $transactionCode,
                        'amount' => $payment->amount,
                        'order_id' => $payment->order_id,
                        'vnp_TransactionNo' => $inputData['vnp_TransactionNo'] ?? null,
                    ]);
                    app(AdminNotifier::class)->refundRequired($payment->order, (int) $payment->amount);
                }

                return ['RspCode' => '02', 'Message' => 'Order already confirmed'];
            }

            // 5. Kiểm tra kết quả giao dịch (vnp_ResponseCode + vnp_TransactionStatus)
            if ($succeeded) {
                // 6. Update Payment
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'response_data' => $inputData,
                ]);

                // 7. Update Order
                $order->update([
                    'payment_status' => 'paid',
                ]);

                AuditService::log('payment_paid', 'Payment', $payment->id, [
                    'transaction_code' => $transactionCode,
                    'amount' => $payment->amount,
                    'order_id' => $order->id,
                ]);
                app(AdminNotifier::class)->orderPaid($order);

                // 8. Gửi hóa đơn điện tử xác nhận thanh toán thành công
                try {
                    $order->loadMissing(['items.product', 'items.variant', 'user']);
                    if ($order->user && $order->user->email) {
                        Mail::to($order->user->email)->queue(new OrderConfirmedMail($order));
                    }
                } catch (\Throwable $e) {
                    \Log::error('Không thể gửi email xác nhận thanh toán VNPay: '.$e->getMessage());
                }

                return ['RspCode' => '00', 'Message' => 'Confirm Success'];
            } else {
                // Giao dịch lỗi / khách hủy thanh toán
                $payment->update([
                    'status' => 'failed',
                    'response_data' => $inputData,
                ]);

                $order->update(['payment_status' => 'failed']);
                $failedOrder = $order;

                return ['RspCode' => '00', 'Message' => 'Confirm Success']; // Vẫn trả 00 vì merchant đã ghi nhận trạng thái lỗi
            }
        });

        // Thanh toán thất bại → hủy đơn để hoàn tồn kho + lượt coupon (không giữ kho vĩnh viễn)
        if ($failedOrder && $failedOrder->status === 'pending') {
            $this->orderService->cancelOrder($failedOrder, 'payment_failed');
        }

        return $result;
    }
}
