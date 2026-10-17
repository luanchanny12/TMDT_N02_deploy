<?php

namespace App\Http\Controllers;

use App\Exceptions\CouponException;
use App\Exceptions\OrderException;
use App\Models\Coupon;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use App\Services\Order\PricingService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService,
        private CartService $cartService
    ) {}

    public function index()
    {
        $orders = $this->orderService->getOrdersForUser(Auth::id());

        return view('orders.index', compact('orders'));
    }

    public function show(int $orderId)
    {
        $order = $this->orderService->getOrderForUser($orderId, Auth::id());

        return view('orders.show', compact('order'));
    }

    /**
     * Bước 1→2: validate thông tin giao hàng, lưu vào session, chuyển trang review.
     */
    public function review(Request $request)
    {
        $validated = $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string',
            'shipping_notes' => 'nullable|string|max:500',
            'payment_method' => 'required|in:cod,vnpay',
        ]);

        $cart = $this->cartService->getCartWithItems(Auth::id());

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        session(['checkout' => $validated]);

        return redirect()->route('checkout.review.show');
    }

    /**
     * Bước 2: trang xem lại đơn hàng trước khi xác nhận (HĐĐT có bước xác nhận).
     */
    public function showReview()
    {
        $checkout = session('checkout');

        if (! $checkout) {
            return redirect()->route('checkout.index')
                ->with('error', 'Vui lòng nhập thông tin giao hàng trước khi xác nhận đơn.');
        }

        $cart = $this->cartService->getCartWithItems(Auth::id());
        $cartItems = $cart->items;

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        $cartTotal = $cart->totalPrice();
        $couponCode = session('coupon_code');
        $discount = 0;
        $coupon = null;

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->where('status', 'active')->first();
            if ($coupon) {
                $discount = $coupon->calculateDiscount($cartTotal);
            } else {
                session()->forget('coupon_code');
                $couponCode = null;
            }
        }

        $pricing = app(PricingService::class)->breakdown((int) $cartTotal, (int) $discount);
        $shippingFee = $pricing['shipping_fee'];
        $taxAmount = $pricing['tax_amount'];
        $taxRate = $pricing['tax_rate'];
        $taxLabel = app(PricingService::class)->taxLabel();
        $total = $pricing['total'];

        return view('checkout.review', compact(
            'checkout',
            'cartItems',
            'cartTotal',
            'couponCode',
            'discount',
            'shippingFee',
            'taxAmount',
            'taxRate',
            'taxLabel',
            'total'
        ));
    }

    /**
     * Bước 2→3: bắt buộc đồng ý điều kiện, tạo đơn từ session checkout.
     */
    public function confirm(Request $request)
    {
        $checkout = session('checkout');

        if (! $checkout) {
            // Double-submit: request đầu đã tạo đơn và xóa session checkout → đưa về đơn vừa tạo
            $recentOrder = Auth::user()->orders()
                ->where('created_at', '>=', now()->subSeconds(30))
                ->latest('id')
                ->first();

            if ($recentOrder) {
                return redirect()->route('orders.success', $recentOrder->id);
            }

            return redirect()->route('checkout.index')
                ->with('error', 'Phiên xác nhận đơn hàng đã hết hạn, vui lòng nhập lại thông tin.');
        }

        if (! $request->boolean('agree_terms')) {
            return redirect()->route('checkout.review.show')
                ->with('error', 'Vui lòng đồng ý Điều kiện giao dịch chung');
        }

        return $this->placeOrder($request, $checkout);
    }

    private function placeOrder(Request $request, array $validated)
    {
        try {
            $order = $this->orderService->placeOrder(Auth::user(), [
                'shipping_name' => $validated['shipping_name'],
                'shipping_phone' => $validated['shipping_phone'],
                'shipping_address' => $validated['shipping_address'],
                'note' => $validated['shipping_notes'] ?? null,
                'payment_method' => $validated['payment_method'],
            ]);

            session()->forget('checkout');

            $payment = $this->paymentService->createPayment($order, $request->ip());

            if (isset($payment->redirect_url) && $payment->redirect_url) {
                return redirect()->away($payment->redirect_url);
            }

            return redirect()->route('orders.success', $order)->with('success', 'Đặt hàng thành công!');
        } catch (OrderException|CouponException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Khách tự hủy đơn khi đơn còn "Chờ xử lý" và chưa thanh toán.
     */
    public function cancel(int $orderId)
    {
        $order = $this->orderService->getOrderForUser($orderId, Auth::id());

        try {
            $this->orderService->cancelByCustomer($order);
        } catch (OrderException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('orders.show', $order->id)->with('success', 'Đã hủy đơn hàng.');
    }

    public function success(int $orderId)
    {
        $order = $this->orderService->getOrderForUser($orderId, Auth::id());

        return view('orders.success', compact('order'));
    }
}
