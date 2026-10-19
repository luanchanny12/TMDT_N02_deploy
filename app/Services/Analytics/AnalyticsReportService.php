<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Campaign;
use App\Models\Order;
use App\Services\BaseService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Số liệu cho trang Admin → Phân tích dữ liệu.
 *  - Tỷ lệ chuyển đổi = số đơn (không tính đơn hủy, có ghi nhận trên website) / số lượt truy cập.
 *  - ROAS = doanh thu đơn đến từ chiến dịch / chi phí quảng cáo của chiến dịch.
 */
class AnalyticsReportService extends BaseService
{
    public function build(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $events = AnalyticsEvent::query()->whereBetween('created_at', [$from, $to]);
        $orders = $this->trackedOrders()->whereBetween('created_at', [$from, $to]);

        $visits = (clone $events)->where('type', 'visit')->count();
        $orderCount = (clone $orders)->count();
        $revenue = (int) (clone $orders)->sum('total');

        $funnel = [
            ['label' => 'Truy cập website', 'value' => (clone $events)->where('type', 'visit')->distinct()->count('session_hash')],
            ['label' => 'Xem sản phẩm', 'value' => (clone $events)->where('type', 'product_view')->distinct()->count('session_hash')],
            ['label' => 'Thêm vào giỏ', 'value' => (clone $events)->where('type', 'add_to_cart')->distinct()->count('session_hash')],
            ['label' => 'Đặt hàng', 'value' => $orderCount],
        ];

        $campaigns = $this->campaignStats();
        $adSpend = $campaigns->sum('spend');
        $adRevenue = $campaigns->sum('revenue');

        return [
            'kpis' => [
                'visits' => $visits,
                'product_views' => (clone $events)->where('type', 'product_view')->count(),
                'orders' => $orderCount,
                'revenue' => $revenue,
                'conversion_rate' => $this->ratio($orderCount, $visits) * 100,
                'aov' => $orderCount ? intdiv($revenue, $orderCount) : 0,
                'ad_spend' => $adSpend,
                'ad_revenue' => $adRevenue,
                'roas' => $this->ratio($adRevenue, $adSpend),
            ],
            'funnel' => $funnel,
            'daily' => $this->daily($from, $to),
            'sources' => $this->sources($from, $to),
            'topProducts' => $this->topProducts($from, $to),
            'campaigns' => $campaigns,
        ];
    }

    /**
     * Chỉ số từng chiến dịch trên toàn bộ thời gian chạy (chi phí nhập theo chiến dịch).
     */
    public function campaignStats()
    {
        $visits = AnalyticsEvent::query()
            ->where('type', 'visit')->whereNotNull('campaign_id')
            ->groupBy('campaign_id')
            ->selectRaw('campaign_id, COUNT(*) as c')
            ->pluck('c', 'campaign_id');

        $orders = $this->trackedOrders()
            ->whereNotNull('campaign_id')
            ->groupBy('campaign_id')
            ->get(['campaign_id', DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as revenue')])
            ->keyBy('campaign_id');

        return Campaign::query()->latest()->get()->map(function (Campaign $c) use ($visits, $orders) {
            $v = (int) ($visits[$c->id] ?? 0);
            $o = (int) ($orders[$c->id]->orders ?? 0);
            $r = (int) ($orders[$c->id]->revenue ?? 0);

            return [
                'model' => $c,
                'spend' => $c->spend,
                'visits' => $v,
                'orders' => $o,
                'revenue' => $r,
                'conversion_rate' => $this->ratio($o, $v) * 100,
                'cpa' => $o ? intdiv($c->spend, $o) : null,
                'roas' => $c->spend > 0 ? $r / $c->spend : null,
            ];
        });
    }

    private function daily(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $visits = AnalyticsEvent::query()->where('type', 'visit')->whereBetween('created_at', [$from, $to])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->pluck('c', 'd');

        $orders = $this->trackedOrders()->whereBetween('created_at', [$from, $to])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get([DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'), DB::raw('SUM(total) as r')])
            ->keyBy('d');

        $rows = [];
        foreach (CarbonPeriod::create($from->startOfDay(), $to->startOfDay()) as $day) {
            $key = $day->toDateString();
            $rows[] = [
                'label' => $day->format('d/m'),
                'visits' => (int) ($visits[$key] ?? 0),
                'orders' => (int) ($orders[$key]->c ?? 0),
                'revenue' => (int) ($orders[$key]->r ?? 0),
            ];
        }

        return $rows;
    }

    private function sources(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $visits = AnalyticsEvent::query()->where('type', 'visit')->whereBetween('created_at', [$from, $to])
            ->groupBy('source')
            ->selectRaw('source, COUNT(*) as c')
            ->pluck('c', 'source');

        $orders = $this->trackedOrders()->whereBetween('created_at', [$from, $to])
            ->groupBy('traffic_source')
            ->get(['traffic_source', DB::raw('COUNT(*) as c'), DB::raw('SUM(total) as r')])
            ->keyBy(fn ($row) => (string) $row->traffic_source);

        $keys = collect($visits->keys())->merge($orders->keys())->map(fn ($k) => (string) $k)->unique();

        return $keys->map(function ($key) use ($visits, $orders) {
            $v = (int) ($visits[$key] ?? 0);
            $o = (int) ($orders[$key]->c ?? 0);

            return [
                'source' => $key === '' ? 'Trực tiếp' : $key,
                'visits' => $v,
                'orders' => $o,
                'revenue' => (int) ($orders[$key]->r ?? 0),
                'conversion_rate' => $this->ratio($o, $v) * 100,
            ];
        })->sortByDesc('visits')->values()->all();
    }

    private function topProducts(CarbonImmutable $from, CarbonImmutable $to)
    {
        return AnalyticsEvent::query()
            ->whereIn('type', ['product_view', 'add_to_cart'])
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('views')
            ->limit(8)
            ->with('product:id,name,slug')
            ->get([
                'product_id',
                DB::raw("SUM(CASE WHEN type = 'product_view' THEN 1 ELSE 0 END) as views"),
                DB::raw("SUM(CASE WHEN type = 'add_to_cart' THEN 1 ELSE 0 END) as carts"),
            ]);
    }

    /**
     * Đơn hợp lệ (không hủy) có ghi nhận hành trình mua trên website (sự kiện purchase) —
     * để tỷ lệ chuyển đổi so sánh cùng nguồn dữ liệu với lượt truy cập.
     */
    private function trackedOrders()
    {
        return Order::query()
            ->where('status', '!=', 'cancelled')
            ->whereHas('analyticsEvents', fn ($q) => $q->where('type', 'purchase'));
    }

    private function ratio(int|float $a, int|float $b): float
    {
        return $b > 0 ? $a / $b : 0.0;
    }
}
