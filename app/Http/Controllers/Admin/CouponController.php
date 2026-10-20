<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages');

        if ($search = $request->input('search')) {
            $query->whereLike('code', "%{$search}%");
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            if ($status === 'expired') {
                $query->where('end_at', '<', now());
            } else {
                $query->where('status', $status);
            }
        }

        $coupons = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::where('status', 'active')->count(),
            'expired' => Coupon::where('end_at', '<', now())->count(),
            'used' => CouponUsage::count(),
        ];

        return view('admin.coupons.index', compact('coupons', 'stats'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|alpha_dash|unique:coupons,code',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|integer|min:1',
            'max_discount' => 'nullable|integer|min:0',
            'min_order_amount' => 'required|integer|min:0',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'usage_limit' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        if ($validated['type'] === 'percent' && $validated['value'] > 100) {
            return back()->withErrors(['value' => 'Phần trăm giảm không được quá 100%'])->withInput();
        }

        if ($validated['type'] === 'fixed' && $validated['value'] < 1000) {
            return back()->withErrors(['value' => 'Số tiền giảm tối thiểu 1,000₫'])->withInput();
        }

        if (isset($validated['usage_limit']) && $validated['usage_limit'] !== null && $validated['usage_limit'] == 0) {
            $validated['usage_limit'] = null;
        }

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Tạo mã giảm giá thành công!');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|alpha_dash|unique:coupons,code,'.$coupon->id,
            'type' => 'required|in:percent,fixed',
            'value' => 'required|integer|min:1',
            'max_discount' => 'nullable|integer|min:0',
            'min_order_amount' => 'required|integer|min:0',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'usage_limit' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        if ($validated['type'] === 'percent' && $validated['value'] > 100) {
            return back()->withErrors(['value' => 'Phần trăm giảm không được quá 100%'])->withInput();
        }

        if ($validated['type'] === 'fixed' && $validated['value'] < 1000) {
            return back()->withErrors(['value' => 'Số tiền giảm tối thiểu 1,000₫'])->withInput();
        }

        if (isset($validated['usage_limit']) && $validated['usage_limit'] !== null && $validated['usage_limit'] == 0) {
            $validated['usage_limit'] = null;
        }

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Cập nhật mã giảm giá thành công!');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Đã xóa mã giảm giá!');
    }

    public function toggleStatus(Coupon $coupon)
    {
        $coupon->update([
            'status' => $coupon->status === 'active' ? 'inactive' : 'active',
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $coupon->fresh()->status,
            ]);
        }

        return back()->with('success', 'Cập nhật trạng thái thành công!');
    }

    public function usageHistory(Coupon $coupon)
    {
        $usages = CouponUsage::with('user', 'order')
            ->where('coupon_id', $coupon->id)
            ->latest()
            ->paginate(20);

        return view('admin.coupons.usage', compact('coupon', 'usages'));
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Chưa chọn mã nào.'], 400);
        }

        Coupon::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa các mã đã chọn.']);
    }

    public function bulkToggle(Request $request)
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status', 'active');

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Chưa chọn mã nào.'], 400);
        }

        Coupon::whereIn('id', $ids)->update(['status' => $status]);

        return response()->json(['success' => true, 'message' => 'Đã cập nhật trạng thái.']);
    }
}
