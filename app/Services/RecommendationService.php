<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Gợi ý sản phẩm từ dữ liệu thật của shop:
 *  - Bán chạy: tổng số lượng trong các đơn không bị hủy.
 *  - Xem nhiều: lượt mở trang chi tiết (không tính admin).
 *  - Đánh giá cao: trung bình Bayes của đánh giá đã duyệt — 1 đánh giá 5★ không vượt
 *    sản phẩm có 30 đánh giá 4,8★.
 *  - Đang hot: kết hợp lượt bán, lượt xem và đánh giá.
 *  - Thường mua cùng: sản phẩm xuất hiện chung trong các đơn hàng.
 */
class RecommendationService
{
    /** Số đánh giá "ảo" kéo điểm về mức trung bình khi sản phẩm có ít đánh giá. */
    public const PRIOR_REVIEWS = 3;

    public const PRIOR_RATING = 4.0;

    private ?Collection $scored = null;

    /**
     * Toàn bộ sản phẩm đang bán kèm điểm xếp hạng (tính một lần mỗi request).
     */
    public function scored(): Collection
    {
        return $this->scored ??= Product::with('category')->withCardData()
            ->where('status', 'active')
            ->withSum(['orderItems as sold_count' => fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'))], 'quantity')
            ->get()
            ->each(function (Product $p) {
                $p->sold_count = (int) $p->sold_count;
                $p->rating_score = $this->bayesianRating($p);
                $p->hot_score = $this->hotScore($p);
            });
    }

    public function bayesianRating(Product $p): float
    {
        $n = (int) $p->reviews_count;
        $avg = (float) ($p->approved_rating_avg ?? 0);

        return (self::PRIOR_REVIEWS * self::PRIOR_RATING + $avg * $n) / (self::PRIOR_REVIEWS + $n);
    }

    public function hotScore(Product $p): float
    {
        $ratingBoost = max(0, $this->bayesianRating($p) - 3) * (int) $p->reviews_count * 3;

        return (int) $p->sold_count * 5 + (int) $p->views_count * 0.5 + $ratingBoost;
    }

    public function hot(int $limit = 8): Collection
    {
        return $this->scored()->sortByDesc('hot_score')->take($limit)->values();
    }

    public function bestSellers(int $limit = 8): Collection
    {
        return $this->scored()->where('sold_count', '>', 0)
            ->sortBy([['sold_count', 'desc'], ['views_count', 'desc']])
            ->take($limit)->values();
    }

    public function mostViewed(int $limit = 8): Collection
    {
        return $this->scored()->where('views_count', '>', 0)
            ->sortByDesc('views_count')->take($limit)->values();
    }

    public function topRated(int $limit = 8): Collection
    {
        return $this->scored()->where('reviews_count', '>', 0)
            ->sortBy([['rating_score', 'desc'], ['reviews_count', 'desc']])
            ->take($limit)->values();
    }

    /**
     * Sản phẩm tương tự: cùng danh mục hoặc cùng danh mục cha, xếp theo độ hot; thiếu thì bù sản phẩm hot.
     */
    public function similarTo(Product $product, int $limit = 4): Collection
    {
        $parentId = $product->category?->parent_id ?? $product->category_id;

        $candidates = $this->scored()->reject(fn ($p) => $p->id === $product->id);
        $similar = $candidates
            ->filter(fn ($p) => $p->category_id === $product->category_id
                || ($p->category && ($p->category->parent_id ?? $p->category_id) === $parentId))
            ->sortByDesc('hot_score');

        return $similar->concat($candidates->sortByDesc('hot_score'))
            ->unique('id')->take($limit)->values();
    }

    /**
     * Khách mua sản phẩm này cũng mua: đếm số đơn (không hủy) có cả hai sản phẩm.
     */
    public function boughtTogether(Product $product, int $limit = 4): Collection
    {
        $orderIds = OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'))
            ->pluck('order_id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        $ranking = OrderItem::whereIn('order_id', $orderIds)
            ->where('product_id', '!=', $product->id)
            ->selectRaw('product_id, COUNT(DISTINCT order_id) as together')
            ->groupBy('product_id')
            ->orderByDesc('together')
            ->limit($limit * 2)
            ->pluck('together', 'product_id');

        $products = $this->scored()->whereIn('id', $ranking->keys())->keyBy('id');

        return $ranking->keys()
            ->map(fn ($id) => $products->get($id))
            ->filter()
            ->take($limit)
            ->values();
    }

    /**
     * Tìm kiếm không có kết quả: ưu tiên sản phẩm khớp một phần từ khóa (tên, mô tả, danh mục),
     * sau đó bù bằng sản phẩm đang hot.
     */
    public function forSearchMiss(string $search, int $limit = 8): Collection
    {
        $words = collect(preg_split('/\s+/u', mb_strtolower(trim($search))))
            ->filter(fn ($w) => mb_strlen($w) >= 2)
            ->take(5);

        // So khớp bằng SQL LIKE để giữ đặc tính không phân biệt dấu của collation MySQL ("ao" → "Áo")
        $matchedIds = $words->isEmpty() ? collect() : Product::where('status', 'active')
            ->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhereLike('name', "%{$word}%")
                        ->orWhereLike('description', "%{$word}%")
                        ->orWhereHas('category', fn ($c) => $c->whereLike('name', "%{$word}%"));
                }
            })
            ->pluck('id');

        $matches = $this->scored()->whereIn('id', $matchedIds)->sortByDesc('hot_score');

        return $matches->concat($this->hot($limit))->unique('id')->take($limit)->values();
    }
}
