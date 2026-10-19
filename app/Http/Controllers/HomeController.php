<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\RecommendationService;

class HomeController extends Controller
{
    public function index()
    {
        // Gợi ý theo dữ liệu thật: đang hot, bán chạy, xem nhiều, đánh giá cao
        $recommendations = app(RecommendationService::class);
        $recommendTabs = collect([
            'hot' => ['Đang hot', 'Kết hợp lượt bán, lượt xem và đánh giá', $recommendations->hot()],
            'best' => ['Bán chạy', 'Nhiều khách mua nhất', $recommendations->bestSellers()],
            'viewed' => ['Xem nhiều', 'Được quan tâm nhiều nhất', $recommendations->mostViewed()],
            'rated' => ['Đánh giá cao', 'Khách hàng hài lòng nhất', $recommendations->topRated()],
        ])->filter(fn ($tab) => $tab[2]->isNotEmpty());
        $featuredProducts = $recommendTabs->first()[2] ?? collect();

        $newProducts = Product::with('category')->withCardData()
            ->where('status', 'active')
            ->latest()
            ->limit(8)
            ->get();

        $categories = Category::withCount('products')
            ->where('status', 'active')
            ->get();

        // Flash sale: Sản phẩm đang có giá khuyến mãi lớn nhất
        $flashSaleProducts = Product::with('category')->withCardData()
            ->where('status', 'active')
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->orderByRaw('(price - sale_price) / price DESC')
            ->limit(4)
            ->get();

        if ($flashSaleProducts->isEmpty()) {
            $flashSaleProducts = $featuredProducts->take(4);
        }

        return view('pages.home', compact('featuredProducts', 'recommendTabs', 'newProducts', 'categories', 'flashSaleProducts'));
    }
}
