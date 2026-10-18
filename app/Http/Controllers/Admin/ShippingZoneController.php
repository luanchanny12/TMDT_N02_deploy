<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShippingZoneController extends Controller
{
    public function create()
    {
        return view('admin.shipping-zones.form', [
            'zone' => new ShippingZone(['is_active' => true, 'provinces' => []]),
            'taken' => $this->provincesTakenByOthers(),
        ]);
    }

    public function store(Request $request)
    {
        $zone = ShippingZone::create($this->validated($request));
        AuditService::log('shipping_zone_created', 'ShippingZone', $zone->id, ['name' => $zone->name, 'fee' => $zone->fee]);

        return redirect()->route('admin.settings.index')->with('success', 'Đã thêm khu vực giao hàng "'.$zone->name.'".');
    }

    public function edit(ShippingZone $shippingZone)
    {
        return view('admin.shipping-zones.form', [
            'zone' => $shippingZone,
            'taken' => $this->provincesTakenByOthers($shippingZone),
        ]);
    }

    public function update(Request $request, ShippingZone $shippingZone)
    {
        $shippingZone->update($this->validated($request, $shippingZone));
        AuditService::log('shipping_zone_updated', 'ShippingZone', $shippingZone->id, ['fee' => $shippingZone->fee]);

        return redirect()->route('admin.settings.index')->with('success', 'Đã cập nhật khu vực "'.$shippingZone->name.'".');
    }

    public function destroy(ShippingZone $shippingZone)
    {
        AuditService::log('shipping_zone_deleted', 'ShippingZone', $shippingZone->id, ['name' => $shippingZone->name]);
        $shippingZone->delete();

        return redirect()->route('admin.settings.index')->with('success', 'Đã xóa khu vực. Các tỉnh trong khu vực này dùng phí ship mặc định.');
    }

    private function validated(Request $request, ?ShippingZone $zone = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'provinces' => 'required|array|min:1',
            'provinces.*' => ['string', 'distinct', Rule::in(ShippingZone::allProvinces())],
            'fee' => 'required|integer|min:0|max:10000000',
            'free_shipping_threshold' => 'nullable|integer|min:0|max:1000000000',
            'sort_order' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'nullable|boolean',
        ], [
            'provinces.required' => 'Chọn ít nhất một tỉnh/thành.',
        ]);

        // Một tỉnh chỉ thuộc một khu vực để phí ship không mơ hồ
        $conflicts = array_intersect($data['provinces'], array_keys($this->provincesTakenByOthers($zone)));
        if ($conflicts) {
            throw ValidationException::withMessages([
                'provinces' => 'Đã thuộc khu vực khác: '.implode(', ', $conflicts).'.',
            ]);
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    /**
     * @return array<string, string> tỉnh => tên khu vực khác đang chứa tỉnh đó
     */
    private function provincesTakenByOthers(?ShippingZone $except = null): array
    {
        $taken = [];
        ShippingZone::query()
            ->when($except?->exists, fn ($q) => $q->whereKeyNot($except->id))
            ->get()
            ->each(function (ShippingZone $z) use (&$taken) {
                foreach ($z->provinces ?? [] as $p) {
                    $taken[$p] = $z->name;
                }
            });

        return $taken;
    }
}
