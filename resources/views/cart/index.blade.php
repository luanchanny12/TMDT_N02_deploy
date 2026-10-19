<x-layouts.app :title="$cartItems->isNotEmpty() ? 'Giỏ hàng' : 'Giỏ hàng trống'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">

        {{-- Page Header --}}
        <div class="flex items-center justify-between flex-wrap gap-4 mb-8">
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28] flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e] shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </span>
                <span>Giỏ hàng của bạn</span>
                <span class="text-xs sm:text-sm font-sans font-normal text-[#9a9490] bg-[#faf7f4] border border-[#efe8e3] px-3 py-1 rounded-full">
                    {{ $cartItems->sum('quantity') }} sản phẩm
                </span>
            </h1>

            <a href="{{ route('products.index') }}" class="text-xs sm:text-sm font-semibold text-[#b8847e] hover:text-[#a6736d] flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Tiếp tục mua sắm
            </a>
        </div>

        @if($cartItems->isNotEmpty())

            {{-- Free Shipping Progress Bar --}}
            @php
                $subtotal = $cartTotal;
                $pricing = app(\App\Services\Order\PricingService::class);
                $freeShippingThreshold = max(1, $pricing->nationwideFreeShippingThreshold());
                $lowestShippingFee = $pricing->lowestShippingFee();
                $progress = min(($subtotal / $freeShippingThreshold) * 100, 100);
                $remaining = max($freeShippingThreshold - $subtotal, 0);
            @endphp
            <div class="bg-white border border-[#efe8e3] rounded-2xl p-5 mb-8 shadow-sm animate-fade-in">
                @if($subtotal < $freeShippingThreshold)
                    <div class="flex items-center gap-3.5 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center flex-shrink-0 text-[#b8847e]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm text-[#2c2a28]">
                                Mua thêm <strong class="text-[#b8847e] font-bold">{{ number_format($remaining, 0, ',', '.') }}₫</strong> để nhận <span class="font-bold text-emerald-700">MIỄN PHÍ VẬN CHUYỂN</span> toàn quốc!
                            </p>
                        </div>
                        <span class="text-xs font-bold text-[#b8847e] flex-shrink-0">{{ round($progress) }}%</span>
                    </div>
                    <div class="h-2.5 bg-[#faf7f4] border border-[#efe8e3] rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#e8c4c4] via-[#c9a9a6] to-[#b8847e] rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                    </div>
                @else
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center flex-shrink-0 text-emerald-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-emerald-800">Tuyệt vời! Đơn hàng của bạn đã đủ điều kiện nhận MIỄN PHÍ VẬN CHUYỂN 🎉</p>
                            <p class="text-xs text-emerald-600">Tiết kiệm 30.000₫ phí giao hàng toàn quốc.</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Main Cart Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                {{-- Left Column: Items List (8 cols) --}}
                <div class="lg:col-span-8 space-y-4">
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-4 sm:p-6 shadow-sm divide-y divide-[#efe8e3]">
                        @foreach($cartItems as $item)
                            <div class="py-5 first:pt-0 last:pb-0 flex flex-col sm:flex-row gap-4 sm:gap-5 items-start sm:items-center justify-between">
                                <div class="flex items-center gap-4 flex-1 min-w-0">
                                    {{-- Product Image --}}
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="flex-shrink-0 w-20 h-20 sm:w-24 sm:h-24 rounded-2xl overflow-hidden border border-[#efe8e3] bg-[#faf7f4] group">
                                        @if($item->product->image_url)
                                            <img src="{{ $item->product->image_url }}"
                                                 alt="{{ $item->product->name }}"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-[#c9a9a6]">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </a>

                                    {{-- Title & Variant --}}
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('products.show', $item->product->slug) }}"
                                           class="font-medium text-[#2c2a28] hover:text-[#b8847e] transition-colors text-sm sm:text-base line-clamp-2 leading-snug">
                                            {{ $item->product->name }}
                                        </a>

                                        @if(!empty($item->variant_id) && !empty($item->variant))
                                            <span class="inline-block text-[11px] font-medium text-[#b8847e] bg-[#faf7f4] border border-[#efe8e3] px-2.5 py-0.5 rounded-lg mt-1.5">
                                                Phân loại: {{ $item->variant->label() }}
                                            </span>
                                        @endif

                                        <div class="mt-2 flex items-baseline gap-2">
                                            <span class="font-serif font-bold text-sm sm:text-base text-[#b8847e]">
                                                {{ number_format($item->price, 0, ',', '.') }}₫
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Quantity & Subtotal Row --}}
                                <div class="flex items-center justify-between sm:justify-end gap-5 w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-t-0 border-[#efe8e3]">
                                    {{-- Quantity Stepper --}}
                                    <div class="flex items-center bg-[#faf7f4] border border-[#efe8e3] rounded-xl p-0.5">
                                        <form action="{{ route('cart.update') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="rowId" value="{{ $item->id }}">
                                            <input type="hidden" name="quantity" value="{{ max($item->quantity - 1, 1) }}">
                                            <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#2c2a28] hover:bg-white hover:text-[#b8847e] transition-colors font-bold text-sm">−</button>
                                        </form>
                                        <span class="w-8 text-center text-xs sm:text-sm font-bold text-[#2c2a28]">{{ $item->quantity }}</span>
                                        <form action="{{ route('cart.update') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="rowId" value="{{ $item->id }}">
                                            <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                            <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#2c2a28] hover:bg-white hover:text-[#b8847e] transition-colors font-bold text-sm">+</button>
                                        </form>
                                    </div>

                                    {{-- Line Total --}}
                                    <div class="text-right min-w-[90px]">
                                        <span class="text-xs text-[#9a9490] block sm:hidden">Thành tiền:</span>
                                        <span class="font-serif font-bold text-base text-[#2c2a28]">
                                            {{ number_format($item->quantity * $item->price, 0, ',', '.') }}₫
                                        </span>
                                    </div>

                                    {{-- Remove Button --}}
                                    <form action="{{ route('cart.remove') }}" method="POST"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')">
                                        @csrf
                                        <input type="hidden" name="rowId" value="{{ $item->id }}">
                                        <button type="submit" class="text-[#9a9490] hover:text-rose-600 transition-colors p-2 rounded-xl hover:bg-rose-50" title="Xóa khỏi giỏ hàng">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Bottom actions bar --}}
                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('products.index') }}"
                           class="text-xs sm:text-sm font-semibold text-[#b8847e] hover:text-[#a6736d] flex items-center gap-1.5 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Chọn thêm sản phẩm khác
                        </a>

                        <form action="{{ route('cart.clear') }}" method="POST"
                              onsubmit="return confirm('Bạn có chắc chắn muốn xóa toàn bộ giỏ hàng?')">
                            @csrf
                            <button type="submit" class="text-xs sm:text-sm font-semibold text-rose-500 hover:text-rose-700 flex items-center gap-1.5 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Xóa tất cả
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Right Column: Order Summary (4 cols) --}}
                <div class="lg:col-span-4">
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-7 shadow-sm sticky top-28 space-y-6">
                        <h2 class="font-serif text-xl font-bold text-[#2c2a28]">Tóm tắt đơn hàng</h2>

                        {{-- Calculation Breakdown --}}
                        <div class="space-y-3.5 text-xs sm:text-sm">
                            <div class="flex justify-between items-center text-[#6b6560]">
                                <span>Tạm tính ({{ $cartItems->sum('quantity') }} món)</span>
                                <span class="font-semibold text-[#2c2a28]">{{ number_format($cartTotal, 0, ',', '.') }}₫</span>
                            </div>

                            <div class="flex justify-between items-center text-[#6b6560]">
                                <span class="flex items-center gap-1">
                                    Phí vận chuyển ước tính
                                    <span class="text-[10px] text-[#9a9490]">(Tiêu chuẩn)</span>
                                </span>
                                @if($subtotal >= $freeShippingThreshold)
                                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full text-xs">Miễn phí</span>
                                @else
                                    <span class="font-semibold text-[#2c2a28]">từ {{ number_format($lowestShippingFee, 0, ',', '.') }}₫</span>
                                @endif
                            </div>

                            {{-- Coupon tip --}}
                            <div class="p-3 bg-[#faf7f4] border border-[#efe8e3] rounded-2xl flex items-start gap-2.5 text-xs text-[#9a9490]">
                                <svg class="w-4 h-4 text-[#b8847e] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                <span>Mã giảm giá và điểm thưởng sẽ được áp dụng tại bước thanh toán tiếp theo.</span>
                            </div>
                        </div>
                        {{-- Total line --}}
                        <div class="border-t border-[#efe8e3] pt-4">
                            <div class="flex justify-between items-baseline mb-1">
                                <span class="text-sm font-bold text-[#2c2a28]">Tổng thanh toán <span class="text-xs font-normal text-[#9a9490]">(chưa gồm ship)</span></span>
                                <span class="font-serif text-2xl sm:text-3xl font-bold text-[#b8847e]">
                                    {{ number_format($cartTotal, 0, ',', '.') }}₫
                                </span>
                            </div>
                            <p class="text-[11px] text-[#9a9490] text-right">
                                {{ $subtotal >= $freeShippingThreshold ? 'Đơn được freeship toàn quốc.' : 'Phí ship tính theo tỉnh/thành ở bước thanh toán.' }}
                            </p>
                        </div>

                        {{-- Checkout CTA --}}
                        <a href="{{ route('checkout.index') }}"
                           class="block w-full bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white text-center py-4 rounded-2xl font-bold hover:shadow-lg hover:shadow-[#b8847e]/30 transition-all text-sm btn-shine">
                            Tiến hành thanh toán
                        </a>

                        {{-- Security & Payment Badges --}}
                        <div class="pt-4 border-t border-[#efe8e3] space-y-3">
                            <div class="flex items-center justify-center gap-3 text-xs text-[#9a9490]">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Thanh toán bảo mật
                                </span>
                                <span>•</span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Đổi trả 7 ngày
                                </span>
                            </div>

                            <div class="flex items-center justify-center gap-2 pt-1">
                                <span class="text-[10px] font-bold text-[#6b6560] bg-[#faf7f4] border border-[#efe8e3] px-2.5 py-1 rounded-md">COD (Tiền mặt)</span>
                                <span class="text-[10px] font-bold text-[#6b6560] bg-[#faf7f4] border border-[#efe8e3] px-2.5 py-1 rounded-md">VNPAY QR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @else
            {{-- Empty Cart State --}}
            <div class="bg-white border border-[#efe8e3] rounded-3xl p-10 sm:p-16 text-center max-w-2xl mx-auto shadow-sm animate-fade-in-up">
                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-[#faf7f4] to-[#f0e8e4] border border-[#efe8e3] rounded-full flex items-center justify-center text-[#b8847e] shadow-inner">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28] mb-2">Giỏ hàng của bạn đang trống</h2>
                <p class="text-xs sm:text-sm text-[#9a9490] max-w-md mx-auto mb-8 leading-relaxed">
                    Khám phá ngay những thiết kế mới nhất vừa ra mắt và ưu đãi hấp dẫn đang chờ đón bạn tại SocialShop.
                </p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-2 bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white px-8 py-4 rounded-2xl font-bold hover:shadow-lg hover:shadow-[#b8847e]/30 transition-all text-sm btn-shine">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Khám phá sản phẩm ngay
                </a>
            </div>
        @endif
    </div>
</x-layouts.app>
