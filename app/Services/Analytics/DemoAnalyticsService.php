<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\CampaignSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Bật/tắt dữ liệu mẫu của trang Phân tích dữ liệu.
 *  - Bật: tạo chiến dịch demo, lượt truy cập/xem/thêm giỏ 14 ngày, sự kiện mua hàng cho đơn demo, lượt xem mẫu trên sản phẩm.
 *  - Tắt: xóa toàn bộ dữ liệu đánh dấu is_demo, lượt xem sản phẩm tính lại từ lượt xem thật.
 * Số liệu thật (sự kiện không phải demo) và đơn hàng luôn được giữ nguyên.
 */
class DemoAnalyticsService
{
    public function isEnabled(): bool
    {
        return AnalyticsEvent::where('is_demo', true)->exists() || Campaign::where('is_demo', true)->exists();
    }

    /**
     * @return array{events: int, campaigns: int}
     */
    public function enable(): array
    {
        $this->disable(); // tạo lại từ đầu để không trùng

        DB::transaction(function () {
            (new CampaignSeeder)->run();

            // Lượt xem mẫu cộng thêm vào lượt xem thật (tắt dữ liệu mẫu sẽ trừ lại về số thật)
            mt_srand(489);
            Product::query()->select('id')->each(function (Product $p) {
                Product::whereKey($p->id)->increment('views_count', mt_rand(20, 500));
            });
        });

        return [
            'events' => AnalyticsEvent::where('is_demo', true)->count(),
            'campaigns' => Campaign::where('is_demo', true)->count(),
        ];
    }

    /**
     * @return array{events: int, campaigns: int}
     */
    public function disable(): array
    {
        $events = AnalyticsEvent::where('is_demo', true)->count();
        $campaignIds = Campaign::where('is_demo', true)->pluck('id');

        DB::transaction(function () use ($campaignIds) {
            AnalyticsEvent::where('is_demo', true)->delete();
            Order::whereIn('campaign_id', $campaignIds)->update(['campaign_id' => null]);
            Campaign::whereIn('id', $campaignIds)->delete();

            // Lượt xem hiển thị trên sản phẩm (thẻ, trang chi tiết, "Xem nhiều", Dashboard) → đúng số lượt xem thật
            Product::query()->update([
                'views_count' => DB::raw("(SELECT COUNT(*) FROM analytics_events WHERE analytics_events.product_id = products.id AND analytics_events.type = 'product_view')"),
            ]);
        });

        return ['events' => $events, 'campaigns' => $campaignIds->count()];
    }
}
