<x-layouts.app :title="'Sản phẩm yêu thích'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">

        {{-- Page Header --}}
        <div class="flex items-center justify-between flex-wrap gap-4 mb-8 pb-5 border-b border-[#efe8e3]">
            <div>
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e] shadow-sm">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">
                        Sản phẩm yêu thích
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-[#9a9490] mt-1.5">
                    Bộ sưu tập các thiết kế bạn đã lưu để theo dõi giá và đặt mua sau ({{ $products->count() }} sản phẩm)
                </p>
            </div>

            @if($products->count())
                <a href="{{ route('products.index') }}"
                   class="text-xs sm:text-sm font-semibold text-[#b8847e] hover:text-[#a6736d] flex items-center gap-1.5 transition-colors bg-[#faf7f4] border border-[#efe8e3] px-4 py-2 rounded-xl shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Khám phá thêm sản phẩm
                </a>
            @endif
        </div>

        @if($products->count())
            {{-- Wishlist Items Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($products as $product)
                    <x-product-card :product="$product" :wishlistIds="$wishlistIds" />
                @endforeach
            </div>

            {{-- Pagination --}}
            @if(method_exists($products, 'links'))
                <div class="mt-10">
                    {{ $products->links() }}
                </div>
            @endif
        @else
            {{-- Empty Wishlist State --}}
            <div class="bg-white border border-[#efe8e3] rounded-3xl p-10 sm:p-16 text-center max-w-2xl mx-auto shadow-sm animate-fade-in-up">
                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-[#faf7f4] to-[#f0e8e4] border border-[#efe8e3] rounded-full flex items-center justify-center text-[#b8847e] shadow-inner">
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28] mb-2">Chưa có sản phẩm yêu thích</h2>
                <p class="text-xs sm:text-sm text-[#9a9490] max-w-md mx-auto mb-8 leading-relaxed">
                    Hãy nhấn vào biểu tượng trái tim trên các mẫu thiết kế bạn quan tâm để lưu lại vào bộ sưu tập cá nhân của mình.
                </p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-2 bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white px-8 py-4 rounded-2xl font-bold hover:shadow-lg hover:shadow-[#b8847e]/30 transition-all text-sm btn-shine">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Khám phá bộ sưu tập ngay
                </a>
            </div>
        @endif
    </div>
</x-layouts.app>
