<?php

namespace App\Services\Order;

use App\Models\Setting;
use App\Services\BaseService;

/**
 * Tính tiền đơn hàng từ một nguồn duy nhất (checkout, trang xem lại, tạo đơn):
 * tổng = tạm tính − giảm giá + phí vận chuyển + VAT.
 * VAT tính trên giá trị hàng sau giảm giá; cấu hình trong Admin → Cài đặt.
 */
class PricingService extends BaseService
{
    public const DEFAULT_SHIPPING_FEE = 30000;

    public const DEFAULT_FREE_SHIPPING_THRESHOLD = 500000;

    public function taxEnabled(): bool
    {
        return (bool) Setting::get('tax_enabled', false);
    }

    /**
     * Thuế suất VAT (%) đang áp dụng; 0 khi tắt.
     */
    public function taxRate(): float
    {
        return $this->taxEnabled() ? (float) Setting::get('tax_rate', 0) : 0.0;
    }

    public function taxLabel(): string
    {
        return (string) Setting::get('tax_label', 'Thuế VAT');
    }

    public function shippingFee(): int
    {
        return (int) Setting::get('shipping_fee', self::DEFAULT_SHIPPING_FEE);
    }

    public function freeShippingThreshold(): int
    {
        return (int) Setting::get('free_shipping_threshold', self::DEFAULT_FREE_SHIPPING_THRESHOLD);
    }

    public function shippingFor(int $subtotal): int
    {
        return $subtotal >= $this->freeShippingThreshold() ? 0 : $this->shippingFee();
    }

    public function taxFor(int $taxable, ?float $rate = null): int
    {
        $rate ??= $this->taxRate();

        return (int) round(max(0, $taxable) * $rate / 100);
    }

    /**
     * @return array{subtotal:int, discount:int, shipping_fee:int, tax_rate:float, tax_amount:int, total:int}
     */
    public function breakdown(int $subtotal, int $discount = 0): array
    {
        $discount = min(max(0, $discount), $subtotal);
        $shipping = $this->shippingFor($subtotal);
        $rate = $this->taxRate();
        $tax = $this->taxFor($subtotal - $discount, $rate);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_fee' => $shipping,
            'tax_rate' => $rate,
            'tax_amount' => $tax,
            'total' => $subtotal - $discount + $shipping + $tax,
        ];
    }

    /**
     * Cấu hình cho JavaScript tính lại tổng khi khách áp mã giảm giá trên trang checkout.
     */
    public function clientConfig(): array
    {
        return [
            'shippingFee' => $this->shippingFee(),
            'freeShippingThreshold' => $this->freeShippingThreshold(),
            'taxRate' => $this->taxRate(),
            'taxLabel' => $this->taxLabel(),
        ];
    }
}
