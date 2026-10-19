<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Tổng tiền tính lại lúc đặt hàng khác với báo giá khách đã xem ở bước xác nhận
 * (admin đổi giá/thuế/phí ship, khuyến mãi hết hạn...). Đơn không được tạo; khách xác nhận lại giá mới.
 */
class PriceChangedException extends RuntimeException
{
    public function __construct(public readonly int $quotedTotal, public readonly int $currentTotal)
    {
        parent::__construct('Giá đơn hàng đã thay đổi.');
    }
}
