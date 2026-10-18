<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\BaseService;

class VNPayService extends BaseService
{
    /**
     * Build the VNPay payment URL.
     */
    public function buildPaymentUrl(Order $order, string $transactionCode, string $ipAddress): string
    {
        $vnp_TmnCode = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        $vnp_Url = config('vnpay.url');
        $vnp_Returnurl = config('vnpay.return_url');

        $vnp_TxnRef = $transactionCode;
        $vnp_OrderInfo = "Thanh toan don hang {$order->order_code}";
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = (int) $order->total * 100;
        $vnp_Locale = 'vn';
        // Không gửi vnp_BankCode — để VNPay hiện màn hình chọn ngân hàng
        $vnp_IpAddr = $ipAddress;

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $vnp_TmnCode,
            'vnp_Amount' => $vnp_Amount,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => now('Asia/Ho_Chi_Minh')->format('YmdHis'),
            // Link thanh toán hết hạn cùng lúc đơn bị tự hủy → không thể trả tiền cho đơn đã hủy
            'vnp_ExpireDate' => now('Asia/Ho_Chi_Minh')->addMinutes((int) config('vnpay.expire_minutes', 30))->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $vnp_IpAddr,
            'vnp_Locale' => $vnp_Locale,
            'vnp_OrderInfo' => $vnp_OrderInfo,
            'vnp_OrderType' => $vnp_OrderType,
            'vnp_ReturnUrl' => $vnp_Returnurl,
            'vnp_TxnRef' => $vnp_TxnRef,
        ];

        ksort($inputData);
        $query = '';
        $i = 0;
        $hashdata = '';
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashdata .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key).'='.urlencode($value).'&';
        }

        $vnp_Url = $vnp_Url.'?'.$query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash='.$vnpSecureHash;
        }

        return $vnp_Url;
    }

    /**
     * Verify VNPay callback or IPN signature.
     */
    public function verifyCallback(array $inputData): bool
    {
        $vnp_HashSecret = (string) config('vnpay.hash_secret');
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

        // Thiếu secret thì ai cũng tự ký được callback "đã thanh toán" → luôn từ chối
        if ($vnp_HashSecret === '' || ! is_string($vnp_SecureHash) || $vnp_SecureHash === '') {
            return false;
        }

        // Callback phải thuộc đúng merchant của shop
        if (($inputData['vnp_TmnCode'] ?? null) !== config('vnpay.tmn_code')) {
            return false;
        }

        $vnpayData = [];
        foreach ($inputData as $key => $value) {
            if (substr($key, 0, 4) == 'vnp_') {
                $vnpayData[$key] = $value;
            }
        }

        unset($vnpayData['vnp_SecureHash']);
        unset($vnpayData['vnp_SecureHashType']);

        ksort($vnpayData);
        $i = 0;
        $hashData = '';
        foreach ($vnpayData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData.'&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashData = $hashData.urlencode($key).'='.urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        return hash_equals($secureHash, strtolower($vnp_SecureHash));
    }

    /**
     * Giao dịch chỉ thành công khi cả mã phản hồi và trạng thái giao dịch đều là 00 (theo tài liệu VNPay).
     */
    public static function isSuccessful(array $inputData): bool
    {
        return ($inputData['vnp_ResponseCode'] ?? '') === '00'
            && ($inputData['vnp_TransactionStatus'] ?? '00') === '00';
    }
}
