<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'product']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('comment', "%{$search}%")
                    ->orWhereHas('product', fn ($pq) => $pq->whereLike('name', "%{$search}%"))
                    ->orWhereHas('user', fn ($uq) => $uq->whereLike('name', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $reviews = $query->latest()->paginate(15)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function approve(Review $review)
    {
        $review->update(['status' => 'approved']);

        AuditService::log('review_approved', 'Review', $review->id);

        return back()->with('success', 'Đã duyệt đánh giá!');
    }

    public function reject(Review $review)
    {
        $review->update(['status' => 'rejected']);

        AuditService::log('review_rejected', 'Review', $review->id);

        return back()->with('success', 'Đã từ chối đánh giá!');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('success', 'Đã xóa đánh giá!');
    }
}
