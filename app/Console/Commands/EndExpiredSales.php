<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Console\Command;

/**
 * Kết thúc khuyến mãi đã hết hạn: xóa giá khuyến mãi để mọi nơi hiển thị lại giá gốc.
 * (Giá khi đặt hàng đã tự kiểm tra hạn qua Product::effectivePrice(), lệnh này dọn dữ liệu cho giao diện.)
 */
class EndExpiredSales extends Command
{
    protected $signature = 'products:end-expired-sales';

    protected $description = 'Đưa sản phẩm hết hạn khuyến mãi về giá gốc';

    public function handle(): int
    {
        $products = Product::whereNotNull('sale_ends_at')->where('sale_ends_at', '<=', now())->get(['id', 'name', 'sale_price', 'sale_ends_at']);

        foreach ($products as $product) {
            $product->update(['sale_price' => null, 'sale_ends_at' => null]);
            AuditService::log('product_sale_ended', 'Product', $product->id, ['name' => $product->name]);
        }

        $this->info("Đã kết thúc {$products->count()} khuyến mãi hết hạn.");

        return self::SUCCESS;
    }
}
