<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetentionEmail extends Model
{
    public const TYPES = [
        'welcome' => 'Thư chào mừng (+1 giờ)',
        'cart_reminder' => 'Nhắc giỏ hàng (+24 giờ)',
        'offer' => 'Ưu đãi có thời hạn (+72 giờ)',
    ];

    public $timestamps = false;

    protected $fillable = ['user_id', 'type', 'coupon_id', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
