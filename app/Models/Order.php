<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'campaign_id', 'traffic_source', 'order_code', 'subtotal', 'discount', 'shipping_fee', 'tax_rate', 'tax_amount', 'total',
        'status', 'payment_method', 'payment_status',
        'shipping_name', 'shipping_phone', 'shipping_address', 'note',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount' => 'integer',
            'shipping_fee' => 'integer',
            'tax_rate' => 'float',
            'tax_amount' => 'integer',
            'total' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
