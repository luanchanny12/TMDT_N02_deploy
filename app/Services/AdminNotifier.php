<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Gửi thông báo tới tất cả quản trị viên đang hoạt động khi có sự kiện cần xử lý.
 * Lỗi gửi thông báo không bao giờ làm hỏng luồng mua hàng.
 */
class AdminNotifier
{
    public const DEFAULT_LOW_STOCK = 5;

    public function send(string $type, string $title, string $message, ?string $url = null, string $level = 'info'): void
    {
        try {
            $admins = User::where('role', 'admin')->where('is_active', true)->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AdminAlert($type, $title, $message, $url, $level));
            }
        } catch (\Throwable $e) {
            Log::warning('Không gửi được thông báo admin', ['type' => $type, 'error' => $e->getMessage()]);
        }
    }

    public function newOrder(Order $order): void
    {
        $this->safely(function () use ($order) {
            $this->send(
                'order_created',
                'Đơn hàng mới '.$order->order_code,
                $order->shipping_name.' · '.number_format($order->total, 0, ',', '.').'₫ · '.($order->payment_method === 'vnpay' ? 'VNPay' : 'COD'),
                route('admin.orders.show', $order),
                'success'
            );

            $this->lowStockFor($order);
        });
    }

    public function orderPaid(Order $order): void
    {
        $this->safely(function () use ($order) {
            $this->send('order_paid', 'Đã thanh toán VNPay', 'Đơn '.$order->order_code.' đã thanh toán '.number_format($order->total, 0, ',', '.').'₫ — có thể xác nhận và giao hàng.', route('admin.orders.show', $order), 'success');
        });
    }

    public function orderCancelledByCustomer(Order $order): void
    {
        $this->safely(function () use ($order) {
            $this->send('order_cancelled', 'Khách hủy đơn '.$order->order_code, 'Khách hàng đã tự hủy đơn, tồn kho đã được hoàn lại.', route('admin.orders.show', $order), 'warning');
        });
    }

    public function refundRequired(Order $order, int $amount): void
    {
        $this->safely(function () use ($order, $amount) {
            $this->send('payment_refund_required', 'Cần hoàn tiền đơn '.$order->order_code, 'Khách đã thanh toán VNPay '.number_format($amount, 0, ',', '.').'₫ cho đơn đã hủy/hết hạn. Hoàn tiền qua cổng VNPay.', route('admin.orders.show', $order), 'danger');
        });
    }

    public function newReview(Review $review): void
    {
        $this->safely(function () use ($review) {
            $review->loadMissing('product:id,name');
            $this->send('review_pending', 'Đánh giá mới chờ duyệt', ($review->product->name ?? 'Sản phẩm').' · '.$review->rating.'★', route('admin.reviews.index', ['status' => 'pending']));
        });
    }

    /**
     * Cảnh báo sản phẩm sắp hết hàng sau khi trừ kho cho đơn.
     */
    public function lowStockFor(Order $order): void
    {
        $this->safely(function () use ($order) {
            $threshold = (int) Setting::get('low_stock_threshold', self::DEFAULT_LOW_STOCK);

            Product::whereIn('id', $order->items()->pluck('product_id'))
                ->where('stock', '<=', $threshold)
                ->get(['id', 'name', 'slug', 'stock'])
                ->each(fn (Product $p) => $this->send(
                    'low_stock',
                    $p->stock > 0 ? 'Sắp hết hàng' : 'Hết hàng',
                    $p->name.' còn '.$p->stock.' sản phẩm.',
                    route('admin.products.edit', $p),
                    $p->stock > 0 ? 'warning' : 'danger'
                ));
        });
    }

    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning('Lỗi tạo thông báo admin: '.$e->getMessage());
        }
    }
}
