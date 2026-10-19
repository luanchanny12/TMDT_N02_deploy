<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payment\PaymentService;
use App\Services\Payment\VNPayService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private VNPayService $vnpayService
    ) {}

    public function vnpayReturn(Request $request)
    {
        $inputData = $request->all();

        if ($this->vnpayService->verifyCallback($inputData)) {
            if (VNPayService::isSuccessful($inputData)) {
                // Cập nhật payment/order ngay trên return (idempotent — IPN vẫn xử lý nếu portal gọi)
                $this->paymentService->processVNPayIpn($inputData);

                $payment = Payment::where('transaction_code', $inputData['vnp_TxnRef'] ?? '')->first();

                if ($payment && $payment->order_id) {
                    return redirect()->route('orders.success', $payment->order_id)
                        ->with('success', 'Thanh toán VNPay thành công!');
                }

                return view('payment.vnpay-return', [
                    'status' => 'success',
                    'message' => 'Giao dịch thành công',
                    'transactionCode' => $inputData['vnp_TxnRef'] ?? '',
                ]);
            }

            $this->paymentService->processVNPayIpn($inputData);

            return view('payment.vnpay-return', [
                'status' => 'error',
                'message' => 'Giao dịch không thành công hoặc đã bị hủy. Đơn hàng đã được hủy và sản phẩm đã được trả lại giỏ hàng để bạn đặt lại.',
            ]);
        }

        return view('payment.vnpay-return', [
            'status' => 'error',
            'message' => 'Chữ ký không hợp lệ (Invalid signature)',
        ]);
    }

    public function vnpayIpn(Request $request)
    {
        $inputData = $request->all();
        $result = $this->paymentService->processVNPayIpn($inputData);

        return response()->json($result);
    }
}
