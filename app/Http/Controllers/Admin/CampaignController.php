<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\Analytics\AnalyticsReportService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function index(AnalyticsReportService $reports)
    {
        return view('admin.campaigns.index', ['campaigns' => $reports->campaignStats()]);
    }

    public function create()
    {
        return view('admin.campaigns.form', ['campaign' => new Campaign([
            'platform' => 'facebook',
            'utm_medium' => 'cpc',
            'landing_path' => '/',
            'status' => 'active',
            'starts_on' => now(),
        ])]);
    }

    public function store(Request $request)
    {
        $campaign = Campaign::create($this->validated($request));
        AuditService::log('campaign_created', 'Campaign', $campaign->id, ['name' => $campaign->name]);

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'Đã tạo chiến dịch. Sao chép link UTM để dán vào quảng cáo.');
    }

    public function edit(Campaign $campaign)
    {
        return view('admin.campaigns.form', compact('campaign'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $campaign->update($this->validated($request, $campaign));
        AuditService::log('campaign_updated', 'Campaign', $campaign->id, ['spend' => $campaign->spend]);

        return redirect()->route('admin.campaigns.index')->with('success', 'Đã cập nhật chiến dịch.');
    }

    public function destroy(Campaign $campaign)
    {
        AuditService::log('campaign_deleted', 'Campaign', $campaign->id, ['name' => $campaign->name]);
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('success', 'Đã xóa chiến dịch (đơn hàng cũ vẫn giữ nguyên).');
    }

    private function validated(Request $request, ?Campaign $campaign = null): array
    {
        // Mã chiến dịch chỉ gồm chữ thường, số, gạch ngang — dùng làm utm_campaign trên link
        $request->merge([
            'utm_campaign' => Str::slug((string) ($request->input('utm_campaign') ?: $request->input('name'))),
            'utm_source' => Str::slug((string) ($request->input('utm_source') ?: $request->input('platform'))),
            'utm_medium' => Str::slug((string) ($request->input('utm_medium') ?: 'cpc')),
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'platform' => ['required', Rule::in(array_keys(Campaign::PLATFORMS))],
            'utm_source' => 'required|string|max:50',
            'utm_medium' => 'required|string|max:50',
            'utm_campaign' => ['required', 'string', 'max:100', Rule::unique('campaigns', 'utm_campaign')->ignore($campaign?->id)],
            'landing_path' => ['required', 'string', 'max:255', 'regex:#^/(?!/)#'],
            'spend' => 'required|integer|min:0|max:100000000000',
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'status' => ['required', Rule::in(['active', 'paused', 'ended'])],
            'note' => 'nullable|string|max:2000',
        ], [
            'utm_campaign.unique' => 'Mã chiến dịch đã tồn tại, hãy đặt mã khác.',
            'landing_path.regex' => 'Trang đích phải là đường dẫn nội bộ bắt đầu bằng "/", ví dụ /products hoặc /danh-muc/giay-dep.',
        ]);

        return $data;
    }
}
