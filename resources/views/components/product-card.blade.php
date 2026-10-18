@props(['product', 'wishlistIds' => []])

@php
    $discountPercent = 0;
    if(isset($product->sale_price) && $product->sale_price && $product->sale_price < $product->price) {
        $discountPercent = round((1 - $product->sale_price / $product->price) * 100);
    }
    $displayPrice = $discountPercent > 0 ? $product->sale_price : $product->price;
    $soldCount = (int) ($product->sold_count ?? 0);
    $totalStock = $product->stock + $soldCount;
    $soldPercent = $totalStock > 0 ? round(($soldCount / $totalStock) * 100) : 0;
    $hasRating = (int) ($product->reviews_count ?? 0) > 0;
    $rating = $hasRating ? (float) $product->average_rating : 0;
    $isWishlisted = in_array($product->id, $wishlistIds);
@endphp

<div class="flex flex-col h-full group bg-white rounded-xl shadow-sm border border-[#efe8e3] overflow-hidden hover-lift relative animate-fade-in-up"
     x-data="{ cartLoading: false, wishlistLoading: false, compareLoading: false, wishlisted: {{ $isWishlisted ? 'true' : 'false' }} }">

    {{-- Discount Badge --}}
    @if($discountPercent > 0)
        <div class="absolute top-2 left-2 z-10 badge-discount">-{{ $discountPercent }}%</div>
    @endif

    {{-- Image — fixed 3:4 ratio --}}
    <a href="{{ route('products.show', $product->slug ?? $product->id) }}" class="block relative hover-zoom aspect-[3/4]">
        @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                 class="w-full h-full object-cover">
        @else
            <div class="w-full h-full bg-gradient-to-br from-[#f5f0ec] to-[#efe8e3] flex items-center justify-center">
                <svg class="w-14 h-14 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        {{-- Compare Button --}}
        <button type="button"
                title="So sánh"
                @click="
                    compareLoading = true;
                    fetch('{{ route('compare.add', $product) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json().then(data => ({ ok: r.ok, data })))
                    .then(({ ok, data }) => {
                        compareLoading = false;
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                message: data.message || (ok ? 'Đã thêm vào danh sách so sánh!' : 'Không thể thêm vào so sánh.'),
                                type: ok ? 'success' : 'error'
                            }
                        }));
                        if (ok) window.dispatchEvent(new CustomEvent('compare-updated', { detail: data.compare_count }));
                    })
                    .catch(() => {
                        compareLoading = false;
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: 'Có lỗi xảy ra, thử lại sau.', type: 'error' }
                        }));
                    })
                "
                :disabled="compareLoading"
                class="absolute bottom-3 left-3 z-10 w-9 h-9 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center shadow-md hover:bg-white transition-all disabled:opacity-60">
            <svg x-show="!compareLoading" class="w-5 h-5 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            <svg x-show="compareLoading" x-cloak class="w-5 h-5 animate-spin text-[#9a9490]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </button>

        {{-- Wishlist Heart Button --}}
        @auth
            <button type="button"
                    title="Yêu thích"
                    @click="
                        wishlistLoading = true;
                        fetch('{{ route('wishlist.toggle', $product) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            wishlistLoading = false;
                            if (data.success) {
                                wishlisted = data.is_wishlisted;
                                window.dispatchEvent(new CustomEvent('toast', {
                                    detail: { message: data.message, type: 'success' }
                                }));
                            }
                        })
                        .catch(() => {
                            wishlistLoading = false;
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: { message: 'Có lỗi xảy ra, thử lại sau.', type: 'error' }
                            }));
                        })
                    "
                    :disabled="wishlistLoading"
                    class="absolute bottom-3 right-3 z-10 w-9 h-9 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center shadow-md hover:bg-white transition-all disabled:opacity-60">
                <svg x-show="!wishlisted" class="w-5 h-5 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <svg x-show="wishlisted" x-cloak class="w-5 h-5 text-[#b8847e]" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </button>
        @endauth
    </a>

    {{-- Content --}}
    <div class="p-3 flex flex-col flex-1">
        {{-- Category --}}
        @if($product->category ?? false)
            <span class="inline-block text-[11px] font-medium text-[#b8847e] bg-[#faf7f4] px-2 py-0.5 rounded-full mb-1.5">
                {{ $product->category->name }}
            </span>
        @endif

        {{-- Name --}}
        <a href="{{ route('products.show', $product->slug ?? $product->id) }}">
            <h3 class="text-sm font-medium text-[#3d3d3d] line-clamp-2 mb-1.5 min-h-[40px] group-hover:text-[#b8847e] transition-colors">
                {{ $product->name }}
            </h3>
        </a>

        {{-- Price + Badge --}}
        <div class="flex flex-wrap items-end gap-2 mb-1 min-h-[28px]">
            <span class="text-lg font-extrabold text-[#b8847e] leading-tight">
                {{ number_format($displayPrice, 0, ',', '.') }}₫
            </span>
            @if($discountPercent > 0)
                <span class="text-xs text-gray-400 line-through mb-[2px]">{{ number_format($product->price, 0, ',', '.') }}₫</span>
                <span class="inline-flex items-center bg-[#fdf2f0] text-[#c0392b] text-[10px] font-bold px-1.5 py-0.5 rounded mb-[2px]">
                    -{{ $discountPercent }}%
                </span>
            @endif
        </div>

        {{-- Sold Progress Bar --}}
        @if($soldCount > 0)
            <div class="sold-progress mb-1.5">
                <div class="sold-progress-bar" style="width: {{ $soldPercent }}%"></div>
                <span class="sold-progress-text">Đã bán {{ $soldCount > 999 ? '1K+' : $soldCount }}</span>
            </div>
        @endif

        {{-- Rating --}}
        @if($hasRating)
            <div class="flex items-center gap-1 text-xs text-gray-500 mb-2">
                <div class="star-rating text-[11px]">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($rating))
                            ★
                        @elseif($i - $rating < 1)
                            ★
                        @else
                            ☆
                        @endif
                    @endfor
                </div>
                <span>{{ number_format($rating, 1) }} ({{ $product->reviews_count }})</span>
            </div>
        @else
            <div class="flex items-center gap-1 text-xs text-gray-400 mb-2">
                <span>Chưa có đánh giá</span>
            </div>
        @endif

        {{-- Add to Cart Button (AJAX) — sản phẩm có size/màu thì chọn ở trang chi tiết --}}
        @if($product->stock > 0 && ($product->variants_count ?? 0) > 0)
            <div class="card-action mt-auto pt-2">
                <a href="{{ route('products.show', $product->slug) }}"
                   class="w-full bg-[#3d3a37] text-white text-sm font-semibold py-2 rounded-lg hover:bg-[#b8847e] transition-all flex items-center justify-center gap-2">
                    Chọn size/màu
                </a>
            </div>
        @elseif($product->stock > 0)
            <div class="card-action mt-auto pt-2">
                <button type="button"
                        @click="
                            cartLoading = true;
                            fetch('{{ route('cart.add') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    product_id: {{ $product->id }},
                                    quantity: 1
                                })
                            })
                            .then(r => r.json())
                            .then(data => {
                                cartLoading = false;
                                if (data.success) {
                                    Alpine.store('cart').count = data.cartCount;
                                    window.dispatchEvent(new CustomEvent('toast', {
                                        detail: { message: 'Đã thêm vào giỏ hàng!', type: 'success' }
                                    }));
                                } else {
                                    window.dispatchEvent(new CustomEvent('toast', {
                                        detail: { message: data.message || 'Không thể thêm vào giỏ hàng.', type: 'error' }
                                    }));
                                }
                            })
                            .catch((err) => {
                                console.error(err);
                                cartLoading = false;
                                window.dispatchEvent(new CustomEvent('toast', {
                                    detail: { message: 'Có lỗi xảy ra, thử lại sau.', type: 'error' }
                                }));
                            })
                        "
                        :disabled="cartLoading"
                        class="w-full bg-[#b8847e] text-white text-sm font-semibold py-2 rounded-lg hover:bg-[#a6736d] transition-all btn-shine flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg x-show="!cartLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                    <svg x-show="cartLoading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="cartLoading ? 'Đang thêm...' : 'Thêm vào giỏ'"></span>
                </button>
            </div>
        @endif
    </div>
</div>
