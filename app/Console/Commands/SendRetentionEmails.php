<?php

namespace App\Console\Commands;

use App\Services\Marketing\RetentionEmailService;
use Illuminate\Console\Command;

class SendRetentionEmails extends Command
{
    protected $signature = 'marketing:send-retention';

    protected $description = 'Gửi chuỗi 3 email giữ chân (chào mừng, nhắc giỏ hàng, ưu đãi) cho khách đã đồng ý nhận email';

    public function handle(RetentionEmailService $service): int
    {
        $sent = $service->run();

        $this->info("Đã gửi: {$sent['welcome']} chào mừng, {$sent['cart_reminder']} nhắc giỏ hàng, {$sent['offer']} ưu đãi.");

        return self::SUCCESS;
    }
}
