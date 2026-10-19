<x-layouts.app :title="'Danh sách sản phẩm'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row gap-8">

            {{-- Sidebar Filters --}}
            <div class="w-full md:w-64 shrink-0">
                <div class="bg-white rounded-xl border border-[#efe8e3] p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Bộ lọc</h3>

                    <form action="{{ route('products.index') }}" method="GET">
                        {{-- Search --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Tên sản phẩm..."
                                   class="w-full border border-[#efe8e3] rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-[#c9a9a6]">
                        </div>

                        {{-- Category --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Danh mục</label>
                            <select name="category" class="w-full border border-[#efe8e3] rounded-xl px-3 py-2 text-sm">
                                <option value="">Tất cả</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Price Range --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Khoảng giá</label>
                            <div class="flex gap-2">
                                    <input type="number" name="min_price" value="{{ request('min_price') }}"
                                           placeholder="Từ" class="w-1/2 border border-[#efe8e3] rounded-xl px-3 py-2 text-sm">
                                    <input type="number" name="max_price" value="{{ request('max_price') }}"
                                           placeholder="Đến" class="w-1/2 border border-[#efe8e3] rounded-xl px-3 py-2 text-sm">
                            </div>
                        </div>

                        {{-- Sort --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sắp xếp</label>
                            <select name="sort" class="w-full border border-[#efe8e3] rounded-xl px-3 py-2 text-sm">
                                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Mới nhất</option>
                                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Giá tăng dần</option>
                                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Giá giảm dần</option>
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Tên A-Z</option>
                                <option value="popular" {{ request('sort', request('featured') ? 'popular' : '') == 'popular' ? 'selected' : '' }}>Phổ biến nhất</option>
                                <option value="best_selling" {{ request('sort') == 'best_selling' ? 'selected' : '' }}>Bán chạy</option>
                                <option value="top_rated" {{ request('sort') == 'top_rated' ? 'selected' : '' }}>Đánh giá cao</option>
                                <option value="most_viewed" {{ request('sort') == 'most_viewed' ? 'selected' : '' }}>Xem nhiều</option>
                            </select>
                        </div>

                        {{-- Brand --}}
                        @if($brands->isNotEmpty())
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Thương hiệu</label>
                                <select name="brand" class="w-full border border-[#efe8e3] rounded-xl px-3 py-2 text-sm">
                                    <option value="">Tất cả</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand }}" {{ request('brand') === $brand ? 'selected' : '' }}>
                                            {{ $brand }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <button type="submit" class="w-full bg-[#b8847e] text-white py-2 rounded-xl hover:bg-[#a6736d] transition">
                            Áp dụng
                        </button>
                    </form>
                </div>
            </div>

            {{-- Product Grid --}}
            <div class="flex-1">
                <div class="flex justify-between items-center mb-6">
                    <p class="text-gray-600">Hiển thị {{ $products->count() }} sản phẩm</p>
                </div>

                @if($products->count())
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                        @foreach($products as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="text-center py-20 animate-fade-in-up">
                        <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-[#f5f0ec] to-[#efe8e3] rounded-full flex items-center justify-center">
                            <svg class="h-12 w-12 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-2">Không tìm thấy sản phẩm phù hợp</h2>
                        <p class="text-[#9a9490] mb-6 max-w-sm mx-auto text-sm">
                            Thử điều chỉnh bộ lọc hoặc tìm với từ khóa khác nhé.
                        </p>
                        <a href="{{ route('products.index') }}"
                           class="inline-flex items-center gap-2 bg-[#b8847e] text-white px-6 py-2.5 rounded-lg font-medium text-sm hover:bg-[#a6736d] transition-colors">
                            Xóa bộ lọc &amp; xem tất cả
                        </a>
                    </div>

                    @if($suggestedProducts->isNotEmpty())
                        <div class="mt-4" data-testid="search-suggestions">
                            <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Có thể bạn sẽ thích</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                                @foreach($suggestedProducts as $product)
                                    <x-product-card :product="$product" />
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
