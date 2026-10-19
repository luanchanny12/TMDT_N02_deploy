<?php

namespace App\Console\Commands;

use App\Models\AnalyticsEvent;
use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * Thực thi thời hạn lưu trữ công bố tại trang "Bảng đăng ký dữ liệu cá nhân":
 * xóa thống kê truy cập và nhật ký bảo mật quá hạn.
 */
class PrunePersonalData extends Command
{
    public const ANALYTICS_MONTHS = 24;

    public const AUDIT_MONTHS = 12;

    protected $signature = 'data:prune';

    protected $description = 'Xóa dữ liệu thống kê truy cập và nhật ký bảo mật đã quá thời hạn lưu trữ';

    public function handle(): int
    {
        $events = AnalyticsEvent::where('created_at', '<', now()->subMonths(self::ANALYTICS_MONTHS))->delete();
        $logs = AuditLog::where('created_at', '<', now()->subMonths(self::AUDIT_MONTHS))->delete();

        $this->info("Đã xóa {$events} sự kiện thống kê và {$logs} dòng nhật ký quá hạn.");

        return self::SUCCESS;
    }
}
