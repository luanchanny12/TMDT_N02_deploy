<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    use HasFactory;

    /** Mã chào mừng quảng bá trên thanh ưu đãi đầu trang (tạo bởi PromoCouponSeeder). */
    public const WELCOME_CODE = 'WELCOME10';

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'start_at',
        'end_at',
        'usage_limit',
        'used_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'min_order_amount' => 'integer',
            'max_discount' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope: coupon còn dùng được (active + trong thời hạn + chưa hết lượt).
     */
    public function scopeValid($query)
    {
        return $query
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('start_at')->orWhere('start_at', '<=', Carbon::now()))
            ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', Carbon::now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')
                ->orWhereColumn('used_count', '<', 'usage_limit'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Tính số tiền được giảm từ coupon này cho 1 đơn hàng.
     */
    public function calculateDiscount(int $orderAmount): int
    {
        if ($orderAmount < $this->min_order_amount) {
            return 0;
        }

        if ($this->type === 'percent') {
            $discount = (int) round($orderAmount * $this->value / 100);
            if ($this->max_discount !== null) {
                $discount = min($discount, $this->max_discount);
            }

            return $discount;
        }

        // fixed
        return min($this->value, $orderAmount);
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }
}
