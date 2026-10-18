<x-layouts.app :title="$cartItems->isNotEmpty() ? 'Giỏ hàng' : 'Giỏ hàng trống'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <h1 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
            <svg class="w-7 h-7 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
            </svg>
            Giỏ hàng
            <span class="text-sm font-normal text-gray-500">({{ $cartItems->sum('quantity') }} sản phẩm)</span>
        </h1>

        @if($cartItems->isNotEmpty())

            {{-- Free Shipping Progress --}}
            @php
                $subtotal = $cartTotal;
                $pricing = app(\App\Services\Order\PricingService::class);
                $freeShippingThreshold = max(1, $pricing->nationwideFreeShippingThreshold());
                $lowestShippingFee = $pricing->lowestShippingFee();
                $progress = min(($subtotal / $freeShippingThreshold) * 100, 100);
                $remaining = max($freeShippingThreshold - $subtotal, 0);
            @endphp
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6 animate-fade-in">
                @if($subtotal < $freeShippingThreshold)
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Mua thêm <span class="text-green-600">{{ number_format($remaining, 0, ',', '.') }}₫</span> để được miễn phí vận chuyển!</p>
                        </div>
                    </div>
                    <div class="shipping-progress">
                        <div class="shipping-progress-bar" style="width: {{ $progress }}%"></div>
                    </div>
                @else
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-green-700">Bạn được miễn phí vận chuyển! 🎉</p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Cart Items --}}
                <div class="lg:col-span-2 space-y-3">
                    @foreach($cartItems as $item)
                        <div class="bg-white rounded-xl shadow-sm p-4 animate-fade-in-up">
                            <div class="flex gap-4">
                                {{-- Image --}}
                                <a href="{{ route('products.show', $item->product->slug) }}" class="flex-shrink-0">
                                    @if($item->product->image_url)
                                        <img src="{{ $item->product->image_url }}"
                                             alt="{{ $item->product->name }}" class="h-20 w-20 md:h-24 md:w-24 object-cover rounded-xl">
                                    @else
                                        <div class="h-20 w-20 md:h-24 md:w-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </a>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <a href="{{ route('products.show', $item->product->slug) }}"
                                           class="font-semibold text-gray-900 hover:text-[#b8847e] transition-colors line-clamp-2 text-sm">
                                            {{ $item->product->name }}
                                            @if(!empty($item->variant_id) && !empty($item->variant))
                                                <span class="block text-xs text-[#9a9490] font-normal mt-0.5">{{ $item->variant->label() }}</span>
                                            @endif
                                        </a>
                                        <form action="{{ route('cart.remove') }}" method="POST" class="flex-shrink-0"
                                              onsubmit="return confirm('Xóa sản phẩm này?')">
                                            @csrf
                                            <input type="hidden" name="rowId" value="{{ $item->id }}">
                                            <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between gap-3">
                                        {{-- Price --}}
                                        <span class="text-[#b8847e] font-bold text-sm">
                                            {{ number_format($item->price, 0, ',', '.') }}₫
                                        </span>

                                        {{-- Quantity --}}
                                        <div class="flex items-center gap-0.5 bg-gray-50 rounded-lg border border-gray-200">
                                            <form action="{{ route('cart.update') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="rowId" value="{{ $item->id }}">
                                                <input type="hidden" name="quantity" value="{{ max($item->quantity - 1, 1) }}">
                                                <button type="submit" class="qty-btn rounded-l-lg border-0 w-8 h-8 text-xs">−</button>
                                            </form>
                                            <span class="w-8 text-center text-sm font-bold">{{ $item->quantity }}</span>
                                            <form action="{{ route('cart.update') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="rowId" value="{{ $item->id }}">
                                                <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                                <button type="submit" class="qty-btn rounded-r-lg border-0 w-8 h-8 text-xs">+</button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Subtotal --}}
                                    <div class="mt-2 text-right">
                                        <span class="text-xs text-gray-400">Thành tiền: </span>
                                        <span class="text-sm font-bold text-gray-900">{{ number_format($item->quantity * $item->price, 0, ',', '.') }}₫</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('products.index') }}"
                           class="text-[#b8847e] hover:text-[#a6736d] text-sm font-semibold flex items-center gap-1 group">
                            <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Tiếp tục mua sắm
                        </a>
                        <form action="{{ route('cart.clear') }}" method="POST"
                              onsubmit="return confirm('Bạn muốn xóa toàn bộ giỏ hàng?')">
                            @csrf
                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-semibold flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Xóa tất cả
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm p-6 sticky top-24 animate-fade-in-up">
                        <h2 class="text-lg font-bold text-gray-900 mb-5">Tóm tắt đơn hàng</h2>
                        <div class="space-y-3 mb-5">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tạm tính ({{ $cartItems->sum('quantity') }} SP)</span>
                                <span class="font-semibold text-gray-900">{{ number_format($cartTotal, 0, ',', '.') }}₫</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Phí vận chuyển</span>
                                @if($subtotal >= $freeShippingThreshold)
                                    <span class="font-semibold text-green-600">Miễn phí</span>
                                @else
                                    <span class="font-semibold text-gray-900">từ {{ number_format($lowestShippingFee, 0, ',', '.') }}₫</span>
                                @endif
                            </div>
                        </div>
                        <div class="border-t border-gray-100 pt-4 mb-5">
                            <div class="flex justify-between items-baseline">
                                <span class="text-base font-bold text-gray-900">Tiền hàng <span class="text-xs font-normal text-gray-500">(chưa gồm ship)</span></span>
                                <span class="text-xl font-extrabold text-[#b8847e]">
                                    {{ number_format($cartTotal, 0, ',', '.') }}₫
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $subtotal >= $freeShippingThreshold ? 'Đơn được freeship toàn quốc.' : 'Phí ship tính theo tỉnh/thành ở bước thanh toán.' }}
                            </p>
                        </div>

                        <a href="{{ route('checkout.index') }}"
                           class="block w-full bg-[#b8847e] text-white text-center py-3.5 rounded-xl font-bold hover:bg-[#a6736d] transition-all shadow-lg shadow-[#e8c4c4] btn-shine">
                            Tiến hành thanh toán
                        </a>

                        {{-- Payment Methods --}}
                        <div class="mt-4 text-center">
                            <p class="text-xs text-gray-400 mb-2">Hỗ trợ thanh toán:</p>
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="bg-gray-100 text-[10px] font-bold px-2 py-1 rounded text-gray-600">COD</span>
                                <span class="bg-gray-100 text-[10px] font-bold px-2 py-1 rounded text-gray-600">VNPAY</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @else
            {{-- Empty Cart --}}
            <div class="text-center py-20 animate-fade-in-up">
                <div class="w-28 h-28 mx-auto mb-6 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center">
                    <svg class="h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Giỏ hàng trống</h2>
                <p class="text-gray-500 mb-8 max-w-sm mx-auto">Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm tại SocialShop</p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-2 bg-[#b8847e] text-white px-8 py-3.5 rounded-full font-bold hover:bg-[#a6736d] transition-all shadow-lg shadow-[#e8c4c4] btn-shine">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Mua sắm ngay
                </a>
            </div>
        @endif
    </div>
</x-layouts.app>
