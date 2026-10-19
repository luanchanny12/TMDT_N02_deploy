<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsReportService;
use App\Services\Analytics\DemoAnalyticsService;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    private const RANGES = [7 => '7 ngày', 30 => '30 ngày', 90 => '90 ngày'];

    public function index(Request $request, AnalyticsReportService $reports)
    {
        $days = (int) $request->query('days', 30);
        if (! array_key_exists($days, self::RANGES)) {
            $days = 30;
        }

        $to = CarbonImmutable::now()->endOfDay();
        $from = $to->subDays($days - 1)->startOfDay();

        return view('admin.analytics.index', array_merge($reports->build($from, $to), [
            'days' => $days,
            'ranges' => self::RANGES,
            'from' => $from,
            'to' => $to,
            'demoEnabled' => app(DemoAnalyticsService::class)->isEnabled(),
        ]));
    }

    /**
     * Công tắc dữ liệu mẫu: bật = tạo lại dữ liệu demo, tắt = xóa (số liệu thật và đơn hàng giữ nguyên).
     */
    public function toggleDemo(Request $request, DemoAnalyticsService $demo)
    {
        $enable = $request->boolean('enabled');
        $result = $enable ? $demo->enable() : $demo->disable();

        AuditService::log($enable ? 'analytics_demo_enabled' : 'analytics_demo_cleared', 'AnalyticsEvent', null, $result);

        return redirect()->route('admin.analytics.index', ['days' => $request->integer('days', 30)])
            ->with('success', $enable
                ? "Đã bật dữ liệu mẫu ({$result['events']} sự kiện, {$result['campaigns']} chiến dịch demo)."
                : "Đã tắt dữ liệu mẫu (xóa {$result['events']} sự kiện, {$result['campaigns']} chiến dịch). Trang chỉ còn số liệu thật.");
    }
}
