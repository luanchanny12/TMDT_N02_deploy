<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Services\AdminNotifier;
use App\Services\AuditService;
use App\Services\Order\PricingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(PricingService $pricing)
    {
        return view('admin.settings.index', [
            'taxEnabled' => $pricing->taxEnabled(),
            'taxRate' => (float) Setting::get('tax_rate', 8),
            'taxLabel' => $pricing->taxLabel(),
            'taxOnShipping' => $pricing->taxOnShipping(),
            'zones' => ShippingZone::orderBy('sort_order')->orderBy('id')->get(),
            'unzonedProvinces' => array_values(array_diff(ShippingZone::allProvinces(), array_keys(ShippingZone::provinceMap()))),
            'shippingFee' => $pricing->shippingFee(),
            'freeShippingThreshold' => $pricing->freeShippingThreshold(),
            'lowStockThreshold' => (int) Setting::get('low_stock_threshold', AdminNotifier::DEFAULT_LOW_STOCK),
            'example' => $pricing->breakdown(1000000),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'tax_enabled' => 'nullable|boolean',
            'tax_on_shipping' => 'nullable|boolean',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'tax_label' => 'required|string|max:50',
            'shipping_fee' => 'required|integer|min:0|max:10000000',
            'free_shipping_threshold' => 'required|integer|min:0|max:1000000000',
            'low_stock_threshold' => 'nullable|integer|min:0|max:100000',
        ], [
            'tax_rate.max' => 'Thuế suất tối đa 100%.',
            'tax_rate.min' => 'Thuế suất không được âm.',
        ]);

        $before = [
            'tax_enabled' => Setting::get('tax_enabled'),
            'tax_rate' => Setting::get('tax_rate'),
            'shipping_fee' => Setting::get('shipping_fee'),
            'free_shipping_threshold' => Setting::get('free_shipping_threshold'),
        ];

        $values = [
            'tax_enabled' => $request->boolean('tax_enabled') ? 1 : 0,
            'tax_rate' => round((float) $validated['tax_rate'], 2),
            'tax_on_shipping' => $request->boolean('tax_on_shipping') ? 1 : 0,
            'tax_label' => trim($validated['tax_label']),
            'shipping_fee' => (int) $validated['shipping_fee'],
            'free_shipping_threshold' => (int) $validated['free_shipping_threshold'],
            'low_stock_threshold' => (int) ($validated['low_stock_threshold'] ?? AdminNotifier::DEFAULT_LOW_STOCK),
        ];

        Setting::put($values);

        AuditService::log('settings_updated', 'Setting', null, ['before' => $before, 'after' => $values]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Đã lưu cài đặt. Đơn hàng mới sẽ áp dụng ngay; đơn cũ giữ nguyên số thuế lúc đặt.');
    }
}
