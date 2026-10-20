<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AuditService;
use App\Services\Order\OrderService;
use App\Services\Referral\ReferralService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private ReferralService $referralService,
        private OrderService $orderService
    ) {}

    public function index(Request $request)
    {
        $query = Order::with('user');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('order_code', "%{$search}%")
                    ->orWhereLike('shipping_name', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'user']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,shipping,delivered,cancelled',
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
            // Không cho reopen đơn đã hủy (đã hoàn kho) — tránh hoàn kho 2 lần
            return $this->reject($request, 'Không thể mở lại đơn đã hủy.');
        }

        // Đơn thanh toán online chưa nhận tiền thì không được xác nhận/giao
        if ($order->payment_method !== 'cod'
            && $order->payment_status !== 'paid'
            && in_array($newStatus, ['confirmed', 'shipping', 'delivered'], true)) {
            return $this->reject($request, 'Đơn thanh toán online chưa được thanh toán — không thể xác nhận/giao hàng.');
        }

        if ($newStatus === 'cancelled') {
            $this->orderService->cancelOrder($order, 'admin');
        } elseif ($newStatus !== $oldStatus) {
            $order->update(['status' => $newStatus]);

            AuditService::log('order_status_changed', 'Order', $order->id, [
                'from' => $oldStatus,
                'to' => $newStatus,
            ]);

            if ($newStatus === 'delivered') {
                $this->referralService->completeReferral($order);
            }

            $this->orderService->notifyStatusChanged($order, $oldStatus);
        }

        $order->refresh();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $order->status,
            ]);
        }

        return back()->with('success', 'Cập nhật trạng thái thành công!');
    }

    private function reject(Request $request, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
