<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Trả về URL ảnh đúng bất kể image_path là URL đầy đủ hay đường dẫn tương đối.
     * Tự động thêm tham số ?v={filemtime} để phá vỡ browser cache khi ảnh được cập nhật.
     */
    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        if (str_starts_with($this->image_path, 'images/')) {
            $fullPath = storage_path('app/public/'.$this->image_path);
            $version = file_exists($fullPath) ? filemtime($fullPath) : 1;

            return asset('storage/'.$this->image_path).'?v='.$version;
        }

        $fullPath = public_path($this->image_path);
        $version = file_exists($fullPath) ? filemtime($fullPath) : 1;

        return asset($this->image_path).'?v='.$version;
    }
}
