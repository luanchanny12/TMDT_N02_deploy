<x-layouts.app
    :title="$product->name"
    :meta-title="$metaTitle"
    :meta-description="$metaDescription"
    :meta-image="$metaImage"
    :json-ld="$jsonLd"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-[#9a9490] mb-6">
            <a href="{{ route('home') }}" class="hover:text-[#b8847e] transition-colors">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('products.index') }}" class="hover:text-[#b8847e] transition-colors">Sản phẩm</a>
            @if($product->category ?? false)
                <span>/</span>
                <a href="{{ route('products.index', ['category' => $product->category->id]) }}" class="hover:text-[#b8847e] transition-colors">{{ $product->category->name }}</a>
            @endif
            <span>/</span>
            <span class="text-[#3d3d3d] font-medium truncate max-w-[200px]">{{ $product->name }}</span>
        </nav>

        {{-- Product Detail --}}
        <div class="bg-white border border-[#efe8e3] rounded-2xl overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

                {{-- Gallery --}}
                <div class="bg-[#f5f0ec] p-6">
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
                    @endphp

                    @if($firstImage)
                        <div x-data="{ current: {{ json_encode($firstImage['src']) }} }">

                            {{-- Ảnh chính --}}
                            <div class="rounded-xl overflow-hidden mb-3">
                                <img :src="current"
                                     :alt="{{ json_encode($product->name) }}"
                                     class="w-full h-[350px] md:h-[420px] object-cover transition-opacity duration-200">
                            </div>

                            {{-- Thumbnails (chỉ hiển thị nếu có ≥ 2 ảnh) --}}
                            @if($galleryImages->count() > 1)
                                <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
                                    @foreach($galleryImages as $i => $img)
                                        <button type="button"
                                                @click="current = {{ json_encode($img['src']) }}"
                                                :class="current === {{ json_encode($img['src']) }}
                                                    ? 'ring-2 ring-[#b8847e] ring-offset-1 opacity-100'
                                                    : 'opacity-60 hover:opacity-90'"
                                                class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden transition-all duration-150">
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
                        <div class="w-full h-[350px] md:h-[420px] bg-[#f5f0ec] rounded-xl flex items-center justify-center">
                            <svg class="w-16 h-16 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="p-6 md:p-8 flex flex-col">
                    {{-- Category --}}
                    @if($product->category ?? false)
                        <span class="text-[11px] uppercase tracking-[1px] text-[#b8847e] font-medium mb-2">
                            {{ $product->category->name }}
                        </span>
                    @endif

                    <h1 class="font-serif text-2xl md:text-3xl font-semibold text-[#3d3d3d] mb-4 leading-snug">{{ $product->name }}</h1>

                    {{-- Price --}}
                    @php
                        $discountPercent = 0;
                        if(isset($product->sale_price) && $product->sale_price && $product->sale_price < $product->price) {
                            $discountPercent = round((1 - $product->sale_price / $product->price) * 100);
                        }
                        $displayPrice = $discountPercent > 0 ? $product->sale_price : $product->price;
                    @endphp
                    <div class="mb-5">
                        @if($discountPercent > 0)
                            <span class="block text-[#9a9490] text-sm line-through mb-0.5">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                        @endif
                        <span class="text-2xl font-bold text-[#b8847e]">{{ number_format($displayPrice, 0, ',', '.') }}đ</span>
                        @if($discountPercent > 0)
                            <span class="ml-2 text-xs font-bold text-white bg-[#b8847e] px-2 py-0.5 rounded">-{{ $discountPercent }}%</span>
                        @endif
                    </div>

                    {{-- Description --}}
                    <div class="text-[#6b6560] text-sm leading-relaxed mb-6">
                        {!! nl2br(e($product->description)) !!}
                    </div>

                    {{-- Stock --}}
                    <div class="mb-5">
                        @php $displayStock = $hasVariants ? $product->variants->sum('stock') : $product->stock; @endphp
                        @if($displayStock > 0)
                            <span class="inline-flex items-center gap-1.5 text-sm text-green-700 bg-green-50 px-3 py-1 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Còn hàng ({{ $displayStock }})
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-sm text-red-600 bg-red-50 px-3 py-1 rounded-lg">
                                Hết hàng
                            </span>
                        @endif
                    </div>

                    {{-- Add to Cart --}}
                    @if(($hasVariants ? $product->variants->sum('stock') : $product->stock) > 0)
                        <form action="{{ route('cart.add') }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                            @if($hasVariants)
                                <div class="mb-5" x-data="{
                                    selected: '',
                                    sizes: {{ json_encode($product->variants->pluck('size')->filter()->unique()->values()) }},
                                    colors: {{ json_encode($product->variants->pluck('color')->filter()->unique()->values()) }},
                                    variants: {{ json_encode($product->variants->map(fn($v) => ['id'=>$v->id,'size'=>$v->size,'color'=>$v->color,'stock'=>$v->stock,'price'=>$v->unitPrice()])->values()) }},
                                    get current() { return this.variants.find(v => v.id === Number(this.selected)) || null },
                                    get available() { return this.variants.filter(v => v.stock > 0) }
                                }">
                                    <label class="text-sm font-medium text-[#3d3d3d] mb-2 block">Phân loại:</label>

                                    @if($product->variants->pluck('size')->filter()->isNotEmpty())
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <template x-for="size in sizes" :key="size">
                                                <button type="button"
                                                        @click="
                                                            const match = variants.find(v => v.size === size && (!colors.length || v.color === (current?.color ?? variants.find(v=>v.size===size && v.stock>0)?.color)));
                                                            selected = (match || variants.find(v => v.size === size))?.id ?? '';
                                                        "
                                                        class="px-3 py-1.5 border rounded-lg text-sm"
                                                        :class="(current?.size === size) ? 'border-[#b8847e] bg-[#faf7f4] text-[#b8847e] font-semibold' : 'border-[#efe8e3] text-[#3d3d3d]'"
                                                        x-text="size"></button>
                                            </template>
                                        </div>
                                    @endif

                                    @if($product->variants->pluck('color')->filter()->isNotEmpty())
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <template x-for="color in colors" :key="color">
                                                <button type="button"
                                                        @click="
                                                            const match = variants.find(v => v.color === color && (!sizes.length || v.size === (current?.size ?? variants.find(v=>v.color===color && v.stock>0)?.size)));
                                                            selected = (match || variants.find(v => v.color === color))?.id ?? '';
                                                        "
                                                        class="px-3 py-1.5 border rounded-lg text-sm"
                                                        :class="(current?.color === color) ? 'border-[#b8847e] bg-[#faf7f4] text-[#b8847e] font-semibold' : 'border-[#efe8e3] text-[#3d3d3d]'"
                                                        x-text="color"></button>
                                            </template>
                                        </div>
                                    @endif

                                    <input type="hidden" name="variant_id" :value="selected || ''">
                                    <p class="text-xs text-[#9a9490]" x-show="current">
                                        <span x-show="current">Còn <span x-text="current?.stock"></span> · <span x-text="new Intl.NumberFormat('vi-VN').format(current?.price ?? 0) + '₫'"></span></span>
                                    </p>
                                    <p class="text-xs text-red-500 mt-1" x-show="!selected && selected !== 0">Vui lòng chọn phân loại</p>
                                </div>
                            @endif

                            <div class="mb-5">
                                <label class="text-sm font-medium text-[#3d3d3d] mb-2 block">Số lượng:</label>
                                <div class="flex items-center gap-1">
                                    <button type="button" onclick="changeQty(-1)" class="qty-btn">−</button>
                                    <input type="number" name="quantity" id="qty-input" value="1" min="1"
                                           max="{{ $hasVariants ? 99 : $product->stock }}"
                                           class="w-14 border border-[#efe8e3] rounded-lg px-2 py-1.5 text-center text-sm font-medium focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent">
                                    <button type="button" onclick="changeQty(1)" class="qty-btn">+</button>
                                </div>
                            </div>
                            <button type="submit"
                                    class="w-full bg-[#b8847e] text-white py-3 rounded-lg font-medium hover:bg-[#a6736d] transition-colors text-sm">
                                Thêm vào giỏ hàng
                            </button>
                        </form>
                    @endif

                    {{-- Compare --}}
                    <div class="mt-3" x-data="{ compareLoading: false }">
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
                                class="w-full border border-[#c9a9a6] text-[#b8847e] py-3 rounded-lg font-medium hover:bg-[#e8c4c4] transition-colors text-sm flex items-center justify-center gap-2 disabled:opacity-60">
                            <svg x-show="!compareLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <svg x-show="compareLoading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="compareLoading ? 'Đang thêm...' : 'Thêm vào so sánh'"></span>
                        </button>
                    </div>

                    {{-- Share --}}
                    @php $btn = 'bg-[#3d3a37] text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-[#b8847e] transition-colors'; @endphp
                    <div class="mt-6 pt-5 border-t border-[#efe8e3]" data-testid="share-buttons">
                        <p class="text-sm font-medium text-[#3d3d3d] mb-3">Chia sẻ sản phẩm:</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="shareNative()" id="share-native"
                                    class="hidden items-center gap-1 bg-[#b8847e] text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-[#a6736d] transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12s-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                Chia sẻ...
                            </button>
                            <button type="button" onclick="shareTo('facebook')" class="{{ $btn }}">Facebook</button>
                            <button type="button" onclick="shareTo('messenger')" class="{{ $btn }}">Messenger</button>
                            <button type="button" onclick="shareTo('zalo')" class="{{ $btn }}">Zalo</button>
                            <button type="button" onclick="shareTo('x')" class="{{ $btn }}">X (Twitter)</button>
                            <button type="button" onclick="shareTo('telegram')" class="{{ $btn }}">Telegram</button>
                            <button type="button" onclick="shareTo('email')" class="{{ $btn }}">Email</button>
                            <button type="button" onclick="copyShareLink()" id="copy-share-link" class="{{ $btn }}">Sao chép link</button>
                        </div>
                        @auth
                            <p class="mt-2 text-xs text-[#9a9490]">Link đã gắn mã giới thiệu của bạn — bạn bè mua hàng qua link này, bạn nhận hoa hồng.</p>
                        @endauth
                        <input type="hidden" id="referral-link"
                               value="{{ route('products.show', $product->slug) }}@auth?ref={{ Auth::user()->referral_code }}@endauth">
                        <input type="hidden" id="share-title" value="{{ $product->name }} - SocialShop">
                    </div>
                </div>
            </div>
        </div>

        {{-- Reviews Section --}}
        <div id="reviews" class="mt-12 scroll-mt-24">
            <h2 class="font-serif text-2xl font-semibold text-[#3d3d3d] mb-6">Đánh giá sản phẩm</h2>

            {{-- Rating Summary --}}
            <div class="bg-white border border-[#efe8e3] rounded-xl p-6 mb-8 flex flex-col sm:flex-row items-start sm:items-center gap-6">
                <div class="text-center sm:text-left">
                    <div class="text-5xl font-bold text-[#b8847e] leading-none">{{ $product->average_rating }}</div>
                    <div class="mt-2 text-[#c9a9a6] text-lg">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= floor($product->average_rating))
                                ★
                            @elseif($i - $product->average_rating < 1)
                                ★
                            @else
                                ☆
                            @endif
                        @endfor
                    </div>
                    <p class="mt-1 text-sm text-[#9a9490]">{{ $product->reviews_count }} đánh giá</p>
                </div>
                <div class="flex-1 w-full space-y-1.5">
                    @for($i = 5; $i >= 1; $i--)
                        @php
                            $count = $product->reviews()->where('status', 'approved')->where('rating', $i)->count();
                            $percent = $product->reviews_count > 0 ? round(($count / $product->reviews_count) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-3 text-[#9a9490]">{{ $i }}</span>
                            <svg class="w-4 h-4 text-[#c9a9a6]" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <div class="flex-1 h-2 bg-[#f5f0ec] rounded-full overflow-hidden">
                                <div class="h-full bg-[#c9a9a6] rounded-full" style="width: {{ $percent }}%"></div>
                            </div>
                            <span class="w-10 text-right text-[#9a9490]">{{ $count }}</span>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Review Form --}}
            @auth
                @if($canReview)
                    <div class="bg-white border border-[#efe8e3] rounded-xl p-6 mb-8" x-data="reviewForm()">
                        <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Viết đánh giá của bạn</h3>

                        @if($errors->any())
                            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form action="{{ route('reviews.store', $product) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-4">
                                <label class="text-sm font-medium text-[#3d3d3d] mb-2 block">Đánh giá sao</label>
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" @click="rating = {{ $i }}"
                                                class="transition-transform hover:scale-110">
                                            <svg class="w-8 h-8" :class="rating >= {{ $i }} ? 'text-[#c9a9a6]' : 'text-[#e8e3df]'"
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </button>
                                    @endfor
                                    <input type="hidden" name="rating" :value="rating">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="text-sm font-medium text-[#3d3d3d] mb-2 block">Nội dung bình luận</label>
                                <textarea name="comment" rows="3" maxlength="1000"
                                          class="w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent resize-none"
                                          placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."></textarea>
                            </div>

                            <div class="mb-5">
                                <label class="text-sm font-medium text-[#3d3d3d] mb-2 block">Ảnh đính kèm (không bắt buộc)</label>
                                <input type="file" name="image" accept="image/*"
                                       class="w-full text-sm text-[#9a9490] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#faf7f4] file:text-[#b8847e] hover:file:bg-[#efe8e3] file:cursor-pointer file:transition-colors">
                                <p class="text-xs text-[#9a9490] mt-1">JPG, PNG. Tối đa 2MB.</p>
                            </div>

                            <button type="submit" :disabled="!rating || submitting"
                                    class="bg-[#b8847e] text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-[#a6736d] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="submitting ? 'Đang gửi...' : 'Gửi đánh giá'"></span>
                            </button>
                        </form>
                    </div>
                @elseif($hasReviewed)
                    <div class="bg-white border border-[#efe8e3] rounded-xl p-6 mb-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-[#f5f0ec] to-[#efe8e3] rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-[#9a9490] text-sm">Bạn đã đánh giá sản phẩm này rồi.</p>
                    </div>
                @else
                    <div class="bg-white border border-[#efe8e3] rounded-xl p-6 mb-8 text-center">
                        <p class="text-[#9a9490] text-sm">Mua sản phẩm để để lại đánh giá.</p>
                    </div>
                @endif
            @else
                <div class="bg-white border border-[#efe8e3] rounded-xl p-6 mb-8 text-center">
                    <p class="text-[#9a9490] text-sm mb-2">Đăng nhập và mua sản phẩm để để lại đánh giá.</p>
                    <a href="{{ route('login') }}" class="text-sm text-[#b8847e] font-medium hover:underline">Đăng nhập ngay</a>
                </div>
            @endauth

            {{-- Review List --}}
            <div class="space-y-0">
                @forelse($reviews as $review)
                    <div class="border-b border-[#efe8e3] pb-4 mb-4">
                        <div class="flex items-start gap-3">
                            {{-- Avatar --}}
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#f5f0ec] to-[#e8c4c4] flex items-center justify-center flex-shrink-0">
                                @if($review->user->avatar)
                                    <img src="{{ str_starts_with($review->user->avatar, 'http') ? $review->user->avatar : asset('storage/' . $review->user->avatar) }}" alt="{{ $review->user->name }}" class="w-full h-full rounded-full object-cover">
                                @else
                                    <span class="text-sm font-semibold text-[#b8847e]">{{ mb_substr($review->user->name, 0, 1) }}</span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium text-[#3d3d3d]">{{ $review->user->name }}</span>
                                    <div class="text-[#c9a9a6] text-sm">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $review->rating) ★ @else ☆ @endif
                                        @endfor
                                    </div>
                                    <span class="text-xs text-[#9a9490]">{{ $review->created_at->diffForHumans() }}</span>
                                </div>

                                @if($review->comment)
                                    <p class="mt-1.5 text-sm text-[#6b6560] leading-relaxed">{{ $review->comment }}</p>
                                @endif

                                @if($review->image)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $review->image) }}" alt="Ảnh đánh giá"
                                             class="w-20 h-20 object-cover rounded-lg border border-[#efe8e3] cursor-pointer hover:opacity-80 transition-opacity">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-[#f5f0ec] to-[#efe8e3] rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <p class="font-serif text-lg font-semibold text-[#3d3d3d] mb-1">Chưa có đánh giá nào</p>
                        <p class="text-sm text-[#9a9490]">Hãy là người đầu tiên đánh giá sản phẩm này.</p>
                    </div>
                @endforelse
            </div>

            {{-- Load More --}}
            @if($reviews->hasPages())
                <div class="mt-6 text-center">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>

        {{-- Related Products --}}
        @if($relatedProducts->count())
            <div class="mt-12">
                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h2 class="font-serif text-2xl font-semibold text-[#3d3d3d]">Sản phẩm liên quan</h2>
                        <p class="text-[#9a9490] text-sm mt-0.5">Có thể bạn quan tâm</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($relatedProducts as $product)
                        <x-product-card :product="$product" />
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
            nativeBtn.classList.remove('hidden');
            nativeBtn.classList.add('inline-flex');
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
                setTimeout(() => btn.textContent = 'Sao chép link', 2000);
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
