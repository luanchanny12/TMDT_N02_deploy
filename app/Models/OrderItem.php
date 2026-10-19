<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'product_name', 'size', 'color',
        'price', 'cost_price', 'quantity', 'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'cost_price' => 'integer',
            'quantity' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    /**
     * Lợi nhuận gộp của dòng đơn theo giá vốn đã chụp lúc đặt hàng.
     */
    public function grossProfit(): ?int
    {
        return $this->cost_price === null ? null : ($this->price - $this->cost_price) * $this->quantity;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function optionLabel(): ?string
    {
        $label = collect([$this->size, $this->color])->filter()->implode(' - ');

        return $label !== '' ? $label : null;
    }
}
