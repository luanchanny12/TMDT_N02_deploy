<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Campaign;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Ghi nhận hành vi người dùng (phiên truy cập, xem sản phẩm, thêm giỏ, mua hàng)
 * và nguồn truy cập / chiến dịch quảng cáo theo mô hình last-click.
 * Không lưu IP: mỗi phiên được định danh bằng chuỗi ngẫu nhiên trong session.
 */
class AnalyticsService extends BaseService
{
    public const ATTRIBUTION_COOKIE = 'sc_attribution';

    public const ATTRIBUTION_MINUTES = 60 * 24 * 30; // giữ nguồn chiến dịch 30 ngày

    private const SOURCE_ALIASES = [
        'facebook' => ['facebook.', 'fb.', 'fb.me', 'messenger.'],
        'instagram' => ['instagram.'],
        'tiktok' => ['tiktok.'],
        'youtube' => ['youtube.', 'youtu.be'],
        'google' => ['google.'],
        'zalo' => ['zalo.'],
    ];

    public function sessionId(): string
    {
        $session = session();
        $sid = $session->get('analytics_sid');

        if (! $sid) {
            $sid = Str::random(32);
            $session->put('analytics_sid', $sid);
        }

        return hash('sha256', $sid);
    }

    /**
     * Đọc UTM / trang giới thiệu của request, lưu làm nguồn hiện tại (last-click).
     * Trả về true nếu đây là một lượt truy cập mới từ chiến dịch/nguồn khác.
     */
    public function captureAttribution(Request $request): bool
    {
        $utmSource = $this->clean($request->query('utm_source'));

        if ($utmSource) {
            $campaign = ($utmCampaign = $this->clean($request->query('utm_campaign'), 100))
                ? Campaign::where('utm_campaign', $utmCampaign)->first()
                : null;

            return $this->remember(['campaign_id' => $campaign?->id, 'source' => $utmSource]);
        }

        // Không có UTM: chỉ xét trang giới thiệu ở đầu phiên (vd. click link chia sẻ trên Facebook)
        if (! session()->has('analytics_attribution')) {
            $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

            if ($host && $host !== $request->getHost()) {
                return $this->remember(['campaign_id' => null, 'source' => $this->normalizeHost($host)]);
            }
        }

        return false;
    }

    /**
     * @return array{campaign_id: int|null, source: string|null}
     */
    public function attribution(): array
    {
        $data = session('analytics_attribution');

        if (! is_array($data)) {
            $cookie = request()->cookie(self::ATTRIBUTION_COOKIE);
            $data = is_string($cookie) ? json_decode($cookie, true) : null;
        }

        return [
            'campaign_id' => isset($data['campaign_id']) && is_numeric($data['campaign_id']) ? (int) $data['campaign_id'] : null,
            'source' => isset($data['source']) && is_string($data['source']) ? $this->clean($data['source']) : null,
        ];
    }

    public function track(string $type, array $attributes = []): void
    {
        if (auth()->user()?->role === 'admin') {
            return; // không tính thao tác của quản trị viên
        }

        try {
            $attribution = $this->attribution();

            AnalyticsEvent::create(array_merge([
                'type' => $type,
                'session_hash' => $this->sessionId(),
                'user_id' => auth()->id(),
                'campaign_id' => $attribution['campaign_id'],
                'source' => $attribution['source'],
            ], $attributes));
        } catch (\Throwable $e) {
            // Thống kê không được làm hỏng luồng mua hàng
            Log::warning('Analytics tracking failed', ['type' => $type, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Ghi một lượt truy cập (visit) cho đầu mỗi phiên, hoặc khi khách quay lại qua một chiến dịch khác.
     */
    public function recordVisit(Request $request, bool $newAttribution): void
    {
        if (session('analytics_visit_recorded') && ! $newAttribution) {
            return;
        }

        session()->put('analytics_visit_recorded', true);
        $this->track('visit', ['path' => Str::limit('/'.ltrim($request->path(), '/'), 250, '')]);
    }

    private function remember(array $attribution): bool
    {
        $previous = session('analytics_attribution');
        session()->put('analytics_attribution', $attribution);
        Cookie::queue(self::ATTRIBUTION_COOKIE, json_encode($attribution), self::ATTRIBUTION_MINUTES);

        return $previous !== $attribution;
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(preg_replace('/^(www\.|m\.|l\.|lm\.)/', '', $host));

        foreach (self::SOURCE_ALIASES as $source => $needles) {
            foreach ($needles as $needle) {
                if (str_starts_with($host, $needle) || str_contains($host, '.'.$needle)) {
                    return $source;
                }
            }
        }

        return Str::limit($host, 50, '');
    }

    private function clean(mixed $value, int $max = 50): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9._\-]/', '', $value);

        return $value === '' ? null : substr($value, 0, $max);
    }
}
