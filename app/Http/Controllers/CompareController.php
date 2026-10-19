<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function index(Request $request)
    {
        // Chỉ nhận ID số nguyên, tối đa 4 sản phẩm (giống giới hạn khi thêm bằng POST)
        $ids = collect((array) $request->input('products', []))
            ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(4)
            ->values()
            ->all();

        if (empty($ids)) {
            $ids = session('compare_products', []);
        } else {
            session(['compare_products' => $ids]);
        }

        $products = Product::whereIn('id', $ids)
            ->where('status', 'active')
            ->with(['category', 'variants'])
            ->withCardData()
            ->withSum(['orderItems as sold_count' => fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'))], 'quantity')
            ->get()
            ->sortBy(fn ($p) => array_search($p->id, $ids))
            ->values();

        $rows = $this->comparisonRows($products);
        $winners = $this->winners($products);

        // Gợi ý thêm sản phẩm cùng danh mục để so sánh (khi mới có 0–3 sản phẩm)
        $suggestions = $products->count() < 4
            ? Product::with('category')->withCardData()
                ->where('status', 'active')
                ->whereNotIn('id', $products->pluck('id'))
                ->when($products->isNotEmpty(), fn ($q) => $q->whereIn('category_id', $products->pluck('category_id')))
                ->orderByDesc('views_count')
                ->limit(4)
                ->get()
            : collect();

        return view('pages.compare', compact('products', 'rows', 'winners', 'suggestions'));
    }

    /**
     * Các tiêu chí so sánh; mỗi dòng có cờ "same" để ẩn khi khách chỉ muốn xem điểm khác nhau.
     *
     * @return array<int, array{label: string, values: array<int, string>, same: bool}>
     */
    private function comparisonRows($products): array
    {
        $join = fn ($items) => $items->filter()->unique()->implode(', ') ?: '—';

        $rows = [
            'Danh mục' => $products->map(fn ($p) => $p->category->name ?? '—'),
            'Thương hiệu' => $products->map(fn ($p) => $p->brand ?: '—'),
            'Size có sẵn' => $products->map(fn ($p) => $join($p->variants->where('stock', '>', 0)->pluck('size'))),
            'Màu có sẵn' => $products->map(fn ($p) => $join($p->variants->where('stock', '>', 0)->pluck('color'))),
            'Tình trạng' => $products->map(fn ($p) => $p->stock > 0 ? ($p->stock <= 5 ? 'Sắp hết (còn '.$p->stock.')' : 'Còn hàng') : 'Hết hàng'),
            'Lượt xem' => $products->map(fn ($p) => number_format((int) $p->views_count, 0, ',', '.')),
        ];

        return collect($rows)->map(fn ($values, $label) => [
            'label' => $label,
            'values' => $values->all(),
            'same' => $values->unique()->count() <= 1,
        ])->values()->all();
    }

    /**
     * Sản phẩm nổi bật theo từng tiêu chí (chỉ khi có từ 2 sản phẩm).
     *
     * @return array<string, int|null> tiêu chí => product id
     */
    private function winners($products): array
    {
        if ($products->count() < 2) {
            return [];
        }

        $best = function (callable $score) use ($products) {
            $top = $products->sortByDesc($score)->first();
            $value = $score($top);

            // Không có "người thắng" khi tất cả bằng nhau hoặc đều bằng 0
            return $value > 0 && $products->filter(fn ($p) => $score($p) == $value)->count() < $products->count()
                ? $top->id
                : null;
        };

        return [
            'cheapest' => $best(fn ($p) => -$p->effectivePrice() + 1_000_000_000),
            'discount' => $best(fn ($p) => $p->isOnSale() ? round(($p->price - $p->sale_price) / max(1, $p->price) * 100) : 0),
            'rating' => $best(fn ($p) => (float) $p->average_rating * 1000 + (int) $p->reviews_count),
            'bestseller' => $best(fn ($p) => (int) $p->sold_count),
        ];
    }

    public function add(Request $request, Product $product)
    {
        $compareProducts = session('compare_products', []);

        if (count($compareProducts) >= 4) {
            $message = 'Chỉ có thể so sánh tối đa 4 sản phẩm!';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if (in_array($product->id, $compareProducts)) {
            $message = 'Sản phẩm đã có trong danh sách so sánh!';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $compareProducts[] = $product->id;
        session(['compare_products' => $compareProducts]);

        $message = 'Đã thêm vào danh sách so sánh!';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'compare_count' => count($compareProducts),
            ]);
        }

        return back()->with('success', $message);
    }

    public function remove(Request $request, Product $product)
    {
        $compareProducts = session('compare_products', []);
        $compareProducts = array_filter($compareProducts, fn ($id) => $id != $product->id);
        session(['compare_products' => array_values($compareProducts)]);

        return back()->with('success', 'Đã xóa khỏi danh sách so sánh!');
    }

    public function clear()
    {
        session()->forget('compare_products');

        return back()->with('success', 'Đã xóa danh sách so sánh!');
    }
}
