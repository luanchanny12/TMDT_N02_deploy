<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Product;
use App\Services\Analytics\AnalyticsReportService;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'hasDemoData' => $reports->hasDemoData(),
        ]));
    }

    /**
     * Xóa dữ liệu demo (lượt truy cập, sự kiện, chiến dịch mẫu) — số liệu thật và đơn hàng giữ nguyên.
     */
    public function clearDemo()
    {
        $events = AnalyticsEvent::where('is_demo', true)->count();
        $campaignIds = Campaign::where('is_demo', true)->pluck('id');

        DB::transaction(function () use ($campaignIds) {
            AnalyticsEvent::where('is_demo', true)->delete();
            Order::whereIn('campaign_id', $campaignIds)->update(['campaign_id' => null]);
            Campaign::whereIn('id', $campaignIds)->delete();

            // Lượt xem hiển thị trên sản phẩm (thẻ, trang chi tiết, "Xem nhiều", Dashboard) cũng chứa số mẫu
            // → tính lại bằng đúng số lượt xem thật đã ghi nhận
            Product::query()->update([
                'views_count' => DB::raw("(SELECT COUNT(*) FROM analytics_events WHERE analytics_events.product_id = products.id AND analytics_events.type = 'product_view')"),
            ]);
        });

        AuditService::log('analytics_demo_cleared', 'AnalyticsEvent', null, ['events' => $events, 'campaigns' => $campaignIds->count()]);

        return redirect()->route('admin.analytics.index')
            ->with('success', "Đã xóa dữ liệu mẫu ({$events} sự kiện, {$campaignIds->count()} chiến dịch). Trang chỉ còn số liệu thật.");
    }
}
