<?php

namespace App\Http\Controllers;

use App\Exceptions\CartException;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Services\Analytics\AnalyticsService;
use App\Services\Cart\CartService;
use App\Services\Order\PricingService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function index()
    {
        if ($this->cartService->isGuest()) {
            $cartItems = $this->cartService->getGuestCartItems();
            $cartTotal = $cartItems->sum(fn ($item) => $item->quantity * $item->price);

            return view('cart.index', compact('cartItems', 'cartTotal'));
        }

        $cart = $this->cartService->getCartWithItems(auth()->id());
        $cartItems = $cart->items;
        $cartTotal = $cart->totalPrice();

        return view('cart.index', compact('cartItems', 'cartTotal'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $variantId = $request->filled('variant_id') ? (int) $request->variant_id : null;

        if ($variantId !== null) {
            $variant = ProductVariant::find($variantId);
            if (! $variant || $variant->product_id !== (int) $request->product_id) {
                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => 'Biến thể không hợp lệ.'], 422)
                    : redirect()->back()->with('error', 'Biến thể không hợp lệ.');
            }
        }

        try {
            if ($this->cartService->isGuest()) {
                $this->cartService->addGuestItem(
                    (int) $request->product_id,
                    (int) ($request->quantity ?? 1),
                    $variantId
                );
                $this->trackAddToCart($request);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'cartCount' => $this->cartService->getGuestItemCount(),
                    ]);
                }

                return redirect()->route('cart.index')->with('success', 'Đã thêm sản phẩm vào giỏ hàng!');
            }

            $this->cartService->addItem(
                auth()->id(),
                (int) $request->product_id,
                (int) ($request->quantity ?? 1),
                $variantId
            );
            $this->trackAddToCart($request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'cartCount' => $this->cartService->getItemCount(auth()->id()),
                ]);
            }

            return redirect()->route('cart.index')->with('success', 'Đã thêm sản phẩm vào giỏ hàng!');
        } catch (CartException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function trackAddToCart(Request $request): void
    {
        app(AnalyticsService::class)->track('add_to_cart', [
            'product_id' => (int) $request->product_id,
            'value' => (int) ($request->quantity ?? 1),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'rowId' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            if ($this->cartService->isGuest()) {
                $this->cartService->updateGuestItem((string) $request->rowId, (int) $request->quantity);

                return redirect()->route('cart.index')->with('success', 'Cập nhật giỏ hàng thành công!');
            }

            $this->cartService->updateItem(
                auth()->id(),
                (int) $request->rowId,
                (int) $request->quantity
            );

            return redirect()->route('cart.index')->with('success', 'Cập nhật giỏ hàng thành công!');
        } catch (CartException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }
    }

    public function remove(Request $request)
    {
        $request->validate([
            'rowId' => 'required',
        ]);

        if ($this->cartService->isGuest()) {
            $this->cartService->removeGuestItem((string) $request->rowId);

            return redirect()->route('cart.index')->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng!');
        }

        $this->cartService->removeItem(auth()->id(), (int) $request->rowId);

        return redirect()->route('cart.index')->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng!');
    }

    public function clear()
    {
        if ($this->cartService->isGuest()) {
            $this->cartService->clearGuestCart();

            return redirect()->route('cart.index')->with('success', 'Đã xóa toàn bộ giỏ hàng!');
        }

        $this->cartService->clearCart(auth()->id());

        return redirect()->route('cart.index')->with('success', 'Đã xóa toàn bộ giỏ hàng!');
    }

    public function checkout()
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để tiến hành thanh toán!');
        }

        $cart = $this->cartService->getCartWithItems(auth()->id());
        $cartItems = $cart->items;

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống!');
        }

        $cartTotal = $cart->totalPrice();

        // Lấy coupon từ session (nếu có)
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

        $total = $cartTotal - $discount;
        $pricingConfig = app(PricingService::class)->clientConfig();

        return view('checkout.index', compact('cartItems', 'cartTotal', 'couponCode', 'discount', 'total', 'pricingConfig'));
    }
}
