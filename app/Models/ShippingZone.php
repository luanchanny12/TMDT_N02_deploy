<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ShippingZone extends Model
{
    private const CACHE_KEY = 'shipping_zones.active';

    protected $fillable = ['name', 'provinces', 'fee', 'free_shipping_threshold', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'provinces' => 'array',
            'fee' => 'integer',
            'free_shipping_threshold' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Tất cả tỉnh/thành hợp lệ (34 đơn vị sau sắp xếp 2025).
     *
     * @return array<int, string>
     */
    public static function allProvinces(): array
    {
        return array_merge(...array_values(config('vietnam.regions')));
    }

    /**
     * Bảng tra tỉnh → khu vực đang hoạt động (có cache).
     *
     * @return array<string, array{zone: string, fee: int, threshold: int|null}>
     */
    public static function provinceMap(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $map = [];
            foreach (static::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get() as $zone) {
                foreach ($zone->provinces ?? [] as $province) {
                    $map[$province] ??= ['zone' => $zone->name, 'fee' => $zone->fee, 'threshold' => $zone->free_shipping_threshold];
                }
            }

            return $map;
        });
    }
}
