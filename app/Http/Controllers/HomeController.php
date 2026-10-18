<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        // Nổi bật: SP đang giảm giá trước, sau đó theo giá cao (khác với "mới nhất")
        $featuredProducts = Product::with('category')->withCardData()
            ->where('status', 'active')
            ->orderByRaw('CASE WHEN sale_price IS NOT NULL AND sale_price < price THEN 0 ELSE 1 END')
            ->orderByDesc('price')
            ->limit(8)
            ->get();

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

        return view('pages.home', compact('featuredProducts', 'newProducts', 'categories', 'flashSaleProducts'));
    }
}
