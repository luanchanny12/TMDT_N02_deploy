<?php

namespace App\Services\Order;

use App\Models\Setting;
use App\Models\ShippingZone;
use App\Services\BaseService;

/**
 * Tính tiền đơn hàng từ một nguồn duy nhất (checkout, trang xem lại, tạo đơn):
 * tổng = tạm tính − giảm giá + phí vận chuyển + VAT.
 *  - Phí ship theo khu vực của tỉnh/thành nhận hàng (Admin → Cài đặt → Khu vực giao hàng);
 *    tỉnh không thuộc khu vực nào dùng phí ship mặc định.
 *  - VAT tính trên giá trị hàng sau giảm giá, cộng thêm phí ship nếu bật "Tính VAT trên phí vận chuyển".
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

    public function taxOnShipping(): bool
    {
        return (bool) Setting::get('tax_on_shipping', false);
    }

    public function shippingFee(): int
    {
        return (int) Setting::get('shipping_fee', self::DEFAULT_SHIPPING_FEE);
    }

    public function freeShippingThreshold(): int
    {
        return (int) Setting::get('free_shipping_threshold', self::DEFAULT_FREE_SHIPPING_THRESHOLD);
    }

    /**
     * @return array{zone: string|null, fee: int, threshold: int}
     */
    public function shippingRule(?string $province = null): array
    {
        $zone = $province ? (ShippingZone::provinceMap()[$province] ?? null) : null;

        return [
            'zone' => $zone['zone'] ?? null,
            'fee' => $zone['fee'] ?? $this->shippingFee(),
            'threshold' => $zone['threshold'] ?? $this->freeShippingThreshold(),
        ];
    }

    /**
     * Ngưỡng để đơn được miễn phí ship ở MỌI tỉnh/thành — dùng cho banner "Freeship toàn quốc từ ...",
     * lấy mức cao nhất giữa các khu vực để thông điệp quảng cáo luôn đúng.
     */
    public function nationwideFreeShippingThreshold(): int
    {
        $map = ShippingZone::provinceMap();
        $default = $this->freeShippingThreshold();
        $thresholds = array_map(fn ($zone) => $zone['threshold'] ?? $default, $map);

        if (count($map) < count(ShippingZone::allProvinces())) {
            $thresholds[] = $default; // còn tỉnh dùng ngưỡng mặc định
        }

        return (int) max($thresholds ?: [$default]);
    }

    /**
     * Mức phí ship thấp nhất (khu vực rẻ nhất) — hiển thị "phí ship chỉ từ ...".
     */
    public function lowestShippingFee(): int
    {
        $fees = array_column(ShippingZone::provinceMap(), 'fee');
        if (count($fees) < count(ShippingZone::allProvinces())) {
            $fees[] = $this->shippingFee();
        }

        return (int) min($fees ?: [$this->shippingFee()]);
    }

    public function shippingFor(int $subtotal, ?string $province = null): int
    {
        $rule = $this->shippingRule($province);

        return $subtotal >= $rule['threshold'] ? 0 : $rule['fee'];
    }

    public function taxFor(int $taxable, ?float $rate = null): int
    {
        $rate ??= $this->taxRate();

        return (int) round(max(0, $taxable) * $rate / 100);
    }

    /**
     * @return array{subtotal:int, discount:int, shipping_fee:int, shipping_zone:string|null, tax_rate:float, tax_amount:int, total:int}
     */
    public function breakdown(int $subtotal, int $discount = 0, ?string $province = null): array
    {
        $discount = min(max(0, $discount), $subtotal);
        $shipping = $this->shippingFor($subtotal, $province);
        $rate = $this->taxRate();
        $taxable = $subtotal - $discount + ($this->taxOnShipping() ? $shipping : 0);
        $tax = $this->taxFor($taxable, $rate);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_fee' => $shipping,
            'shipping_zone' => $this->shippingRule($province)['zone'],
            'tax_rate' => $rate,
            'tax_amount' => $tax,
            'total' => $subtotal - $discount + $shipping + $tax,
        ];
    }

    /**
     * Cấu hình cho JavaScript tính lại tổng khi khách đổi tỉnh/thành hoặc áp mã giảm giá trên trang checkout.
     */
    public function clientConfig(): array
    {
        return [
            'shippingFee' => $this->shippingFee(),
            'freeShippingThreshold' => $this->freeShippingThreshold(),
            'zones' => ShippingZone::provinceMap(),
            'taxRate' => $this->taxRate(),
            'taxLabel' => $this->taxLabel(),
            'taxOnShipping' => $this->taxOnShipping(),
        ];
    }
}
