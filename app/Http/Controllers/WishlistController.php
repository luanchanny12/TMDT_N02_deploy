<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function index()
    {
        $user = Auth::user();
        if (! $user->referral_code) {
            $user->update(['referral_code' => $this->authService->generateUniqueReferralCode()]);
        }
        $referralCode = $user->referral_code;

        $wishlist = $user->wishlist;

        $productIds = $wishlist ? $wishlist->items()->pluck('product_id')->toArray() : [];

        $wishlistIds = $productIds;

        $products = count($productIds)
            ? Product::whereIn('id', $productIds)->with('category')->withCardData()->latest()->paginate(12)
            : collect([]);

        return view('pages.wishlist', compact('products', 'wishlistIds', 'referralCode'));
    }

    public function toggle(Request $request, Product $product)
    {
        $user = Auth::user();
        $wishlist = $user->wishlist ?? $user->wishlist()->create(['user_id' => $user->id]);

        $exists = WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('product_id', $product->id)
            ->first();

        if ($exists) {
            $exists->delete();
            $isWishlisted = false;
            $message = 'Đã bỏ yêu thích';
        } else {
            WishlistItem::create([
                'wishlist_id' => $wishlist->id,
                'product_id' => $product->id,
            ]);
            $isWishlisted = true;
            $message = 'Đã thêm vào yêu thích';
        }

        $wishlistCount = $wishlist->items()->count();

        return response()->json([
            'success' => true,
            'is_wishlisted' => $isWishlisted,
            'wishlist_count' => $wishlistCount,
            'message' => $message,
        ]);
    }

    /**
     * Xem danh sách yêu thích công khai được chia sẻ bởi một người dùng
     */
    public function shared(Request $request, string $code)
    {
        $owner = User::where('referral_code', $code)->firstOrFail();

        $wishlist = $owner->wishlist;
        $productIds = $wishlist ? $wishlist->items()->pluck('product_id')->toArray() : [];

        $products = Product::whereIn('id', $productIds)->with('category')->withCardData()->latest()->paginate(12);

        $currentUser = Auth::user();
        $wishlistIds = ($currentUser && $currentUser->wishlist)
            ? $currentUser->wishlist->items()->pluck('product_id')->toArray()
            : [];

        // Gắn cookie Referral 30 ngày để ghi nhận hoa hồng nếu khách mua hàng
        $cookieName = config('referral.cookie_name', 'referral_code');
        $cookieLifetime = config('referral.cookie_lifetime', 43200);

        return response()
            ->view('pages.wishlist-shared', compact('owner', 'products', 'wishlistIds', 'code'))
            ->cookie($cookieName, $code, $cookieLifetime);
    }

    /**
     * Lưu toàn bộ sản phẩm từ danh sách được chia sẻ vào danh sách yêu thích của người xem (yêu cầu đăng nhập)
     */
    public function saveAllToMine(Request $request, string $code)
    {
        $owner = User::where('referral_code', $code)->firstOrFail();
        $myWishlist = Auth::user()->wishlist ?? Auth::user()->wishlist()->create(['user_id' => Auth::id()]);

        $sourceIds = $owner->wishlist ? $owner->wishlist->items()->pluck('product_id')->toArray() : [];

        $addedCount = 0;
        foreach ($sourceIds as $productId) {
            $existing = WishlistItem::where('wishlist_id', $myWishlist->id)
                ->where('product_id', $productId)
                ->first();

            if (! $existing) {
                WishlistItem::create([
                    'wishlist_id' => $myWishlist->id,
                    'product_id' => $productId,
                ]);
                $addedCount++;
            }
        }

        return back()->with('success', "Đã thêm {$addedCount} sản phẩm từ bộ sưu tập của {$owner->name} vào danh sách yêu thích của bạn!");
    }
}
