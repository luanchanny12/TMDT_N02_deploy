<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\Analytics\AnalyticsService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->withCardData()->where('status', 'active');

        $search = $request->input('search') ?: $request->input('q');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', '%'.$search.'%')
                    ->orWhereLike('description', '%'.$search.'%');
            });
        }

        if ($request->filled('category')) {
            $category = Category::find($request->category);

            if ($category && $category->parent_id === null) {
                $categoryIds = Category::query()
                    ->where('status', 'active')
                    ->where(function ($q) use ($category) {
                        $q->where('id', $category->id)
                            ->orWhere('parent_id', $category->id);
                    })
                    ->pluck('id');

                $query->whereIn('category_id', $categoryIds);
            } else {
                $query->where('category_id', $request->category);
            }
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        // Nút "Sản phẩm nổi bật" (?featured=1) → mặc định sắp theo độ phổ biến
        $sortBy = $request->get('sort', $request->boolean('featured') ? 'popular' : 'latest');
        $this->applySort($query, $sortBy);

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::where('status', 'active')->get();
        $brands = Product::where('status', 'active')
            ->whereNotNull('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');

        $suggestedProducts = $products->isEmpty()
            ? app(RecommendationService::class)->forSearchMiss((string) $search)
            : collect();

        return view('products.index', compact('products', 'categories', 'brands', 'suggestedProducts'));
    }

    public function category(Request $request, Category $category)
    {
        if ($category->status !== 'active') {
            abort(404);
        }

        $categoryIds = $this->categoryIdsWithDescendants($category);

        $query = Product::with('category')->withCardData()
            ->where('status', 'active')
            ->whereIn('category_id', $categoryIds);

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $this->applySort($query, $request->get('sort', 'latest'));

        $products = $query->paginate(12)->withQueryString();

        $childCategories = $category->children()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $parent = $category->parent_id
            ? Category::where('status', 'active')->find($category->parent_id)
            : null;

        $metaTitle = $category->name.' - SocialShop';
        $metaDescription = $category->description
            ? Str::limit(strip_tags($category->description), 160)
            : 'Mua sắm '.$category->name.' tại SocialShop — nhiều mẫu mã, giá tốt.';

        return view('categories.show', compact(
            'category', 'products', 'childCategories', 'parent',
            'metaTitle', 'metaDescription'
        ));
    }

    public function show(Product $product)
    {
        if ($product->status !== 'active') {
            abort(404);
        }

        // Admin xem cửa hàng không tính vào lượt xem (giống trang Phân tích dữ liệu)
        if (auth()->user()?->role !== 'admin') {
            Product::whereKey($product->id)->increment('views_count');
            $product->views_count = (int) $product->views_count + 1;
        }
        app(AnalyticsService::class)->track('product_view', ['product_id' => $product->id]);

        $product->load([
            'category',
            'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
            'variants' => fn ($q) => $q->orderBy('size')->orderBy('color'),
        ]);
        $soldCount = (int) $product->orderItems()
            ->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'))
            ->sum('quantity');

        $recommendations = app(RecommendationService::class);
        $relatedProducts = $recommendations->similarTo($product);
        $boughtTogether = $recommendations->boughtTogether($product);

        $reviews = $product->reviews()
            ->where('status', 'approved')
            ->with('user')
            ->latest()
            ->paginate(5);

        $canReview = false;
        $hasReviewed = false;

        if (auth()->check()) {
            $user = auth()->user();
            $hasReviewed = $user->reviews()->where('product_id', $product->id)->exists();

            if (! $hasReviewed) {
                $canReview = $user->orders()
                    ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                    ->whereIn('status', ['delivered'])
                    ->exists();
            }
        }

        $hasVariants = $product->variants->isNotEmpty();

        // SEO Meta Tags
        $metaTitle = $product->name.' - SocialShop';
        $metaDescription = Str::limit(strip_tags($product->description ?? $product->name), 160);
        $metaImage = $product->image_url ?? asset('images/og-image.jpg');

        // JSON-LD Structured Data
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $metaDescription,
            'image' => $metaImage,
            'sku' => $product->sku,
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->brand ?? 'SocialShop',
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $product->effectivePrice(),
                'priceCurrency' => 'VND',
                'availability' => ($hasVariants
                    ? $product->variants->sum('stock')
                    : $product->stock) > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => 'SocialShop',
                ],
            ],
            'aggregateRating' => $product->reviews_count > 0 ? [
                '@type' => 'AggregateRating',
                'ratingValue' => $product->average_rating,
                'reviewCount' => $product->reviews_count,
            ] : null,
        ];

        return view('products.show', compact(
            'product', 'soldCount', 'relatedProducts', 'boughtTogether', 'reviews', 'canReview', 'hasReviewed',
            'metaTitle', 'metaDescription', 'metaImage', 'jsonLd', 'hasVariants'
        ));
    }

    /**
     * @return array<int, int>
     */
    private function categoryIdsWithDescendants(Category $category): array
    {
        $ids = [(int) $category->id];
        $queue = [(int) $category->id];

        while ($queue) {
            $children = Category::query()
                ->where('status', 'active')
                ->whereIn('parent_id', $queue)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $queue = array_values(array_diff($children, $ids));
            $ids = array_merge($ids, $queue);
        }

        return $ids;
    }

    private function applySort($query, string $sortBy): void
    {
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'popular':
                $query->withSum(['orderItems as sold_count' => fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'))], 'quantity')
                    ->orderByDesc('sold_count')->orderByDesc('views_count');
                break;
            case 'best_selling':
                $query->withSum(['orderItems as sold_count' => fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'))], 'quantity')
                    ->orderByDesc('sold_count');
                break;
            case 'top_rated':
                $query->orderByDesc('approved_rating_avg')->orderByDesc('reviews_count');
                break;
            case 'most_viewed':
                $query->orderByDesc('views_count');
                break;
            default:
                $query->latest();
        }
    }
}
