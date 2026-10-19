<x-layouts.app>
    {{-- Hero Section (Editorial Boutique Lookbook) --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-[#faf7f4] via-[#f7f0ea] to-[#faf7f4] py-10 md:py-16 border-b border-[#efe8e3]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                {{-- Hero Copy --}}
                <div class="lg:col-span-7 space-y-5 text-center lg:text-left z-10 animate-fade-in-up">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-[#efe8e3] text-xs font-semibold text-[#b8847e] shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-[#b8847e] animate-ping"></span>
                        <span class="uppercase tracking-wider text-[11px]">BỘ SƯU TẬP MỚI • XUÂN HÈ 2026</span>
                    </div>

                    <h1 class="font-serif text-4xl sm:text-5xl lg:text-[3.5rem] font-bold tracking-tight text-[#2c2a28] leading-[1.15]">
                        Vẻ Đẹp Thanh Lịch<br>
                        <span class="italic font-normal text-[#b8847e]">Dịu Nhẹ</span> Từng Đường Nét
                    </h1>

                    <p class="text-sm sm:text-base text-[#6b6560] max-w-xl mx-auto lg:mx-0 leading-relaxed">
                        Khám phá tủ đồ mùa mới với các chất liệu tự nhiên, phom dáng chuẩn mực tôn vinh cá tính thời trang hiện đại cùng ưu đãi ra mắt lên đến <strong>40%</strong>.
                    </p>

                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3.5 pt-2">
                        <a href="{{ route('products.index') }}"
                           class="px-7 py-3.5 rounded-full bg-[#b8847e] hover:bg-[#a6736d] text-white text-sm font-semibold tracking-wide transition-all shadow-lg shadow-[#b8847e]/25 flex items-center gap-2 group">
                            <span>Khám phá bộ sưu tập</span>
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="{{ route('products.index', ['sort' => 'price_desc']) }}"
                           class="px-7 py-3.5 rounded-full bg-white hover:bg-[#faf7f4] border border-[#efe8e3] text-[#2c2a28] text-sm font-semibold tracking-wide transition-all shadow-sm">
                            Sản phẩm nổi bật
                        </a>
                    </div>

                    {{-- Social Proof Strip --}}
                    <div class="pt-6 border-t border-[#efe8e3]/80 flex items-center justify-center lg:justify-start gap-6">
                        <div class="flex -space-x-2">
                            <img class="w-8 h-8 rounded-full border-2 border-white object-cover" src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=80&h=80&fit=crop" alt="Khách hàng 1">
                            <img class="w-8 h-8 rounded-full border-2 border-white object-cover" src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&h=80&fit=crop" alt="Khách hàng 2">
                            <img class="w-8 h-8 rounded-full border-2 border-white object-cover" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=80&h=80&fit=crop" alt="Khách hàng 3">
                            <div class="w-8 h-8 rounded-full border-2 border-white bg-[#b8847e] text-white text-[11px] font-bold flex items-center justify-center">+5K</div>
                        </div>
                        <div class="text-left text-xs">
                            <div class="flex text-amber-400 text-xs">★★★★★</div>
                            <span class="text-[#6b6560]">Hơn <strong>5.000+</strong> quý cô tin chọn</span>
                        </div>
                    </div>
                </div>

                {{-- Hero Visual Lookbook --}}
                <div class="lg:col-span-5 relative">
                    <div class="relative mx-auto max-w-sm sm:max-w-md lg:max-w-none">
                        <div class="absolute -inset-4 bg-gradient-to-tr from-[#e8c4c4]/60 to-[#c9a9a6]/30 rounded-3xl blur-2xl -z-10 transform -rotate-3"></div>

                        <div class="relative rounded-2xl overflow-hidden shadow-2xl border-4 border-white aspect-[3/4] bg-[#f5f0ec]">
                            <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&h=1060&fit=crop&q=85"
                                 alt="Lookbook thời trang SocialShop"
                                 class="w-full h-full object-cover">

                            {{-- Floating Lookbook Pill --}}
                            <div class="absolute bottom-5 left-5 right-5 p-3.5 sm:p-4 rounded-xl bg-white/95 backdrop-blur-md border border-white/40 shadow-lg flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] uppercase tracking-wider text-[#b8847e] font-bold">Lookbook Độc Quyền</p>
                                    <p class="text-xs sm:text-sm font-serif font-bold text-[#2c2a28]">Váy Lụa Satin Cao Cấp</p>
                                    <p class="text-[11px] text-[#6b6560]">Ưu đãi ra mắt trong hôm nay</p>
                                </div>
                                <a href="{{ route('products.index') }}" class="text-xs font-bold bg-[#b8847e] text-white px-3 py-1.5 rounded-full hover:bg-[#a6736d] transition-colors">
                                    Xem ngay
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Ưu đãi mua sắm: số liệu đọc từ Admin → Cài đặt nên luôn khớp chính sách thật --}}
    @php
        $pricing = app(\App\Services\Order\PricingService::class);
        $freeFrom = $pricing->nationwideFreeShippingThreshold();
    @endphp
    {{-- Trust & Value Strip (4 Cam kết) --}}
    <section class="border-b border-[#efe8e3] bg-white py-6" data-testid="shop-benefits">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center flex-shrink-0 text-[#b8847e]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#2c2a28] uppercase tracking-wider">Freeship toàn quốc</h4>
                        <p class="text-[11px] text-[#9a9490]">Cho đơn từ {{ number_format($freeFrom, 0, ',', '.') }}₫</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center flex-shrink-0 text-[#b8847e]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#2c2a28] uppercase tracking-wider">Đổi trả 7 ngày</h4>
                        <p class="text-[11px] text-[#9a9490]">Đổi size & mẫu tận nơi tiện lợi</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center flex-shrink-0 text-[#b8847e]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#2c2a28] uppercase tracking-wider">Thanh toán an toàn</h4>
                        <p class="text-[11px] text-[#9a9490]">VNPay Sandbox & COD tiền mặt</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center flex-shrink-0 text-[#b8847e]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#2c2a28] uppercase tracking-wider">Trợ lý AI 24/7</h4>
                        <p class="text-[11px] text-[#9a9490]">Tư vấn size & phối đồ tức thì</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">

        {{-- Categories Showcase (Visual Circular Cards) --}}
        @if($categories->count())
            <div class="text-center mb-8">
                <p class="text-[11px] uppercase tracking-[3px] text-[#b8847e] font-semibold mb-1">DANH MỤC NỔI BẬT</p>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">Khám Phá Phong Cách Của Bạn</h2>
                <div class="w-12 h-0.5 bg-[#b8847e] mx-auto mt-2.5"></div>
            </div>

            @php
                $categoryImages = [
                    'thoi-trang-nam' => asset('category-images/thoi-trang-nam.jpg'),
                    'thoi-trang-nu' => asset('category-images/thoi-trang-nu.jpg'),
                    'giay-dep' => asset('category-images/giay-dep.jpg'),
                    'tui-xach' => asset('category-images/tui-xach.jpg'),
                    'tui-vi' => asset('category-images/tui-xach.jpg'),
                    'dong-ho' => asset('category-images/dong-ho.jpg'),
                    'my-pham' => asset('category-images/my-pham.jpg'),
                    'my-pham-nuoc-hoa' => asset('category-images/my-pham.jpg'),
                ];
                $topCategories = $categories->whereNull('parent_id')->take(6);
                if($topCategories->isEmpty()) {
                    $topCategories = $categories->take(6);
                }
            @endphp

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4 mb-14">
                @foreach($topCategories as $category)
                    @php
                        $img = $category->image_url ?? ($categoryImages[$category->slug] ?? asset('category-images/thoi-trang-nam.jpg'));
                    @endphp
                    <a href="{{ route('categories.show', $category) }}"
                       class="group flex flex-col items-center p-3.5 rounded-2xl bg-white border border-[#efe8e3] hover-lift text-center transition-all">
                        <div class="w-20 h-20 sm:w-22 sm:h-22 rounded-full overflow-hidden p-1 bg-[#faf7f4] border-2 border-[#efe8e3] group-hover:border-[#b8847e] transition-colors mb-2.5">
                            <img src="{{ $img }}"
                                 alt="{{ $category->name }}"
                                 class="w-full h-full object-cover rounded-full group-hover:scale-110 transition-transform duration-300">
                        </div>
                        <span class="text-xs sm:text-sm font-bold text-[#2c2a28] group-hover:text-[#b8847e] transition-colors line-clamp-1">
                            {{ $category->name }}
                        </span>
                        <span class="text-[11px] text-[#9a9490] mt-0.5">
                            {{ $category->total_products_count ?? $category->products_count }} sản phẩm
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Flash Sale Deals with Live Countdown Clock --}}
        @if(!empty($flashSaleProducts) && $flashSaleProducts->count())
            <div class="mb-14 rounded-3xl bg-gradient-to-r from-[#fff5f5] via-[#fff0f3] to-[#faf7f4] border border-red-100 p-5 sm:p-8"
                 x-data="{
                     endsAt: {{ $flashSaleEndsAt?->getTimestampMs() ?? 0 }},
                     hours: '--', minutes: '--', seconds: '--', ended: false,
                     init() {
                         // Đếm tới mốc kết thúc thật do admin đặt — F5, tab ẩn danh hay trình duyệt khác đều thấy cùng số
                         const tick = () => {
                             const diff = Math.max(0, Math.floor((this.endsAt - Date.now()) / 1000));
                             this.hours = String(Math.floor(diff / 3600)).padStart(2, '0');
                             this.minutes = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
                             this.seconds = String(diff % 60).padStart(2, '0');
                             if (diff === 0 && !this.ended) {
                                 this.ended = true; // hết giờ thì giá về giá gốc → tải lại để cập nhật
                                 setTimeout(() => window.location.reload(), 3000);
                             }
                         };
                         tick();
                         setInterval(tick, 1000);
                     }
                 }">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 bg-red-600 text-white font-extrabold text-xs px-3.5 py-1.5 rounded-full tracking-wider uppercase shadow-md shadow-red-200">
                            <span>🔥 FLASH SALE GIỜ VÀNG</span>
                        </span>
                        <div class="flex items-center gap-1.5 text-xs font-bold text-[#2c2a28]">
                            <span class="text-[#9a9490] font-normal mr-1" x-text="ended ? 'Ưu đãi đã kết thúc' : 'Kết thúc sau:'">Kết thúc sau:</span>
                            <span x-show="!ended" class="bg-[#2c2a28] text-white px-2 py-1 rounded font-mono" x-text="hours" x-cloak></span><span x-show="!ended" x-cloak>:</span>
                            <span x-show="!ended" class="bg-[#2c2a28] text-white px-2 py-1 rounded font-mono" x-text="minutes" x-cloak></span><span x-show="!ended" x-cloak>:</span>
                            <span x-show="!ended" class="bg-[#2c2a28] text-white px-2 py-1 rounded font-mono" x-text="seconds" x-cloak></span>
                        </div>
                        <span class="text-[11px] text-[#9a9490]" data-testid="flash-sale-deadline">
                            Giá ưu đãi áp dụng đến {{ $flashSaleEndsAt?->timezone(config('app.timezone'))->format('H:i d/m/Y') }}, sau đó trở về giá gốc
                        </span>
                    </div>
                    <a href="{{ route('products.index', ['sort' => 'price_asc']) }}" class="text-xs font-bold text-[#b8847e] hover:text-[#a6736d] flex items-center gap-1 group">
                        <span>Xem tất cả ưu đãi</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($flashSaleProducts as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
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

        {{-- Referral VIP Club Banner --}}
        @auth
            <div class="mb-14">
                <div class="bg-gradient-to-r from-[#f5f0ec] via-[#faf7f4] to-[#f5f0ec] border border-[#efe8e3] rounded-3xl p-6 sm:p-10 flex flex-col md:flex-row items-center justify-between gap-6 shadow-sm">
                    <div class="space-y-1.5 text-center md:text-left">
                        <span class="inline-block px-3 py-1 bg-white border border-[#efe8e3] text-[#b8847e] text-[10px] font-bold rounded-full uppercase tracking-wider">CHƯƠNG TRÌNH ĐẶC QUYỀN</span>
                        <h3 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">Giới thiệu bạn bè, nhận thưởng ngay</h3>
                        <p class="text-[#6b6560] text-xs sm:text-sm max-w-lg">Chia sẻ mã giới thiệu của bạn và nhận ngay voucher ưu đãi hấp dẫn khi bạn bè mua sắm thành công!</p>
                    </div>
                    <a href="{{ route('profile') }}"
                       class="bg-[#b8847e] text-white px-7 py-3 rounded-full font-semibold text-xs sm:text-sm hover:bg-[#a6736d] transition-all shadow-md shadow-[#b8847e]/20 whitespace-nowrap flex-shrink-0">
                        Lấy mã giới thiệu ngay
                    </a>
                </div>
            </div>
        @endauth

        {{-- New Products (Sản phẩm mới nhất) --}}
        @if($newProducts->count())
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">Sản phẩm mới nhất</h2>
                    <p class="text-[#9a9490] text-xs sm:text-sm mt-0.5">Đón đầu xu hướng thời trang cùng SocialShop</p>
                </div>
                <a href="{{ route('products.index', ['sort' => 'latest']) }}" class="text-[#b8847e] text-xs sm:text-sm font-semibold hover:text-[#a6736d] transition-colors flex items-center gap-1">
                    <span>Xem tất cả</span>
                    <span>→</span>
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($newProducts as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>

