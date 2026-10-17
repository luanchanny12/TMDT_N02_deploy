<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Chiến dịch quảng cáo + lượt truy cập 14 ngày gần nhất để trang Phân tích dữ liệu có số liệu demo.
 */
class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2026);

        $campaigns = collect([
            ['name' => 'Sale Thu 2026 - Facebook Ads', 'platform' => 'facebook', 'utm_campaign' => 'sale-thu-2026-fb', 'landing_path' => '/danh-muc/thoi-trang-nu', 'spend' => 1500000],
            ['name' => 'Sneaker Week - TikTok', 'platform' => 'tiktok', 'utm_campaign' => 'sneaker-week-tiktok', 'landing_path' => '/danh-muc/giay-dep', 'spend' => 900000],
            ['name' => 'Review túi xách - YouTube', 'platform' => 'youtube', 'utm_campaign' => 'review-tui-xach-yt', 'landing_path' => '/danh-muc/tui-xach', 'spend' => 1200000],
        ])->map(fn ($c) => Campaign::create($c + [
            'utm_source' => $c['platform'],
            'utm_medium' => 'cpc',
            'starts_on' => now()->subDays(14)->toDateString(),
            'status' => 'active',
        ]));

        // Gán khoảng một nửa đơn demo cho các chiến dịch / nguồn tự nhiên
        $sources = ['google', 'zalo', null, null];
        Order::query()->orderBy('id')->get()->each(function (Order $order, int $i) use ($campaigns, $sources) {
            $campaign = $i % 2 === 0 ? $campaigns[$i % 3] : null;
            $order->forceFill([
                'campaign_id' => $campaign?->id,
                'traffic_source' => $campaign?->utm_source ?? $sources[$i % count($sources)],
            ])->saveQuietly();
        });

        $productIds = Product::where('status', 'active')->pluck('id')->all();
        $rows = [];

        for ($d = 13; $d >= 0; $d--) {
            $day = Carbon::now()->subDays($d)->startOfDay();
            $visitsToday = mt_rand(35, 70);

            for ($v = 0; $v < $visitsToday; $v++) {
                $session = hash('sha256', "seed-{$d}-{$v}");
                $roll = mt_rand(1, 100);
                $campaign = $roll <= 45 ? $campaigns[$roll % 3] : null;
                $source = $campaign?->utm_source ?? [null, 'google', 'facebook', 'zalo'][$roll % 4];
                $at = $day->copy()->addMinutes(mt_rand(8 * 60, 23 * 60));
                $base = ['session_hash' => $session, 'campaign_id' => $campaign?->id, 'source' => $source, 'user_id' => null, 'order_id' => null, 'value' => null];

                $rows[] = $base + ['type' => 'visit', 'product_id' => null, 'path' => $campaign?->landing_path ?? '/', 'created_at' => $at];

                if (mt_rand(1, 100) <= 65 && $productIds) {
                    $product = $productIds[array_rand($productIds)];
                    $rows[] = $base + ['type' => 'product_view', 'product_id' => $product, 'path' => null, 'created_at' => $at->copy()->addMinute()];

                    if (mt_rand(1, 100) <= 30) {
                        $rows[] = $base + ['type' => 'add_to_cart', 'product_id' => $product, 'path' => null, 'value' => 1, 'created_at' => $at->copy()->addMinutes(3)];
                    }
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            AnalyticsEvent::insert($chunk);
        }
    }
}
