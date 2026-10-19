<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\BaseService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * TH13: phễu 5 bước tách theo kênh, chẩn đoán điểm rơi, kinh tế đơn vị (biên gộp, ROAS hòa vốn, LTV, CAC).
 * Sự kiện tương ứng GA4: visit = session_start, product_view = view_item, add_to_cart, begin_checkout, purchase.
 */
class FunnelReportService extends BaseService
{
    public const STEPS = [
        'visit' => 'Truy cập',
        'product_view' => 'Xem sản phẩm',
        'add_to_cart' => 'Thêm vào giỏ',
        'begin_checkout' => 'Bắt đầu thanh toán',
        'purchase' => 'Mua hàng',
    ];

    public const CHANNELS = [
        'paid' => 'Quảng cáo trả phí',
        'organic' => 'Tìm kiếm tự nhiên',
        'social' => 'Mạng xã hội',
        'referral' => 'Giới thiệu (website khác)',
        'direct' => 'Trực tiếp',
    ];

    private const SEARCH_SOURCES = ['google', 'bing', 'coccoc', 'yahoo', 'duckduckgo'];

    private const SOCIAL_SOURCES = ['facebook', 'instagram', 'tiktok', 'youtube', 'zalo', 'messenger', 'twitter', 'x', 'threads'];

    /** LTV/CAC tối thiểu được coi là kênh bền vững. */
    public const TARGET_LTV_CAC = 3;

    /**
     * Phễu (số phiên duy nhất ở mỗi bước) theo từng kênh + tổng.
     *
     * @return array<string, array{label: string, steps: array<string, int>}>
     */
    public function funnelByChannel(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $channelSql = $this->channelCase();

        $rows = AnalyticsEvent::query()
            ->leftJoin('orders', 'orders.id', '=', 'analytics_events.order_id')
            ->whereBetween('analytics_events.created_at', [$from, $to])
            ->whereIn('analytics_events.type', array_keys(self::STEPS))
            // Đơn đã hủy không tính là mua hàng
            ->where(fn ($q) => $q->where('analytics_events.type', '!=', 'purchase')->orWhere('orders.status', '!=', 'cancelled'))
            ->groupBy(DB::raw($channelSql), 'analytics_events.type')
            ->selectRaw("{$channelSql} as channel, analytics_events.type as type, COUNT(DISTINCT analytics_events.session_hash) as sessions")
            ->get();

        $result = [];
        foreach (self::CHANNELS + ['all' => 'Tất cả kênh'] as $key => $label) {
            $result[$key] = ['label' => $label, 'steps' => array_fill_keys(array_keys(self::STEPS), 0)];
        }

        foreach ($rows as $row) {
            $result[$row->channel]['steps'][$row->type] += (int) $row->sessions;
            $result['all']['steps'][$row->type] += (int) $row->sessions;
        }

        return $result;
    }

    /**
     * Chẩn đoán điểm rơi: Số đơn tăng thêm ≈ Lượng vào bước × Δ tỷ lệ chuyển tiếp × Tỷ lệ chuyển đổi các bước sau.
     *
     * @param  array<string, int>  $steps
     * @return array<int, array{from: string, to: string, entering: int, rate: float, downstream: float, extra_orders: float, priority: bool}>
     */
    public function dropOffDiagnostic(array $steps, float $deltaPoints = 5.0): array
    {
        $keys = array_keys(self::STEPS);
        $rows = [];

        for ($i = 0; $i < count($keys) - 1; $i++) {
            $entering = $steps[$keys[$i]];
            $next = $steps[$keys[$i + 1]];
            $rate = $entering > 0 ? min(1, $next / $entering) : 0.0;
            // Tỷ lệ từ bước kế tiếp tới mua hàng
            $downstream = $next > 0 ? min(1, $steps['purchase'] / $next) : 0.0;

            $rows[] = [
                'from' => self::STEPS[$keys[$i]],
                'to' => self::STEPS[$keys[$i + 1]],
                'entering' => $entering,
                'rate' => $rate * 100,
                'downstream' => $downstream * 100,
                'extra_orders' => round($entering * ($deltaPoints / 100) * $downstream, 1),
                'priority' => false,
            ];
        }

        // Điểm can thiệp ưu tiên = nơi cải thiện cùng một mức Δ mang lại nhiều đơn nhất
        $best = collect($rows)->sortByDesc('extra_orders')->keys()->first();
        if ($best !== null && $rows[$best]['extra_orders'] > 0) {
            $rows[$best]['priority'] = true;
        }

        return $rows;
    }

