<x-layouts.app :title="'Sản phẩm yêu thích'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10" x-data="{ openShareModal: false }">

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
                <div class="flex items-center gap-2.5">
                    <button type="button" @click="openShareModal = true"
                            class="text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-[#b8847e] to-[#a6736d] hover:shadow-md hover:shadow-[#b8847e]/25 flex items-center gap-1.5 transition-all px-4 py-2 rounded-xl shadow-xs btn-shine">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12s-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        <span>🎁 Chia sẻ danh sách này</span>
                    </button>

                    <a href="{{ route('products.index') }}"
                       class="text-xs sm:text-sm font-semibold text-[#b8847e] hover:text-[#a6736d] flex items-center gap-1.5 transition-colors bg-[#faf7f4] border border-[#efe8e3] px-4 py-2 rounded-xl shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span>Khám phá thêm</span>
                    </a>
                </div>
            @endif
        </div>

        {{-- Share Wishlist Modal --}}
        @php
            $currentRefCode = $referralCode ?? Auth::user()->referral_code;
            $shareUrl = route('wishlist.shared', ['code' => $currentRefCode]);
        @endphp
        <div x-show="openShareModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity"
             @keydown.escape.window="openShareModal = false">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-[#efe8e3] relative animate-fade-in-up"
                 @click.outside="openShareModal = false">
                <button type="button" @click="openShareModal = false"
                        class="absolute top-5 right-5 text-[#9a9490] hover:text-[#2c2a28] text-xl font-bold transition-colors">
                    &times;
                </button>

                <div class="flex items-center gap-3 mb-4">
                    <span class="w-10 h-10 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12s-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    </span>
                    <div>
                        <h3 class="font-serif text-xl sm:text-2xl font-bold text-[#2c2a28]">Chia sẻ bộ sưu tập yêu thích</h3>
                        <p class="text-xs text-[#9a9490]">Gửi gu thời trang của bạn đến bạn bè trên mạng xã hội</p>
                    </div>
                </div>

                <div class="bg-[#faf7f4] border border-[#efe8e3] p-3.5 rounded-2xl mb-5 text-xs text-[#7d7571] flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-[#b8847e] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Link chia sẻ đã được tự động gắn mã giới thiệu của bạn. Khi bạn bè xem bộ sưu tập và mua sắm, bạn sẽ nhận <strong>5% hoa hồng</strong> vào tài khoản!</span>
                </div>

                {{-- Copy Link Box --}}
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-[#2c2a28] mb-1.5 uppercase tracking-wider">Đường link công khai của bạn:</label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly id="my-wishlist-share-link" value="{{ $shareUrl }}"
                               class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-700 truncate focus:outline-none">
                        <button type="button" onclick="copyMyWishlistLink()" id="btn-copy-my-wishlist"
                                class="px-4 py-2.5 bg-[#b8847e] hover:bg-[#a6736d] text-white text-xs font-semibold rounded-xl transition-colors shadow-xs flex items-center gap-1">
                            <span>Sao chép</span>
                        </button>
                    </div>
                </div>

                {{-- Social Share Buttons --}}
                <div>
                    <label class="block text-xs font-semibold text-[#2c2a28] mb-2 uppercase tracking-wider">Chia sẻ 1 chạm lên mạng xã hội:</label>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 text-center text-xs">
                        <button type="button" onclick="shareWishlistTo('facebook')" class="p-2.5 rounded-xl border border-[#efe8e3] bg-[#faf7f4] hover:bg-[#b8847e] hover:text-white font-medium transition-colors">Facebook</button>
                        <button type="button" onclick="shareWishlistTo('messenger')" class="p-2.5 rounded-xl border border-[#efe8e3] bg-[#faf7f4] hover:bg-[#b8847e] hover:text-white font-medium transition-colors">Messenger</button>
                        <button type="button" onclick="shareWishlistTo('zalo')" class="p-2.5 rounded-xl border border-[#efe8e3] bg-[#faf7f4] hover:bg-[#b8847e] hover:text-white font-medium transition-colors">Zalo</button>
                        <button type="button" onclick="shareWishlistTo('x')" class="p-2.5 rounded-xl border border-[#efe8e3] bg-[#faf7f4] hover:bg-[#b8847e] hover:text-white font-medium transition-colors">X (Twitter)</button>
                        <button type="button" onclick="shareWishlistTo('telegram')" class="p-2.5 rounded-xl border border-[#efe8e3] bg-[#faf7f4] hover:bg-[#b8847e] hover:text-white font-medium transition-colors">Telegram</button>
                    </div>
                </div>
            </div>
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

    @push('scripts')
    <script>
        const myWishlistUrl = "{{ $shareUrl }}";
        const myWishlistTitle = "Khám phá bộ sưu tập thời trang yêu thích của tôi trên SocialShop!";

        function copyMyWishlistLink() {
            const btn = document.getElementById('btn-copy-my-wishlist');
            const done = () => {
                btn.innerHTML = '<span>Đã chép ✓</span>';
                setTimeout(() => {
                    btn.innerHTML = '<span>Sao chép</span>';
                }, 2000);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(myWishlistUrl).then(done);
            } else {
                const tmp = document.createElement('textarea');
                tmp.value = myWishlistUrl;
                document.body.appendChild(tmp);
                tmp.select();
                document.execCommand('copy');
                tmp.remove();
                done();
            }
        }

        function shareWishlistTo(network) {
            const u = encodeURIComponent(myWishlistUrl);
            const t = encodeURIComponent(myWishlistTitle);
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