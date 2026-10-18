<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Services\AdminNotifier;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $user = $request->user();

        if ($user->reviews()->where('product_id', $product->id)->exists()) {
            return back()->withErrors(['comment' => 'Bạn đã đánh giá sản phẩm này rồi.']);
        }

        $hasPurchased = $user->orders()
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->where('status', 'delivered')
            ->exists();

        if (! $hasPurchased) {
            return back()->withErrors(['comment' => 'Bạn cần mua và nhận hàng sản phẩm này trước khi đánh giá.']);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'image' => 'nullable|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'rating.required' => 'Vui lòng chọn số sao.',
            'rating.min' => 'Số sao tối thiểu là 1.',
            'rating.max' => 'Số sao tối đa là 5.',
            'image.mimes' => 'Hình ảnh phải là JPG, PNG hoặc WebP.',
            'image.max' => 'Hình ảnh tối đa 2MB.',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('reviews', 'public');
        }

        $completedOrder = $user->orders()
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->where('status', 'delivered')
            ->latest()
            ->first();

        $validated['user_id'] = $user->id;
        $validated['product_id'] = $product->id;
        $validated['order_id'] = $completedOrder->id;
        $validated['status'] = 'pending';

        $review = Review::create($validated);
        app(AdminNotifier::class)->newReview($review);

        return back()->with('success', 'Đánh giá của bạn đã được gửi thành công và đang chờ duyệt!');
    }
}
