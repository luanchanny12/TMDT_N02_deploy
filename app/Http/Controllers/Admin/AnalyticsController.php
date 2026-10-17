<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsReportService;
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
        ]));
    }
}
