<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    protected $fillable = ['key', 'value'];

    /**
     * Đọc một cấu hình (có cache), trả về $default nếu chưa đặt.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * Ghi nhiều cấu hình cùng lúc và làm mới cache.
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
