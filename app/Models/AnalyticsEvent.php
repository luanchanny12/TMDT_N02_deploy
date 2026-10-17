<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'type', 'session_hash', 'user_id', 'campaign_id', 'source',
        'product_id', 'order_id', 'value', 'path', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
