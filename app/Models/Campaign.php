<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    public const PLATFORMS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'google' => 'Google Ads',
        'zalo' => 'Zalo',
        'other' => 'Khác',
    ];

    protected $fillable = [
        'name', 'platform', 'utm_source', 'utm_medium', 'utm_campaign',
        'landing_path', 'spend', 'starts_on', 'ends_on', 'status', 'note', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'spend' => 'integer',
            'is_demo' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function events()
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    public function platformLabel(): string
    {
        return self::PLATFORMS[$this->platform] ?? $this->platform;
    }

    /**
     * Link có gắn UTM để dán vào bài quảng cáo.
     */
    public function trackingUrl(): string
    {
        $path = '/'.ltrim($this->landing_path ?: '/', '/');
        $query = http_build_query([
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
        ]);

        return rtrim(config('app.url'), '/').$path.(str_contains($path, '?') ? '&' : '?').$query;
    }
}
