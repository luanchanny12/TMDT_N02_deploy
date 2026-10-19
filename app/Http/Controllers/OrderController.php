<?php

namespace App\Http\Controllers;

use App\Exceptions\CouponException;
use App\Exceptions\OrderException;
use App\Exceptions\PriceChangedException;
use App\Models\ShippingZone;
use App\Services\Cart\CartService;
use App\Services\Order\GuestCheckoutService;
use App\Services\Order\OrderService;
use App\Services\Order\PricingService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /** Báo giá ở bước xác nhận được giữ trong 15 phút. */
    private const QUOTE_MINUTES = 15;

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
        $guestRules = Auth::check() ? [] : ['guest_email' => 'required|email:rfc|max:255'];

        $validated = $request->validate($guestRules + [
            'shipping_name' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'shipping_province' => ['required', 'string', Rule::in(ShippingZone::allProvinces())],
            'shipping_notes' => 'nullable|string|max:500',
            'payment_method' => 'required|in:cod,vnpay',
        ], [
            'guest_email.required' => 'Vui lòng nhập email để nhận xác nhận đơn hàng.',
            'guest_email.email' => 'Email không hợp lệ.',
        ]);

        if (! Auth::check()) {
            if ($this->cartService->getGuestCartItems()->isEmpty()) {
                return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
            }

            app(GuestCheckoutService::class)->createAccountAndLogin($request, $validated['guest_email'], $validated['shipping_name']);
            unset($validated['guest_email']);
        }

        $cart = $this->cartService->getCartWithItems(Auth::id());

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        session(['checkout' => $validated]);

        try {
            $this->storeQuote($validated);
        } catch (OrderException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        return redirect()->route('checkout.review.show');
    }

    /**
     * Báo giá có hạn 15 phút: tính đúng như lúc đặt hàng và ghi nhớ tổng tiền khách đã thấy.
     */
    private function storeQuote(array $checkout): array
    {
        $quote = $this->orderService->quote(Auth::user(), $checkout['shipping_province'] ?? null);

        session(['checkout_quote' => [
            'total' => $quote['total'],
            'expires_at' => now()->addMinutes(self::QUOTE_MINUTES)->getTimestamp(),
        ]]);

        return $quote;
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

        try {
            $quote = $this->storeQuote($checkout);
        } catch (OrderException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        // Giá từng dòng theo báo giá (không dùng giá lưu lúc bỏ vào giỏ)
        $linePrices = collect($quote['lines'])->keyBy('cart_item_id');
        $cartItems->each(function ($item) use ($linePrices) {
            $item->price = $linePrices->get($item->id)['price'] ?? $item->price;
        });

        $pricing = $quote['pricing'];
        $cartTotal = $quote['subtotal'];
        $couponCode = session('coupon_code');
        $discount = $quote['discount'];
        $shippingFee = $pricing['shipping_fee'];
        $shippingRule = app(PricingService::class)->shippingRule($checkout['shipping_province'] ?? null);
        $taxAmount = $pricing['tax_amount'];
        $taxRate = $pricing['tax_rate'];
        $taxLabel = app(PricingService::class)->taxLabel();
        $total = $pricing['total'];
        $quoteExpiresAt = now()->addMinutes(self::QUOTE_MINUTES);

        if ($quote['coupon_error']) {
            session()->now('error', 'Mã giảm giá không còn áp dụng được: '.$quote['coupon_error']);
        }

        return view('checkout.review', compact(
            'checkout',
            'cartItems',
            'cartTotal',
            'couponCode',
            'discount',
            'shippingFee',
            'taxAmount',
            'shippingRule',
            'taxRate',
            'taxLabel',
            'total',
            'quoteExpiresAt'
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

        $quote = session('checkout_quote');
        if (! $quote || $quote['expires_at'] < now()->getTimestamp()) {
            return redirect()->route('checkout.review.show')
                ->with('error', 'Báo giá đã quá '.self::QUOTE_MINUTES.' phút nên hệ thống vừa cập nhật lại giá. Vui lòng kiểm tra và xác nhận lại.');
        }

        return $this->placeOrder($request, $checkout + ['expected_total' => $quote['total']]);
    }

    private function placeOrder(Request $request, array $validated)
    {
        try {
            $order = $this->orderService->placeOrder(Auth::user(), [
                'shipping_name' => $validated['shipping_name'],
                'shipping_phone' => $validated['shipping_phone'],
                'shipping_address' => $validated['shipping_address'],
                'shipping_province' => $validated['shipping_province'] ?? null,
                'note' => $validated['shipping_notes'] ?? null,
                'payment_method' => $validated['payment_method'],
                'expected_total' => $validated['expected_total'] ?? null,
            ]);

            session()->forget(['checkout', 'checkout_quote']);

            $payment = $this->paymentService->createPayment($order, $request->ip());

            if (isset($payment->redirect_url) && $payment->redirect_url) {
                return redirect()->away($payment->redirect_url);
            }

            return redirect()->route('orders.success', $order)->with('success', 'Đặt hàng thành công!');
        } catch (PriceChangedException $e) {
            return redirect()->route('checkout.review.show')->with('error', sprintf(
                'Giá đơn hàng vừa thay đổi từ %s₫ thành %s₫ (giá sản phẩm, khuyến mãi, phí ship hoặc thuế đã được cập nhật). Vui lòng kiểm tra và xác nhận lại — bạn chỉ thanh toán theo số tiền hiển thị.',
                number_format($e->quotedTotal, 0, ',', '.'),
                number_format($e->currentTotal, 0, ',', '.')
            ));
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
