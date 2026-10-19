<x-layouts.app :title="$metaTitle" :meta-description="$metaDescription">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-[#9a9490] mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-[#b8847e] transition-colors">Trang chủ</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            @if($parent)
                <a href="{{ route('categories.show', $parent) }}" class="hover:text-[#b8847e] transition-colors">
                    {{ $parent->name }}
                </a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            @endif
            <span class="text-[#3d3d3d] font-medium">{{ $category->name }}</span>
        </nav>

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="font-serif text-3xl font-semibold text-[#3d3d3d] mb-2">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-[#6b6560] text-sm max-w-2xl">{!! nl2br(e($category->description)) !!}</p>
            @endif
        </div>

        {{-- Child categories --}}
        @if($childCategories->isNotEmpty())
            <div class="flex flex-wrap gap-2 mb-8">
                @foreach($childCategories as $child)
                    <a href="{{ route('categories.show', $child) }}"
                       class="bg-white border border-[#efe8e3] rounded-full px-4 py-1.5 text-sm text-[#3d3d3d] hover:bg-[#e8c4c4] hover:border-[#e8c4c4] transition-colors">
                        {{ $child->name }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Toolbar --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <p class="text-sm text-[#6b6560]">
                Hiển thị <span class="font-semibold text-[#3d3d3d]">{{ $products->count() }}</span> sản phẩm
            </p>

            <form method="GET" action="{{ route('categories.show', $category) }}" class="flex items-center gap-2">
                @foreach(request()->only(['min_price', 'max_price']) as $key => $value)
                    @if($value !== null && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="sort" class="text-sm text-[#6b6560]">Sắp xếp:</label>
                <select name="sort" id="sort"
                        class="border border-[#efe8e3] rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#c9a9a6]"
                        onchange="this.form.submit()">
                    <option value="latest" {{ request('sort') === 'latest' || !request()->has('sort') ? 'selected' : '' }}>Mới nhất</option>
                    <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Giá tăng dần</option>
                    <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Giá giảm dần</option>
                    <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Tên A-Z</option>
                    <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>Phổ biến nhất</option>
                    <option value="best_selling" {{ request('sort') === 'best_selling' ? 'selected' : '' }}>Bán chạy</option>
                    <option value="top_rated" {{ request('sort') === 'top_rated' ? 'selected' : '' }}>Đánh giá cao</option>
                    <option value="most_viewed" {{ request('sort') === 'most_viewed' ? 'selected' : '' }}>Xem nhiều</option>
                </select>
            </form>
        </div>

        {{-- Product grid --}}
        @if($products->count())
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @else
            <div class="text-center py-20">
                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-[#f5f0ec] to-[#efe8e3] rounded-full flex items-center justify-center">
                    <svg class="h-12 w-12 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-2">Chưa có sản phẩm nào</h2>
                <p class="text-[#9a9490] mb-6 max-w-sm mx-auto text-sm">
                    Danh mục này hiện chưa có sản phẩm phù hợp. Hãy khám phá danh mục khác nhé.
                </p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-2 bg-[#b8847e] text-white px-6 py-2.5 rounded-lg font-medium text-sm hover:bg-[#a6736d] transition-colors">
                    Xem tất cả sản phẩm
                </a>
            </div>
        @endif
    </div>
</x-layouts.app>
