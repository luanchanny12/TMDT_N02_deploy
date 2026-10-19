<x-layouts.app>
    {{-- Hero --}}
    <section class="relative" style="background: linear-gradient(135deg, rgba(250,247,244,.86) 0%, rgba(243,235,230,.84) 50%, rgba(232,223,216,.86) 100%), url('https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1600&h=900&fit=crop&q=85') center/cover no-repeat;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20 text-center relative z-10">
            <p class="text-[11px] uppercase tracking-[3px] text-[#b8847e] mb-3 font-medium">Bộ sưu tập 2026</p>
            <h1 class="font-serif text-4xl md:text-5xl lg:text-[3.5rem] font-semibold text-[#3d3d3d] leading-tight mb-4">
                Thời trang<br><em class="italic text-[#b8847e]">dịu nhẹ</em> mỗi ngày
            </h1>
            <p class="text-[#6b6560] text-base md:text-lg max-w-lg mx-auto mb-8">
                Khám phá phong cách thanh lịch — từ áo thun basic đến váy dự tiệc, giá tốt &amp; ưu đãi đến 40%
            </p>
            <div class="flex gap-3 justify-center flex-wrap">
                <a href="{{ route('products.index') }}"
                   class="bg-[#b8847e] text-white px-7 py-3 rounded-lg font-medium text-sm hover:bg-[#a6736d] transition-colors">
                    Tất cả sản phẩm
                </a>
                <a href="{{ route('products.index', ['featured' => 1]) }}"
                   class="border border-[#c9a9a6] text-[#b8847e] bg-transparent px-7 py-3 rounded-lg font-medium text-sm hover:bg-[#e8c4c4] hover:text-[#3d3d3d] hover:border-[#e8c4c4] transition-colors">
                    Sản phẩm nổi bật
                </a>
            </div>
        </div>
    </section>

    {{-- Ưu đãi mua sắm: số liệu đọc từ Admin → Cài đặt nên luôn khớp chính sách thật --}}
    @php
        $pricing = app(\App\Services\Order\PricingService::class);
        $freeFrom = $pricing->nationwideFreeShippingThreshold();
    @endphp
    <section class="bg-[#3d3a37] text-white" data-testid="shop-benefits">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="flex items-center gap-3">
                <svg class="h-8 w-8 flex-shrink-0 text-[#e8c4c4]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                <div>
                    <p class="text-sm font-semibold">Freeship toàn quốc</p>
                    <p class="text-xs text-white/70">Cho đơn từ {{ number_format($freeFrom, 0, ',', '.') }}₫</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <svg class="h-8 w-8 flex-shrink-0 text-[#e8c4c4]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <div>
                    <p class="text-sm font-semibold">Đổi trả 7 ngày</p>
                    <p class="text-xs text-white/70"><a href="{{ route('pages.return-policy') }}" class="underline decoration-white/30 hover:text-white">Xem chính sách</a></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <svg class="h-8 w-8 flex-shrink-0 text-[#e8c4c4]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <div>
                    <p class="text-sm font-semibold">COD &amp; VNPay</p>
                    <p class="text-xs text-white/70">Kiểm tra hàng rồi thanh toán</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <svg class="h-8 w-8 flex-shrink-0 text-[#e8c4c4]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <div>
                    <p class="text-sm font-semibold">Tư vấn 24/7</p>
                    <p class="text-xs text-white/70">Trợ lý AI &amp; hotline 077123456</p>
                </div>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Categories --}}
        @if($categories->count())
            <div class="text-center mb-6">
                <h2 class="font-serif text-2xl font-semibold text-[#3d3d3d]">Danh mục nổi bật</h2>
                <p class="text-[#9a9490] text-sm mt-1">Chọn phong cách của bạn</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-12">
                @foreach($categories->take(8) as $category)
                    <a href="{{ route('categories.show', $category) }}"
                       class="block text-center bg-white border border-[#efe8e3] rounded-xl py-3.5 px-4 text-[#3d3d3d] font-medium text-sm hover:bg-[#e8c4c4] hover:border-[#e8c4c4] hover:text-[#3d3d3d] transition-colors">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Gợi ý sản phẩm: đang hot / bán chạy / xem nhiều / đánh giá cao --}}
        @if($recommendTabs->isNotEmpty())
            <div x-data="{ tab: @js($recommendTabs->keys()->first()) }" data-testid="recommend-tabs">
                <div class="flex flex-wrap justify-between items-end gap-3 mb-6">
                    <div>
                        <h2 class="font-serif text-2xl font-semibold text-[#3d3d3d]">Gợi ý cho bạn</h2>
                        @foreach($recommendTabs as $key => [$label, $hint, $items])
                            <p class="text-[#9a9490] text-sm mt-0.5" x-show="tab === '{{ $key }}'" @if(! $loop->first) x-cloak @endif>{{ $hint }}</p>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($recommendTabs as $key => [$label, $hint, $items])
                            <button type="button" @click="tab = '{{ $key }}'"
                                    class="px-4 py-1.5 rounded-full text-sm font-medium border transition-colors"
                                    :class="tab === '{{ $key }}' ? 'bg-[#b8847e] border-[#b8847e] text-white' : 'border-[#efe8e3] text-[#3d3d3d] hover:border-[#c9a9a6]'">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
                @foreach($recommendTabs as $key => [$label, $hint, $items])
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" x-show="tab === '{{ $key }}'" @if(! $loop->first) x-cloak @endif>
                        @foreach($items as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                @endforeach
                <div class="text-center mt-6">
                    <a href="{{ route('products.index', ['sort' => 'popular']) }}" class="text-[#b8847e] text-sm font-semibold hover:text-[#a6736d] transition-colors">Xem tất cả sản phẩm phổ biến →</a>
                </div>
            </div>
        @endif
    </div>

    {{-- Referral Banner --}}
    @auth
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
            <div class="bg-[#f5f0ec] border border-[#efe8e3] rounded-2xl p-8 md:p-10 flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="font-serif text-2xl font-semibold text-[#3d3d3d] mb-2">Giới thiệu bạn bè, nhận thưởng</h3>
                    <p class="text-[#6b6560] text-sm max-w-lg">Chia sẻ mã giới thiệu của bạn và nhận ngay ưu đãi khi bạn bè mua hàng thành công!</p>
                </div>
                <a href="{{ route('profile') }}"
                   class="bg-[#b8847e] text-white px-6 py-3 rounded-lg font-medium text-sm hover:bg-[#a6736d] transition-colors whitespace-nowrap flex-shrink-0">
                    Lấy mã giới thiệu
                </a>
            </div>
        </div>
    @endauth

    {{-- New Products --}}
    @if($newProducts->count())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="font-serif text-2xl font-semibold text-[#3d3d3d]">Sản phẩm mới nhất</h2>
                    <p class="text-[#9a9490] text-sm mt-0.5">Cập nhật xu hướng thời trang</p>
                </div>
                <a href="{{ route('products.index') }}" class="text-[#b8847e] text-sm font-semibold hover:text-[#a6736d] transition-colors">
                    Xem tất cả →
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($newProducts as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </div>
    @endif
</x-layouts.app>
