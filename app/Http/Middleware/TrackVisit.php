<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ghi nhận nguồn truy cập (UTM, trang giới thiệu) và lượt truy cập cho trang phân tích.
 * Bỏ qua trang admin, request AJAX/JSON, Livewire và bot.
 */
class TrackVisit
{
    public function __construct(private AnalyticsService $analytics) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldTrack($request)) {
            return $next($request);
        }

        $newAttribution = $this->analytics->captureAttribution($request);

        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            $this->analytics->recordVisit($request, $newAttribution);
        }

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return false;
        }

        if ($request->is('admin', 'admin/*', 'livewire/*', 'docs*', 'sitemap.xml', 'payment/*', 'auth/*', 'storage/*')) {
            return false;
        }

        // Admin xem/đặt thử trên cửa hàng không làm sai số liệu khách thật
        if ($request->user()?->role === 'admin') {
            return false;
        }

        $agent = strtolower((string) $request->userAgent());

        return ! preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python/', $agent);
    }
}
