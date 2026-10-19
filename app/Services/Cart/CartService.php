<?php

namespace App\Services\Cart;

use App\Exceptions\CartException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BaseService;
use Illuminate\Support\Collection;

class CartService extends BaseService
{
    private const SESSION_KEY = 'guest_cart';

    public function getOrCreateCart(int $userId): Cart
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    public function getCartWithItems(int $userId): Cart
    {
        $cart = $this->getOrCreateCart($userId);

        return $cart->load([
            'items.product.images' => fn ($q) => $q->where('is_primary', true)->limit(1),
            'items.variant',
            'items.product',
        ]);
    }

    public function addItem(int $userId, int $productId, int $quantity = 1, ?int $variantId = null): CartItem
    {
        $product = Product::active()->findOrFail($productId);
        $variant = null;
        $this->ensureVariantChosen($product, $variantId);

        if ($variantId !== null) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->findOrFail($variantId);
        }

        $cart = $this->getOrCreateCart($userId);

        $cartItemQuery = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId);

        if ($variantId !== null) {
            $cartItemQuery->where('variant_id', $variantId);
        } else {
            $cartItemQuery->whereNull('variant_id');
        }

        $cartItem = $cartItemQuery->first();

        $currentQty = $cartItem ? $cartItem->quantity : 0;
        $newTotalQty = $currentQty + $quantity;

        $availableStock = $variant ? $variant->stock : $product->stock;
        if ($availableStock < $newTotalQty) {
            throw new CartException(
                "Sản phẩm \"{$product->name}\" chỉ còn {$availableStock} trong kho."
            );
        }

        $unitPrice = $variant ? $variant->unitPrice() : $product->effectivePrice();

        if ($cartItem) {
            $cartItem->update([
                'quantity' => $newTotalQty,
                'price' => $unitPrice,
            ]);
        } else {
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $unitPrice,
            ]);
        }

        return $cartItem->load(['product', 'variant']);
    }

    public function updateItem(int $userId, int $cartItemId, int $quantity): CartItem
    {
        $cart = $this->getOrCreateCart($userId);
        $cartItem = CartItem::where('id', $cartItemId)
            ->where('cart_id', $cart->id)
            ->with(['product', 'variant'])
            ->firstOrFail();

        $availableStock = $cartItem->variant
            ? $cartItem->variant->stock
            : $cartItem->product->stock;

        if ($availableStock < $quantity) {
            throw new CartException(
                "Sản phẩm \"{$cartItem->product->name}\" chỉ còn {$availableStock} trong kho."
            );
        }

        $unitPrice = $cartItem->variant
            ? $cartItem->variant->unitPrice()
            : $cartItem->product->effectivePrice();

        $cartItem->update([
            'quantity' => $quantity,
            'price' => $unitPrice,
        ]);

        return $cartItem;
    }

    public function removeItem(int $userId, int $cartItemId): void
    {
        $cart = $this->getOrCreateCart($userId);

        CartItem::where('id', $cartItemId)
            ->where('cart_id', $cart->id)
            ->firstOrFail()
            ->delete();
    }

    /**
     * Trả sản phẩm của đơn chưa thanh toán (VNPay bị hủy / hết hạn) về giỏ để khách đặt lại ngay.
     * Bỏ qua sản phẩm đã ngừng bán, dòng khách đã tự thêm lại, và giới hạn theo tồn kho hiện tại.
     */
    public function restoreFromOrder(Order $order): int
    {
        $cart = $this->getOrCreateCart($order->user_id);
        $restored = 0;

        foreach ($order->items()->with(['product', 'variant'])->get() as $item) {
            $product = $item->product;
            if (! $product || $product->status !== 'active') {
                continue;
            }

            $exists = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $item->product_id)
                ->where(fn ($q) => $item->variant_id ? $q->where('variant_id', $item->variant_id) : $q->whereNull('variant_id'))
                ->exists();
            $stock = $item->variant ? $item->variant->stock : $product->stock;

            if ($exists || $stock < 1 || ($item->variant_id && ! $item->variant)) {
                continue;
            }

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => min($item->quantity, $stock),
                'price' => $item->variant ? $item->variant->unitPrice() : $product->effectivePrice(),
            ]);
            $restored++;
        }

        return $restored;
    }

    public function clearCart(int $userId): void
    {
        $cart = Cart::where('user_id', $userId)->first();
        $cart?->items()->delete();
    }

    public function getItemCount(int $userId): int
    {
        $cart = Cart::where('user_id', $userId)->first();
        if (! $cart) {
            return 0;
        }

        return (int) CartItem::where('cart_id', $cart->id)->sum('quantity');
    }

    /**
     * Sản phẩm có size/màu phải chọn biến thể — tránh đơn hàng thiếu size.
     */
    private function ensureVariantChosen(Product $product, ?int $variantId): void
    {
        if ($variantId === null && $product->hasVariants()) {
            throw new CartException("Vui lòng chọn size/màu cho \"{$product->name}\".");
        }
    }

    // ─── Guest cart (session) ────────────────────────────────────────────────

    private function guestKey(int $productId, ?int $variantId = null): string
    {
        return $variantId !== null
            ? $productId.':'.$variantId
            : (string) $productId;
    }

    public function addGuestItem(int $productId, int $quantity = 1, ?int $variantId = null): void
    {
        $product = Product::active()->findOrFail($productId);
        $variant = null;
        $this->ensureVariantChosen($product, $variantId);

        if ($variantId !== null) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->findOrFail($variantId);
        }

        $key = $this->guestKey($productId, $variantId);
        $cart = session()->get(self::SESSION_KEY, []);
        $current = (int) ($cart[$key] ?? 0);
        $newTotal = $current + $quantity;

        $availableStock = $variant ? $variant->stock : $product->stock;
        if ($availableStock < $newTotal) {
            throw new CartException(
                "Sản phẩm \"{$product->name}\" chỉ còn {$availableStock} trong kho."
            );
        }

        $cart[$key] = $newTotal;
        session()->put(self::SESSION_KEY, $cart);
    }

    public function updateGuestItem(string $rowId, int $quantity): void
    {
        $cart = session()->get(self::SESSION_KEY, []);
        if (! isset($cart[$rowId])) {
            throw new CartException('Sản phẩm không có trong giỏ hàng.');
        }

        [$productId, $variantId] = $this->parseGuestKey($rowId);
        $product = Product::active()->findOrFail($productId);
        $variant = $variantId !== null
            ? ProductVariant::where('product_id', $product->id)->find($variantId)
            : null;

        $availableStock = $variant ? $variant->stock : $product->stock;
        if ($availableStock < $quantity) {
            throw new CartException(
                "Sản phẩm \"{$product->name}\" chỉ còn {$availableStock} trong kho."
            );
        }

        $cart[$rowId] = $quantity;
        session()->put(self::SESSION_KEY, $cart);
    }

    public function removeGuestItem(string $rowId): void
    {
        $cart = session()->get(self::SESSION_KEY, []);
        unset($cart[$rowId]);
        session()->put(self::SESSION_KEY, $cart);
    }

    public function clearGuestCart(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function getGuestItemCount(): int
    {
        return (int) array_sum(session()->get(self::SESSION_KEY, []));
    }

    /**
     * @return array{0:int, 1:?int}
     */
    private function parseGuestKey(string $key): array
    {
        if (str_contains($key, ':')) {
            [$productId, $variantId] = explode(':', $key, 2);

            return [(int) $productId, (int) $variantId];
        }

        return [(int) $key, null];
    }

    /**
     * @return Collection<int, object{id:string, product_id:int, variant_id:?int, quantity:int, price:int, product: Product, variant: ?ProductVariant}>
     */
    public function getGuestCartItems(): Collection
    {
        $cart = session()->get(self::SESSION_KEY, []);
        if (empty($cart)) {
            return collect();
        }

        $parsed = [];
        $productIds = [];
        $variantIds = [];

        foreach ($cart as $key => $qty) {
            $row = $this->parseGuestKey((string) $key);
            $parsed[$key] = [
                'product_id' => $row[0],
                'variant_id' => $row[1],
                'quantity' => (int) $qty,
            ];
            $productIds[] = $row[0];
            if ($row[1] !== null) {
                $variantIds[] = $row[1];
            }
        }

        $products = Product::with(['images' => fn ($q) => $q->where('is_primary', true)->limit(1)])
            ->whereIn('id', array_unique($productIds))
            ->get()
            ->keyBy('id');

        $variants = ! empty($variantIds)
            ? ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        return collect($parsed)->map(function ($row, $key) use ($products, $variants) {
            $product = $products->get($row['product_id']);
            if (! $product) {
                return null;
            }

            $variant = $row['variant_id'] !== null
                ? $variants->get($row['variant_id'])
                : null;

            return (object) [
                'id' => $key,
                'product_id' => $row['product_id'],
                'variant_id' => $row['variant_id'],
                'quantity' => $row['quantity'],
                'price' => $variant ? $variant->unitPrice() : $product->effectivePrice(),
                'product' => $product,
                'variant' => $variant,
            ];
        })->filter()->values();
    }

    /**
     * Gộp giỏ guest (session) vào giỏ user sau khi đăng nhập.
     */
    public function mergeGuestCart(int $userId): void
    {
        $cart = session()->get(self::SESSION_KEY, []);
        if (empty($cart)) {
            return;
        }

        foreach ($cart as $key => $qty) {
            [$productId, $variantId] = $this->parseGuestKey((string) $key);

            try {
                $this->addItem($userId, $productId, (int) $qty, $variantId);
            } catch (CartException) {
                // Bỏ qua item hết hàng / không còn kinh doanh
            }
        }

        $this->clearGuestCart();
    }

    public function isGuest(): bool
    {
        return ! auth()->check();
    }
}
