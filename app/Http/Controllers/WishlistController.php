<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlist = Auth::user()->wishlist;

        $productIds = $wishlist ? $wishlist->items()->pluck('product_id')->toArray() : [];

        $wishlistIds = $productIds;

        $products = count($productIds)
            ? Product::whereIn('id', $productIds)->with('category')->withCardData()->latest()->paginate(12)
            : collect([]);

        return view('pages.wishlist', compact('products', 'wishlistIds'));
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
}
