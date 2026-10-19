<x-layouts.app
    :title="$product->name"
    :meta-title="$metaTitle"
    :meta-description="$metaDescription"
    :meta-image="$metaImage"
    :json-ld="$jsonLd"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">

        {{-- Breadcrumb --}}
        <nav class="flex items-center flex-wrap gap-2 text-xs sm:text-sm text-[#9a9490] mb-6">
            <a href="{{ route('home') }}" class="hover:text-[#b8847e] transition-colors flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Trang chủ</span>
            </a>
            <span class="text-[#d5cec7]">/</span>
            <a href="{{ route('products.index') }}" class="hover:text-[#b8847e] transition-colors">Sản phẩm</a>
            @if($product->category ?? false)
                <span class="text-[#d5cec7]">/</span>
                <a href="{{ route('products.index', ['category' => $product->category->id]) }}" class="hover:text-[#b8847e] transition-colors">{{ $product->category->name }}</a>
            @endif
            <span class="text-[#d5cec7]">/</span>
            <span class="text-[#2c2a28] font-medium truncate max-w-[180px] sm:max-w-[280px]">{{ $product->name }}</span>
        </nav>

        {{-- Product Detail Main Card --}}
        <div class="bg-white border border-[#efe8e3] rounded-3xl overflow-hidden shadow-sm mb-12">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-0">

                {{-- Gallery Column (5 cols) --}}
                <div class="lg:col-span-5 bg-[#faf7f4] p-5 sm:p-8 border-b lg:border-b-0 lg:border-r border-[#efe8e3] flex flex-col justify-between">
                    @php
                        $galleryImages = $product->images->map(fn ($img) => [
                            'src' => $img->url,
                            'alt' => $product->name,
                        ]);

                        // Fallback: nếu không có ảnh nào trong product_images
                        if ($galleryImages->isEmpty() && $product->getRawOriginal('image_url')) {
                            $galleryImages = collect([['src' => $product->image_url, 'alt' => $product->name]]);
                        }

                        $firstImage = $galleryImages->first();

                        $discountPercent = 0;
                        if($product->isOnSale()) {
                            $discountPercent = round((1 - $product->sale_price / $product->price) * 100);
                        }
                        $displayPrice = $discountPercent > 0 ? $product->sale_price : $product->price;
                    @endphp

                    @if($firstImage)
                        <div x-data="{ current: {{ json_encode($firstImage['src']) }}, zoom: false }" class="space-y-4">

                            {{-- Ảnh chính với hover zoom và badge --}}
                            <div class="relative bg-white rounded-2xl overflow-hidden border border-[#efe8e3] aspect-square group shadow-inner">
                                <img :src="current"
                                     :alt="{{ json_encode($product->name) }}"
                                     class="w-full h-full object-cover transition-all duration-500 group-hover:scale-105">

                                {{-- Sale Badge --}}
                                @if($discountPercent > 0)
                                    <div class="absolute top-4 left-4 bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white text-xs font-bold px-3 py-1 rounded-full shadow-md">
                                        -{{ $discountPercent }}% GIẢM
                                    </div>
                                @endif

                                {{-- Wishlist Floating Button on Main Image --}}
                                @auth
                                    @php
                                        $isWishlisted = auth()->user()->wishlist ? auth()->user()->wishlist->items()->where('product_id', $product->id)->exists() : false;
                                    @endphp
                                    <button type="button"
                                            x-data="{ wishlisted: {{ $isWishlisted ? 'true' : 'false' }}, loading: false }"
                                            @click="
                                                loading = true;
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
                                                    loading = false;
                                                    if (data.success) {
                                                        wishlisted = data.is_wishlisted;
                                                        if (data.wishlist_count !== undefined && window.Alpine && window.Alpine.store('wishlist')) {
                                                            window.Alpine.store('wishlist').count = data.wishlist_count;
                                                        }
                                                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'success' } }));
                                                    }
                                                })
                                                .catch(() => { loading = false; })
                                            "
                                            :disabled="loading"
                                            class="absolute top-4 right-4 z-10 w-11 h-11 rounded-full bg-white/90 backdrop-blur-md flex items-center justify-center shadow-lg hover:bg-white transition-all text-[#9a9490] hover:text-[#b8847e] cursor-pointer"
                                            title="Yêu thích">
                                        <svg x-show="!wishlisted" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                        <svg x-show="wishlisted" x-cloak class="w-6 h-6 text-[#b8847e]" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                    </button>
                                @else
                                    <a href="{{ route('login') }}"
                                       @click.prevent="
                                           window.dispatchEvent(new CustomEvent('toast', {
                                               detail: { message: 'Vui lòng đăng nhập để lưu sản phẩm yêu thích nhé!', type: 'info' }
                                           }));
                                           setTimeout(() => { window.location.href = '{{ route('login') }}'; }, 800);
                                       "
                                       class="absolute top-4 right-4 z-10 w-11 h-11 rounded-full bg-white/90 backdrop-blur-md flex items-center justify-center shadow-lg hover:bg-white transition-all text-[#9a9490] hover:text-[#b8847e] cursor-pointer"
                                       title="Đăng nhập để thêm vào yêu thích">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                    </a>
                                @endauth

                                {{-- Authenticity Tag --}}
                                <div class="absolute bottom-3 left-3 bg-white/90 backdrop-blur-md px-2.5 py-1 rounded-lg text-[11px] font-semibold text-[#2c2a28] border border-[#efe8e3]/80 flex items-center gap-1.5 shadow-sm">
                                    <svg class="w-3.5 h-3.5 text-[#b8847e]" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Chính hãng 100%
                                </div>
                            </div>

                            {{-- Thumbnails --}}
                            @if($galleryImages->count() > 1)
                                <div class="flex gap-2.5 overflow-x-auto pb-1 scrollbar-hide pt-1">
                                    @foreach($galleryImages as $i => $img)
                                        <button type="button"
                                                @click="current = {{ json_encode($img['src']) }}"
                                                :class="current === {{ json_encode($img['src']) }}
                                                    ? 'border-2 border-[#b8847e] ring-2 ring-[#b8847e]/20 opacity-100 scale-102'
                                                    : 'border border-[#efe8e3] opacity-60 hover:opacity-100 hover:border-[#c9a9a6]'"
                                                class="flex-shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden bg-white transition-all duration-200">
                                            <img src="{{ $img['src'] }}"
                                                 alt="{{ $product->name }} - ảnh {{ $i + 1 }}"
                                                 class="w-full h-full object-cover">
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                        </div>
                    @else
                        {{-- Placeholder khi không có ảnh nào --}}
                        <div class="w-full aspect-square bg-white border border-[#efe8e3] rounded-2xl flex flex-col items-center justify-center text-[#c9a9a6] p-6 text-center">
                            <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs text-[#9a9490]">Hình ảnh đang cập nhật</span>
                        </div>
                    @endif

                    {{-- Small note under gallery --}}
                    <div class="mt-4 pt-4 border-t border-[#efe8e3]/70 flex items-center justify-between text-xs text-[#9a9490]">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Bảo hành chất lượng
                        </span>
                        <span>Mã SP: #SP{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </div>

                {{-- Product Info Column (7 cols) --}}
                <div class="lg:col-span-7 p-6 sm:p-8 lg:p-10 flex flex-col justify-between">
                    <div>
                        {{-- Category & Brand Row --}}
                        <div class="flex items-center gap-2 flex-wrap mb-3">
                            @if($product->category ?? false)
                                <a href="{{ route('products.index', ['category' => $product->category->id]) }}"
                                   class="text-[11px] uppercase tracking-wider font-semibold text-[#b8847e] bg-[#faf7f4] border border-[#efe8e3] px-3 py-1 rounded-full hover:bg-[#b8847e] hover:text-white transition-all">
                                    {{ $product->category->name }}
                                </a>
                            @endif
                            @if($product->brand ?? false)
                                <span class="text-[11px] uppercase tracking-wider font-medium text-[#9a9490] bg-[#f5f0ec] px-2.5 py-1 rounded-full">
                                    Thương hiệu: <strong class="text-[#3d3d3d]">{{ $product->brand }}</strong>
                                </span>
                            @endif
                        </div>

                        {{-- Title --}}
                        <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-semibold text-[#2c2a28] mb-3 leading-snug">
                            {{ $product->name }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-[#6b6560] mb-4" data-testid="product-stats">
                            @if($soldCount > 0)
                                <span>Đã bán <strong class="text-[#3d3d3d]">{{ number_format($soldCount, 0, ',', '.') }}</strong></span>
                            @endif
                            @if($product->showsViewCount())
                                <span class="inline-flex items-center gap-1" title="Số lượt xem trang sản phẩm" data-testid="view-count"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>{{ number_format($product->views_count, 0, ',', '.') }} lượt xem</span>
                            @endif
                        </div>

                        {{-- Rating & Sold Statistics --}}
                        <div class="flex items-center gap-3 sm:gap-4 flex-wrap pb-4 mb-5 border-b border-[#efe8e3]">
                            <a href="#reviews" class="flex items-center gap-1.5 text-sm group">
                                <div class="flex items-center text-[#e8a39e]">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($product->average_rating))
                                            <svg class="w-4 h-4 fill-current text-[#b8847e]" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @else
                                            <svg class="w-4 h-4 fill-current text-[#efe8e3]" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @endif
                                    @endfor
                                </div>
                                <span class="font-bold text-[#2c2a28]">{{ number_format($product->average_rating, 1) }}</span>
                                <span class="text-xs text-[#9a9490] group-hover:text-[#b8847e] transition-colors underline decoration-dotted">({{ $product->reviews_count }} đánh giá)</span>
                            </a>
                            <span class="text-[#d5cec7]">•</span>
                            <div class="text-xs text-[#9a9490] flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Đã kiểm duyệt chất lượng
                            </div>
                            <span class="text-[#d5cec7]">•</span>
                            {{-- Stock Indicator --}}
                            @php $displayStock = $hasVariants ? $product->variants->sum('stock') : $product->stock; @endphp
                            @if($displayStock > 0)
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Còn hàng ({{ $displayStock }})
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 bg-red-50 px-2.5 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    Hết hàng
                                </span>
                            @endif
                        </div>

                        {{-- Price Showcase Box --}}
                        <div class="bg-gradient-to-r from-[#faf7f4] via-[#fdfbf9] to-[#faf7f4] border border-[#efe8e3] rounded-2xl p-4 sm:p-5 mb-6">
                            <div class="flex items-baseline gap-3 flex-wrap">
                                <span class="font-serif text-3xl sm:text-4xl font-bold text-[#b8847e]">
                                    {{ number_format($displayPrice, 0, ',', '.') }}₫
                                </span>
                                @if($discountPercent > 0)
                                    <span class="text-[#9a9490] text-base line-through">
                                        {{ number_format($product->price, 0, ',', '.') }}₫
                                    </span>
                                    <span class="text-xs font-bold text-white bg-[#b8847e] px-2.5 py-0.5 rounded-full shadow-sm">
                                        Tiết kiệm {{ number_format($product->price - $displayPrice, 0, ',', '.') }}₫
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-[#9a9490] mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Giá đã bao gồm thuế VAT và bảo hiểm sản phẩm.
                            </p>
                        </div>

                        {{-- Short Description --}}
                        <div class="text-[#6b6560] text-sm leading-relaxed mb-6 font-normal">
                            {!! nl2br(e($product->description)) !!}
                        </div>

                        {{-- Add to Cart Form --}}
                        @if(($hasVariants ? $product->variants->sum('stock') : $product->stock) > 0)
                            <form action="{{ route('cart.add') }}" method="POST" id="add-to-cart-form">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">

                                {{-- Variants Selector --}}
                                @if($hasVariants)
                                    <div class="mb-6 p-4 rounded-2xl bg-[#faf7f4]/60 border border-[#efe8e3]" x-data="{
                                        selected: '',
                                        sizes: {{ json_encode($product->variants->pluck('size')->filter()->unique()->values()) }},
                                        colors: {{ json_encode($product->variants->pluck('color')->filter()->unique()->values()) }},
                                        variants: {{ json_encode($product->variants->map(fn($v) => ['id'=>$v->id,'size'=>$v->size,'color'=>$v->color,'stock'=>$v->stock,'price'=>$v->unitPrice()])->values()) }},
                                        get current() { return this.variants.find(v => v.id === Number(this.selected)) || null },
                                        get available() { return this.variants.filter(v => v.stock > 0) }
                                    }">
                                        <label class="text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-3 block">Phân loại:</label>
                                        {{-- Size Options --}}
                                        @if($product->variants->pluck('size')->filter()->isNotEmpty())
                                            <div class="mb-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <label class="text-xs uppercase tracking-wider font-semibold text-[#2c2a28]">Kích cỡ (Size):</label>
                                                    <span class="text-xs text-[#b8847e] font-medium" x-show="current?.size" x-text="'Đã chọn: ' + current?.size"></span>
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    <template x-for="size in sizes" :key="size">
                                                        <button type="button"
                                                                @click="
                                                                    const match = variants.find(v => v.size === size && (!colors.length || v.color === (current?.color ?? variants.find(v=>v.size===size && v.stock>0)?.color)));
                                                                    selected = (match || variants.find(v => v.size === size))?.id ?? '';
                                                                "
                                                                class="px-4 py-2 border rounded-xl text-sm font-medium transition-all duration-150"
                                                                :class="(current?.size === size)
                                                                    ? 'border-[#b8847e] bg-white text-[#b8847e] shadow-sm ring-2 ring-[#b8847e]/20 font-bold'
                                                                    : 'border-[#efe8e3] bg-white text-[#3d3d3d] hover:border-[#c9a9a6]'"
                                                                x-text="size"></button>
                                                    </template>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Color Options --}}
                                        @if($product->variants->pluck('color')->filter()->isNotEmpty())
                                            <div class="mb-3">
                                                <div class="flex items-center justify-between mb-2">
                                                    <label class="text-xs uppercase tracking-wider font-semibold text-[#2c2a28]">Màu sắc:</label>
                                                    <span class="text-xs text-[#b8847e] font-medium" x-show="current?.color" x-text="'Đã chọn: ' + current?.color"></span>
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    <template x-for="color in colors" :key="color">
                                                        <button type="button"
                                                                @click="
                                                                    const match = variants.find(v => v.color === color && (!sizes.length || v.size === (current?.size ?? variants.find(v=>v.color===color && v.stock>0)?.size)));
                                                                    selected = (match || variants.find(v => v.color === color))?.id ?? '';
                                                                "
                                                                class="px-4 py-2 border rounded-xl text-sm font-medium transition-all duration-150 flex items-center gap-1.5"
                                                                :class="(current?.color === color)
                                                                    ? 'border-[#b8847e] bg-white text-[#b8847e] shadow-sm ring-2 ring-[#b8847e]/20 font-bold'
                                                                    : 'border-[#efe8e3] bg-white text-[#3d3d3d] hover:border-[#c9a9a6]'">
                                                            <span class="w-3 h-3 rounded-full border border-black/10 inline-block bg-[#b8847e]"></span>
                                                            <span x-text="color"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        @endif

                                        <input type="hidden" name="variant_id" :value="selected || ''">
                                        <div class="flex items-center justify-between text-xs pt-2 border-t border-[#efe8e3] mt-3">
                                            <p class="text-[#9a9490]" x-show="current">
                                                Kho: <span class="font-semibold text-[#2c2a28]" x-text="current?.stock"></span> món · Giá: <span class="font-bold text-[#b8847e]" x-text="new Intl.NumberFormat('vi-VN').format(current?.price ?? 0) + '₫'"></span>
                                            </p>
                                            <p class="text-xs text-rose-500 font-medium" x-show="!selected && selected !== 0">
                                                * Vui lòng chọn phân loại size/màu để đặt mua
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Quantity Selector & Add Button --}}
                                <div class="space-y-4 mb-6">
                                    <div class="flex items-center gap-4">
                                        <label class="text-xs uppercase tracking-wider font-semibold text-[#2c2a28]">Số lượng:</label>
                                        <div class="flex items-center bg-white border border-[#efe8e3] rounded-xl p-1 shadow-sm">
                                            <button type="button" onclick="changeQty(-1)" class="w-9 h-9 rounded-lg flex items-center justify-center text-[#2c2a28] hover:bg-[#faf7f4] hover:text-[#b8847e] transition-colors font-bold">−</button>
                                            <input type="number" name="quantity" id="qty-input" value="1" min="1"
                                                   max="{{ $hasVariants ? 99 : $product->stock }}"
                                                   class="w-14 border-0 text-center text-sm font-bold text-[#2c2a28] focus:ring-0 p-0">
                                            <button type="button" onclick="changeQty(1)" class="w-9 h-9 rounded-lg flex items-center justify-center text-[#2c2a28] hover:bg-[#faf7f4] hover:text-[#b8847e] transition-colors font-bold">+</button>
                                        </div>
                                        <span class="text-xs text-[#9a9490]">
                                            (Tối đa: {{ $hasVariants ? 99 : $product->stock }})
                                        </span>
                                    </div>

                                    <div class="space-y-3 pt-2">
                                        {{-- Row 1: Hai nút chính Mua Ngay & Thêm vào giỏ --}}
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            {{-- MUA NGAY (Ưu tiên số 1: Đi thẳng vào Checkout không cần qua Cart) --}}
                                            <button type="submit" name="buy_now" value="1"
                                                    class="w-full bg-gradient-to-r from-[#b8847e] via-[#ab746e] to-[#a6736d] text-white py-4 px-6 rounded-2xl font-bold hover:shadow-xl hover:shadow-[#b8847e]/35 transition-all text-sm flex items-center justify-center gap-2 btn-shine cursor-pointer group">
                                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                </svg>
                                                <span class="tracking-wide">MUA NGAY</span>
                                            </button>

                                            {{-- Thêm vào giỏ hàng --}}
                                            <button type="submit"
                                                    class="w-full border-2 border-[#b8847e] text-[#b8847e] bg-white hover:bg-[#faf7f4] py-4 px-6 rounded-2xl font-bold transition-all text-sm flex items-center justify-center gap-2 shadow-sm cursor-pointer group">
                                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                                </svg>
                                                <span>Thêm vào giỏ hàng</span>
                                            </button>
                                        </div>

                                        {{-- Row 2: Yêu thích & So sánh --}}
                                        <div class="grid grid-cols-2 gap-3">
                                            {{-- Wishlist Button --}}
                                            @auth
                                                @php
                                                    $isWishlisted = auth()->user()->wishlist ? auth()->user()->wishlist->items()->where('product_id', $product->id)->exists() : false;
                                                @endphp
                                                <div x-data="{ wishlistLoading: false, wishlisted: {{ $isWishlisted ? 'true' : 'false' }} }">
                                                    <button type="button"
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
                                                                        if (data.wishlist_count !== undefined && window.Alpine && window.Alpine.store('wishlist')) {
                                                                            window.Alpine.store('wishlist').count = data.wishlist_count;
                                                                        }
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
                                                            class="w-full border border-[#efe8e3] bg-white text-[#2c2a28] py-3 px-4 rounded-xl font-medium hover:border-[#b8847e] hover:text-[#b8847e] transition-colors text-xs flex items-center justify-center gap-2 shadow-sm disabled:opacity-60 cursor-pointer">
                                                        <svg x-show="!wishlisted" class="w-4 h-4 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                        </svg>
                                                        <svg x-show="wishlisted" x-cloak class="w-4 h-4 text-[#b8847e]" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                        </svg>
                                                        <span x-text="wishlisted ? 'Đã yêu thích' : 'Yêu thích sản phẩm'"></span>
                                                    </button>
                                                </div>
                                            @else
                                                <a href="{{ route('login') }}"
                                                   @click.prevent="
                                                       window.dispatchEvent(new CustomEvent('toast', {
                                                           detail: { message: 'Vui lòng đăng nhập để lưu sản phẩm vào danh sách yêu thích nhé!', type: 'info' }
                                                       }));
                                                       setTimeout(() => { window.location.href = '{{ route('login') }}'; }, 800);
                                                   "
                                                   title="Đăng nhập để thêm vào yêu thích"
                                                   class="w-full border border-[#efe8e3] bg-white text-[#2c2a28] py-3 px-4 rounded-xl font-medium hover:border-[#b8847e] hover:text-[#b8847e] transition-colors text-xs flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                                                    <svg class="w-4 h-4 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                    </svg>
                                                    <span>Yêu thích sản phẩm</span>
                                                </a>
                                            @endauth

                                            {{-- Compare Button --}}
                                            <div x-data="{ compareLoading: false }">
                                                <button type="button"
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
                                                        class="w-full border border-[#efe8e3] bg-white text-[#2c2a28] py-3 px-4 rounded-xl font-medium hover:border-[#b8847e] hover:text-[#b8847e] transition-colors text-xs flex items-center justify-center gap-2 disabled:opacity-60 shadow-sm cursor-pointer">
                                                    <svg x-show="!compareLoading" class="w-4 h-4 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                                    </svg>
                                                    <svg x-show="compareLoading" x-cloak class="w-4 h-4 animate-spin text-[#b8847e]" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    <span x-text="compareLoading ? 'Đang thêm...' : 'So sánh sản phẩm'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        @else
                            {{-- Out of stock banner --}}
                            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-sm mb-6 flex items-center gap-3">
                                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Sản phẩm này hiện đang tạm hết hàng. Quý khách vui lòng chọn sản phẩm khác hoặc liên hệ bộ phận hỗ trợ.</span>
                            </div>
                        @endif

                        {{-- Trust Badges Strip (4 Pillars) --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 py-4 border-t border-b border-[#efe8e3] my-6">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center flex-shrink-0 text-[#b8847e]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-[#2c2a28]">Freeship từ 500K</h4>
                                    <p class="text-[10px] text-[#9a9490]">Toàn quốc</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center flex-shrink-0 text-[#b8847e]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-[#2c2a28]">Đổi trả 7 ngày</h4>
                                    <p class="text-[10px] text-[#9a9490]">Dễ dàng & nhanh</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center flex-shrink-0 text-[#b8847e]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-[#2c2a28]">Chính hãng 100%</h4>
                                    <p class="text-[10px] text-[#9a9490]">Cam kết hoàn tiền</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center flex-shrink-0 text-[#b8847e]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-[#2c2a28]">Giao hỏa tốc 2h</h4>
                                    <p class="text-[10px] text-[#9a9490]">Tại TP.HCM & HN</p>
                                </div>
                            </div>
                        </div>

                        {{-- Share Section with Affiliate notice --}}
                        @php $btnShare = 'bg-[#faf7f4] hover:bg-[#b8847e] text-[#2c2a28] hover:text-white border border-[#efe8e3] px-3 py-1.5 rounded-xl text-xs font-medium transition-all shadow-sm'; @endphp
                        <div class="pt-2" data-testid="share-buttons">
                            <div class="flex items-center justify-between mb-2.5">
                                <p class="text-xs uppercase tracking-wider font-semibold text-[#2c2a28]">Chia sẻ sản phẩm này:</p>
                                <button type="button" onclick="copyShareLink()" id="copy-share-link" class="text-xs text-[#b8847e] font-semibold hover:underline flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    Sao chép link
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" onclick="shareNative()" id="share-native"
                                        class="hidden items-center gap-1.5 bg-[#b8847e] text-white px-3.5 py-1.5 rounded-xl text-xs font-medium hover:bg-[#a6736d] transition-colors shadow-sm">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12s-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                    Chia sẻ...
                                </button>
                                <button type="button" onclick="shareTo('facebook')" class="{{ $btnShare }}">Facebook</button>
                                <button type="button" onclick="shareTo('messenger')" class="{{ $btnShare }}">Messenger</button>
                                <button type="button" onclick="shareTo('zalo')" class="{{ $btnShare }}">Zalo</button>
                                <button type="button" onclick="shareTo('x')" class="{{ $btnShare }}">X (Twitter)</button>
                                <button type="button" onclick="shareTo('telegram')" class="{{ $btnShare }}">Telegram</button>
                                <button type="button" onclick="shareTo('email')" class="{{ $btnShare }}">Email</button>
                            </div>
                            @auth
                                <p class="mt-2.5 text-xs text-[#9a9490] flex items-center gap-1 bg-[#faf7f4] p-2 rounded-lg border border-[#efe8e3]">
                                    <svg class="w-4 h-4 text-[#b8847e] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Link chia sẻ đã tự động gắn mã giới thiệu của bạn — khi bạn bè mua qua link, bạn sẽ nhận hoa hồng.
                                </p>
                            @endauth
                            <input type="hidden" id="referral-link"
                                   value="{{ route('products.show', $product->slug) }}@auth?ref={{ Auth::user()->referral_code }}@endauth">
                            <input type="hidden" id="share-title" value="{{ $product->name }} - SocialShop">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reviews Section --}}
        <div id="reviews" class="mt-12 scroll-mt-24">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="font-serif text-2xl sm:text-3xl font-semibold text-[#2c2a28]">Đánh giá từ khách hàng</h2>
                    <p class="text-xs sm:text-sm text-[#9a9490] mt-1">Trải nghiệm thực tế từ những người đã mua sản phẩm này</p>
                </div>
            </div>

            {{-- Rating Summary Card --}}
            <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 mb-8 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                    {{-- Big Score --}}
                    <div class="md:col-span-4 text-center md:text-left md:border-r border-[#efe8e3] md:pr-8">
                        <div class="text-6xl font-serif font-bold text-[#b8847e] leading-none mb-3">{{ number_format($product->average_rating, 1) }}</div>
                        <div class="flex items-center justify-center md:justify-start gap-1 text-[#b8847e] mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($product->average_rating))
                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @else
                                    <svg class="w-5 h-5 fill-current text-[#efe8e3]" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endif
                            @endfor
                        </div>
                        <p class="text-sm text-[#9a9490]">Tổng cộng {{ $product->reviews_count }} đánh giá xác thực</p>
                    </div>

                    {{-- Breakdown Bars --}}
                    <div class="md:col-span-8 space-y-2">
                        @for($i = 5; $i >= 1; $i--)
                            @php
                                $count = $product->reviews()->where('status', 'approved')->where('rating', $i)->count();
                                $percent = $product->reviews_count > 0 ? round(($count / $product->reviews_count) * 100) : 0;
                            @endphp
                            <div class="flex items-center gap-3 text-xs sm:text-sm">
                                <span class="w-8 font-medium text-[#2c2a28] flex items-center gap-1">
                                    {{ $i }}
                                    <svg class="w-3.5 h-3.5 text-[#b8847e] fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </span>
                                <div class="flex-1 h-2.5 bg-[#faf7f4] border border-[#efe8e3] rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-[#e8c4c4] to-[#b8847e] rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                                </div>
                                <span class="w-12 text-right text-xs text-[#9a9490] font-medium">{{ $count }} ({{ $percent }}%)</span>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Review Form --}}
            @auth
                @if($canReview)
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 mb-8 shadow-sm" x-data="reviewForm()">
                        <h3 class="font-serif text-xl font-semibold text-[#2c2a28] mb-1">Viết đánh giá của bạn</h3>
                        <p class="text-xs text-[#9a9490] mb-5">Chia sẻ cảm nhận chân thật giúp người mua khác lựa chọn tốt hơn</p>

                        @if($errors->any())
                            <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-2xl text-sm text-red-600 flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <form action="{{ route('reviews.store', $product) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-5">
                                <label class="text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-2 block">1. Đánh giá chất lượng sao:</label>
                                <div class="flex items-center gap-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" @click="rating = {{ $i }}"
                                                class="transition-transform hover:scale-120 focus:outline-none p-1">
                                            <svg class="w-9 h-9" :class="rating >= {{ $i }} ? 'text-[#b8847e]' : 'text-[#efe8e3] hover:text-[#e8c4c4]'"
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </button>
                                    @endfor
                                    <input type="hidden" name="rating" :value="rating">
                                    <span class="text-xs text-[#9a9490] ml-2" x-show="rating > 0" x-text="rating + '/5 sao'"></span>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-2 block">2. Chi tiết nhận xét:</label>
                                <textarea name="comment" rows="4" maxlength="1000"
                                          class="w-full border border-[#efe8e3] rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#b8847e] focus:border-transparent resize-none bg-[#faf7f4]/40"
                                          placeholder="Chất vải, kích thước, form dáng có đúng mô tả không? Bạn cảm nhận thế nào?"></textarea>
                            </div>

                            <div class="mb-6">
                                <label class="text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-2 block">3. Ảnh đính kèm (tùy chọn):</label>
                                <div class="border border-dashed border-[#efe8e3] rounded-2xl p-4 bg-[#faf7f4]/40">
                                    <input type="file" name="image" accept="image/*"
                                           class="w-full text-xs text-[#9a9490] file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#b8847e] file:text-white hover:file:bg-[#a6736d] file:cursor-pointer file:transition-colors">
                                    <p class="text-[11px] text-[#9a9490] mt-1.5">Hỗ trợ định dạng JPG, PNG, WEBP. Dung lượng tối đa 2MB.</p>
                                </div>
                            </div>

                            <button type="submit" :disabled="!rating || submitting"
                                    class="bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white px-8 py-3.5 rounded-2xl text-sm font-bold hover:shadow-lg hover:shadow-[#b8847e]/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 btn-shine">
                                <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="submitting ? 'Đang gửi...' : 'Gửi đánh giá'"></span>
                            </button>
                        </form>
                    </div>
                @elseif($hasReviewed)
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 mb-8 text-center shadow-sm">
                        <div class="w-14 h-14 mx-auto mb-3 bg-[#faf7f4] border border-[#efe8e3] rounded-full flex items-center justify-center text-[#b8847e]">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h4 class="font-serif text-lg font-semibold text-[#2c2a28] mb-1">Cảm ơn bạn!</h4>
                        <p class="text-[#9a9490] text-xs sm:text-sm">Bạn đã gửi đánh giá cho sản phẩm này rồi.</p>
                    </div>
                @else
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 mb-8 text-center shadow-sm">
                        <p class="text-[#9a9490] text-sm">Mua sản phẩm để để lại đánh giá.</p>
                    </div>
                @endif
            @else
                <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 mb-8 text-center shadow-sm">
                    <p class="text-[#2c2a28] text-sm font-medium mb-2">Đăng nhập tài khoản và mua sản phẩm để chia sẻ đánh giá.</p>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-[#b8847e] px-5 py-2 rounded-xl hover:bg-[#a6736d] transition-colors shadow-sm">
                        Đăng nhập ngay
                    </a>
                </div>
            @endauth

            {{-- Review List --}}
            <div class="space-y-4">
                @forelse($reviews as $review)
                    <div class="bg-white border border-[#efe8e3] rounded-2xl p-5 sm:p-6 shadow-sm hover:border-[#c9a9a6] transition-colors">
                        <div class="flex items-start gap-4">
                            {{-- Avatar --}}
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#faf7f4] to-[#e8c4c4] border border-[#efe8e3] flex items-center justify-center flex-shrink-0 text-sm font-bold text-[#b8847e] shadow-inner overflow-hidden">
                                @if($review->user->avatar)
                                    <img src="{{ str_starts_with($review->user->avatar, 'http') ? $review->user->avatar : asset('storage/' . $review->user->avatar) }}" alt="{{ $review->user->name }}" class="w-full h-full object-cover">
                                @else
                                    <span>{{ mb_substr($review->user->name, 0, 1) }}</span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between flex-wrap gap-2 mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-[#2c2a28]">{{ $review->user->name }}</span>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Đã mua hàng
                                        </span>
                                    </div>
                                    <span class="text-xs text-[#9a9490]">{{ $review->created_at->diffForHumans() }}</span>
                                </div>

                                {{-- Stars --}}
                                <div class="flex items-center gap-0.5 text-[#b8847e] mb-2.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $review->rating)
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @else
                                            <svg class="w-4 h-4 fill-current text-[#efe8e3]" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @endif
                                    @endfor
                                </div>

                                @if($review->comment)
                                    <p class="text-sm text-[#4a4744] leading-relaxed mb-3">{{ $review->comment }}</p>
                                @endif

                                @if($review->image)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $review->image) }}" alt="Ảnh đánh giá"
                                             class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-xl border border-[#efe8e3] cursor-pointer hover:opacity-90 hover:scale-102 transition-all shadow-sm">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-10 text-center shadow-sm">
                        <div class="w-16 h-16 mx-auto mb-3 bg-[#faf7f4] border border-[#efe8e3] rounded-full flex items-center justify-center text-[#c9a9a6]">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <h4 class="font-serif text-lg font-semibold text-[#2c2a28] mb-1">Chưa có đánh giá nào</h4>
                        <p class="text-xs sm:text-sm text-[#9a9490]">Hãy là người đầu tiên trải nghiệm và chia sẻ nhận xét về sản phẩm này.</p>
                    </div>
                @endforelse
            </div>

            {{-- Load More Pagination --}}
            @if($reviews->hasPages())
                <div class="mt-8 text-center">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>

        {{-- Thường được mua cùng (từ các đơn hàng thật) --}}
        @if($boughtTogether->isNotEmpty())
            <div class="mt-12" data-testid="bought-together">
                <div class="mb-6">
                    <h2 class="font-serif text-2xl font-semibold text-[#3d3d3d]">Khách mua sản phẩm này cũng mua</h2>
                    <p class="text-[#9a9490] text-sm mt-0.5">Dựa trên các đơn hàng thực tế tại SocialShop</p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($boughtTogether as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Related Products --}}
        @if($relatedProducts->count())
            <div class="mt-16 pt-10 border-t border-[#efe8e3]">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <span class="text-xs uppercase tracking-widest text-[#b8847e] font-semibold">Gợi ý riêng cho bạn</span>
                        <h2 class="font-serif text-2xl sm:text-3xl font-semibold text-[#2c2a28] mt-1">Sản phẩm liên quan</h2>
                    </div>
                    <a href="{{ route('products.index') }}" class="text-xs sm:text-sm font-semibold text-[#b8847e] hover:text-[#a6736d] flex items-center gap-1 transition-colors">
                        Xem tất cả
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    @foreach($relatedProducts as $relProduct)
                        <x-product-card :product="$relProduct" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function reviewForm() {
            return {
                rating: 0,
                submitting: false,
            }
        }

        function changeQty(delta) {
            const input = document.getElementById('qty-input');
            let val = parseInt(input.value) + delta;
            val = Math.max(1, Math.min(val, parseInt(input.max)));
            input.value = val;
        }

        function shareData() {
            return {
                url: document.getElementById('referral-link').value,
                title: document.getElementById('share-title').value,
            };
        }

        // Nút "Chia sẻ..." mở bảng chia sẻ của hệ điều hành (TikTok, Instagram, tin nhắn...) nếu trình duyệt hỗ trợ
        if (navigator.share) {
            const nativeBtn = document.getElementById('share-native');
            if (nativeBtn) {
                nativeBtn.classList.remove('hidden');
                nativeBtn.classList.add('inline-flex');
            }
        }

        function shareNative() {
            const { url, title } = shareData();
            navigator.share({ title, text: title, url }).catch(() => {});
        }

        function shareTo(network) {
            const { url, title } = shareData();
            const u = encodeURIComponent(url);
            const t = encodeURIComponent(title);
            const targets = {
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${u}`,
                messenger: `https://www.messenger.com/share?link=${u}`,
                zalo: `https://zalo.me/share/?url=${u}`,
                x: `https://twitter.com/intent/tweet?url=${u}&text=${t}`,
                telegram: `https://t.me/share/url?url=${u}&text=${t}`,
                email: `mailto:?subject=${t}&body=${t}%0A${u}`,
            };
            if (network === 'email') {
                window.location.href = targets.email;
            } else {
                window.open(targets[network], '_blank', 'noopener,width=640,height=560');
            }
        }

        function copyShareLink() {
            const { url } = shareData();
            const btn = document.getElementById('copy-share-link');
            const done = () => {
                btn.textContent = 'Đã sao chép ✓';
                setTimeout(() => btn.innerHTML = `
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Sao chép link
                `, 2000);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done);
            } else {
                const tmp = document.createElement('textarea');
                tmp.value = url;
                document.body.appendChild(tmp);
                tmp.select();
                document.execCommand('copy');
                tmp.remove();
                done();
            }
        }
    </script>
    @endpush
</x-layouts.app>