    /**
     * Kinh tế đơn vị từ đơn đặt qua website (không tính đơn hủy), dùng giá vốn đã chụp vào từng dòng đơn.
     */
    public function unitEconomics(CarbonImmutable $from, CarbonImmutable $to, array $allSteps): array
    {
        $tracked = fn () => Order::query()
            ->where('status', '!=', 'cancelled')
            ->whereHas('analyticsEvents', fn ($q) => $q->where('type', 'purchase'));

        $lines = OrderItem::query()
            ->whereIn('order_id', $tracked()->whereBetween('created_at', [$from, $to])->select('id'))
            ->whereNotNull('cost_price')
            ->selectRaw('SUM(price * quantity) as revenue, SUM(cost_price * quantity) as cogs')
            ->first();

        $goodsRevenue = (int) ($lines->revenue ?? 0);
        $margin = $goodsRevenue > 0 ? ($goodsRevenue - (int) $lines->cogs) / $goodsRevenue : null;

        $orders = $tracked()->whereBetween('created_at', [$from, $to]);
        $orderCount = (clone $orders)->count();
        $aov = $orderCount ? intdiv((int) (clone $orders)->sum('total'), $orderCount) : 0;

        // Số đơn bình quân mỗi khách (toàn thời gian) để ước tính giá trị vòng đời
        $customers = $tracked()->distinct()->count('user_id');
        $ordersPerCustomer = $customers ? $tracked()->count() / $customers : 0;
        $ltv = $margin !== null ? $aov * $margin * $ordersPerCustomer : null;

        // CAC: chi phí quảng cáo / số khách mới mà đơn đầu tiên đến từ chiến dịch
        $adSpend = (int) Campaign::sum('spend');
        $firstOrders = $tracked()->selectRaw('user_id, MIN(id) as first_id')->groupBy('user_id')->pluck('first_id');
        $acquired = Order::whereIn('id', $firstOrders)->whereNotNull('campaign_id')->count();
        $cac = $acquired ? $adSpend / $acquired : null;

        $visits = $allSteps['visit'] ?? 0;
        $conversion = $visits ? ($allSteps['purchase'] ?? 0) / $visits : 0;

        return [
            'goods_revenue' => $goodsRevenue,
            'gross_margin' => $margin,
            'break_even_roas' => $margin ? 1 / $margin : null,
            'aov' => $aov,
            'orders_per_customer' => $ordersPerCustomer,
            'ltv' => $ltv,
            'ad_spend' => $adSpend,
            'acquired_customers' => $acquired,
            'cac' => $cac,
            'ltv_cac' => ($ltv !== null && $cac) ? $ltv / $cac : null,
            'conversion' => $conversion,
            'break_even_cpc' => $margin !== null ? $aov * $margin * $conversion : null,
            'max_cpc' => $ltv !== null ? $ltv * $conversion / self::TARGET_LTV_CAC : null,
        ];
    }

    private function channelCase(): string
    {
        $search = "'".implode("','", self::SEARCH_SOURCES)."'";
        $social = "'".implode("','", self::SOCIAL_SOURCES)."'";

        return "CASE
            WHEN analytics_events.campaign_id IS NOT NULL THEN 'paid'
            WHEN analytics_events.source IS NULL OR analytics_events.source = '' THEN 'direct'
            WHEN analytics_events.source IN ({$search}) THEN 'organic'
            WHEN analytics_events.source IN ({$social}) THEN 'social'
            ELSE 'referral' END";
    }
}
