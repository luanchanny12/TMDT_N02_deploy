<x-layouts.app :title="'Bộ sưu tập yêu thích của ' . $owner->name">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs text-[#9a9490] mb-6">
            <a href="{{ route('home') }}" class="hover:text-[#b8847e] transition-colors">Trang chủ</a>
            <span>/</span>
            <span class="text-[#2c2a28] font-medium">Bộ sưu tập chia sẻ</span>
        </nav>

        {{-- Shared Wishlist Hero / Header Card --}}
        <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 mb-8 shadow-sm relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-[#faf7f4] rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                <div class="flex items-start sm:items-center gap-4">
                    {{-- Owner Avatar / Initials --}}
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-[#b8847e] to-[#a6736d] text-white flex items-center justify-center font-serif text-2xl sm:text-3xl font-bold shadow-md shadow-[#b8847e]/20 flex-shrink-0">
                        {{ mb_strtoupper(mb_substr($owner->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#faf7f4] border border-[#efe8e3] text-[#b8847e] text-xs font-semibold mb-2">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                            </svg>
                            <span>Bộ sưu tập thời trang yêu thích</span>
                        </div>
                        <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold text-[#2c2a28]">
                            {{ $owner->name }}
                        </h1>
                        <p class="text-xs sm:text-sm text-[#7d7571] mt-1">
                            Tuyển chọn các thiết kế ưng ý nhất tại SocialShop ({{ $products->total() ?? count($products) }} sản phẩm)
                        </p>
                    </div>
                </div>

                {{-- Action & Re-share Column --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    @auth
                        @if(Auth::id() !== $owner->id && $products->count())
                            <form action="{{ route('wishlist.shared.save-all', $code) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full sm:w-auto px-5 py-2.5 bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white rounded-xl text-xs sm:text-sm font-semibold hover:shadow-md hover:shadow-[#b8847e]/20 transition-all flex items-center justify-center gap-1.5 btn-shine">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>Lưu toàn bộ vào yêu thích của tôi</span>
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('register', ['ref' => $code]) }}"
                           class="w-full sm:w-auto px-5 py-2.5 bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white rounded-xl text-xs sm:text-sm font-semibold hover:shadow-md hover:shadow-[#b8847e]/20 transition-all flex items-center justify-center gap-1.5 btn-shine">
                            <span>🎁 Đăng ký nhận quà từ {{ $owner->name }}</span>
                        </a>
                    @endauth

                    <button type="button" onclick="copySharedWishlistLink()" id="btn-copy-shared"
                            class="w-full sm:w-auto px-4 py-2.5 bg-[#faf7f4] border border-[#efe8e3] text-[#2c2a28] hover:text-[#b8847e] hover:border-[#b8847e] rounded-xl text-xs sm:text-sm font-semibold transition-all flex items-center justify-center gap-1.5 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span>Sao chép link</span>
                    </button>
                </div>
            </div>

            {{-- Social Share Row --}}
            <div class="mt-6 pt-5 border-t border-[#efe8e3] flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 text-[#7d7571]">
                    <span class="font-medium">Chia sẻ bộ sưu tập này:</span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="shareSocial('facebook')" class="px-2.5 py-1 rounded-lg bg-[#faf7f4] hover:bg-[#b8847e] hover:text-white border border-[#efe8e3] transition-colors">Facebook</button>
                        <button type="button" onclick="shareSocial('messenger')" class="px-2.5 py-1 rounded-lg bg-[#faf7f4] hover:bg-[#b8847e] hover:text-white border border-[#efe8e3] transition-colors">Messenger</button>
                        <button type="button" onclick="shareSocial('zalo')" class="px-2.5 py-1 rounded-lg bg-[#faf7f4] hover:bg-[#b8847e] hover:text-white border border-[#efe8e3] transition-colors">Zalo</button>
                        <button type="button" onclick="shareSocial('x')" class="px-2.5 py-1 rounded-lg bg-[#faf7f4] hover:bg-[#b8847e] hover:text-white border border-[#efe8e3] transition-colors">X (Twitter)</button>
                        <button type="button" onclick="shareSocial('telegram')" class="px-2.5 py-1 rounded-lg bg-[#faf7f4] hover:bg-[#b8847e] hover:text-white border border-[#efe8e3] transition-colors">Telegram</button>
                    </div>
                </div>

                <div class="text-[11px] text-[#9a9490] flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Mọi đơn hàng đặt mua qua link này đều được áp dụng chính sách bảo đảm của SocialShop</span>
                </div>
            </div>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Products Grid --}}
        @if($products->count())
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
            <div class="bg-white border border-[#efe8e3] rounded-3xl p-10 sm:p-14 text-center max-w-xl mx-auto shadow-sm">
                <div class="w-20 h-20 mx-auto mb-4 bg-[#faf7f4] border border-[#efe8e3] rounded-full flex items-center justify-center text-[#b8847e]">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <h2 class="font-serif text-2xl font-bold text-[#2c2a28] mb-2">Bộ sưu tập hiện đang trống</h2>
                <p class="text-xs sm:text-sm text-[#9a9490] mb-6">
                    {{ $owner->name }} chưa lưu sản phẩm nào vào danh sách này. Hãy khám phá các thiết kế mới nhất của SocialShop ngay!
                </p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-2 bg-[#b8847e] hover:bg-[#a6736d] text-white px-6 py-3 rounded-xl font-bold text-xs shadow-sm transition-all">
                    Xem sản phẩm hot
                </a>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        const sharedWishlistUrl = window.location.href;
        const sharedTitle = 'Bộ sưu tập yêu thích của {{ addslashes($owner->name) }} - SocialShop';

        function copySharedWishlistLink() {
            const btn = document.getElementById('btn-copy-shared');
            const done = () => {
                btn.innerHTML = '<span>Đã chép link ✓</span>';
                setTimeout(() => {
                    btn.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span>Sao chép link</span>
                    `;
                }, 2000);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(sharedWishlistUrl).then(done);
            } else {
                const tmp = document.createElement('textarea');
                tmp.value = sharedWishlistUrl;
                document.body.appendChild(tmp);
                tmp.select();
                document.execCommand('copy');
                tmp.remove();
                done();
            }
        }

        function shareSocial(network) {
            const u = encodeURIComponent(sharedWishlistUrl);
            const t = encodeURIComponent(sharedTitle);
            const targets = {
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${u}`,
                messenger: `https://www.messenger.com/share?link=${u}`,
                zalo: `https://zalo.me/share/?url=${u}`,
                x: `https://twitter.com/intent/tweet?url=${u}&text=${t}`,
                telegram: `https://t.me/share/url?url=${u}&text=${t}`
            };
            if (targets[network]) {
                window.open(targets[network], '_blank', 'noopener,width=640,height=560');
            }
        }
    </script>
    @endpush
</x-layouts.app>
