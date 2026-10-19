<x-layouts.app :title="'Xác nhận đơn hàng'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">

        {{-- Stepper Progress Header --}}
        <div class="max-w-3xl mx-auto mb-10">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-[#efe8e3] z-0 rounded-full"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-2/3 h-1 bg-gradient-to-r from-[#b8847e] to-[#c9a9a6] z-0 rounded-full"></div>

                {{-- Step 1 (Completed) --}}
                <div class="relative z-10 flex flex-col items-center">
                    <a href="{{ route('checkout.index') }}" class="w-11 h-11 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-sm shadow-md hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </a>
                    <span class="text-xs font-medium text-[#9a9490] mt-2.5">1. Thông tin giao hàng</span>
                </div>

                {{-- Step 2 (Active) --}}
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#b8847e] to-[#a6736d] text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-[#b8847e]/20">
                        2
                    </div>
                    <span class="text-xs font-bold text-[#b8847e] mt-2.5">2. Xác nhận đơn hàng</span>
                </div>

                {{-- Step 3 --}}
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-11 h-11 rounded-full bg-white border-2 border-[#efe8e3] text-[#9a9490] flex items-center justify-center font-bold text-sm shadow-sm">
                        3
                    </div>
                    <span class="text-xs font-medium text-[#9a9490] mt-2.5">3. Hoàn tất</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 mb-8">
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">Xác nhận đơn hàng</h1>
            <span class="text-xs font-bold bg-[#faf7f4] text-[#b8847e] border border-[#efe8e3] px-3 py-1 rounded-full">Bước 2/2</span>
        </div>

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('checkout.confirm') }}" method="POST"
              x-data="{ submitting: false }" @submit="if (submitting) { $event.preventDefault() } submitting = true">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-7 space-y-6">

                    {{-- Shipping Info Card --}}
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 shadow-sm">
                        <div class="flex items-center justify-between pb-4 mb-5 border-b border-[#efe8e3]">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                                <h2 class="font-serif text-lg font-bold text-[#2c2a28]">Thông tin giao hàng</h2>
                            </div>
                            <a href="{{ route('checkout.index') }}" class="text-xs font-bold text-[#b8847e] hover:underline flex items-center gap-1">
                                Sửa
                            </a>
                        </div>
                        <dl class="space-y-3 text-xs sm:text-sm">
                            <div class="flex gap-4">
                                <dt class="w-32 text-[#9a9490] flex-shrink-0">Họ tên người nhận:</dt>
                                <dd class="font-semibold text-[#2c2a28]">{{ $checkout['shipping_name'] }}</dd>
                            </div>
                            <div class="flex gap-4">
                                <dt class="w-32 text-[#9a9490] flex-shrink-0">Số điện thoại:</dt>
                                <dd class="font-semibold text-[#2c2a28]">{{ $checkout['shipping_phone'] }}</dd>
                            </div>
                            <div class="flex gap-4">
                                <dt class="w-32 text-[#9a9490] flex-shrink-0">Địa chỉ giao hàng:</dt>
                                <dd class="font-medium text-[#2c2a28] leading-relaxed">{{ $checkout['shipping_address'] }}@if(!empty($checkout['shipping_province'])), {{ $checkout['shipping_province'] }}@endif</dd>
                            </div>
                            @if(!empty($checkout['shipping_notes']))
                                <div class="flex gap-4">
                                    <dt class="w-32 text-[#9a9490] flex-shrink-0">Ghi chú:</dt>
                                    <dd class="text-[#6b6560] italic">{{ $checkout['shipping_notes'] }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Payment Method Review Card --}}
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 shadow-sm">
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-[#efe8e3]">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </span>
                                <h2 class="font-serif text-lg font-bold text-[#2c2a28]">Phương thức thanh toán</h2>
                            </div>
                            <a href="{{ route('checkout.index') }}" class="text-xs font-bold text-[#b8847e] hover:underline flex items-center gap-1">
                                Sửa
                            </a>
                        </div>
                        <p class="text-sm font-semibold text-[#2c2a28] flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#b8847e]"></span>
                            @if($checkout['payment_method'] === 'vnpay')
                                VNPay — Thanh toán trực tuyến
                            @else
                                Thanh toán khi nhận hàng (COD)
                            @endif
                        </p>
                    </div>

                    {{-- Order Items Review Card --}}
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 shadow-sm">
                        <h2 class="font-serif text-lg font-bold text-[#2c2a28] mb-4 pb-3 border-b border-[#efe8e3]">
                            Sản phẩm ({{ $cartItems->sum('quantity') }})
                        </h2>
                        <div class="space-y-4 divide-y divide-[#efe8e3]">
                            @foreach($cartItems as $item)
                                <div class="flex items-center gap-4 pt-3 first:pt-0">
                                    @if($item->product->image_url)
                                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}"
                                             class="w-16 h-16 object-cover rounded-2xl border border-[#efe8e3] flex-shrink-0">
                                    @else
                                        <div class="w-16 h-16 bg-[#faf7f4] rounded-2xl border border-[#efe8e3] flex items-center justify-center text-[#c9a9a6] text-xs font-bold flex-shrink-0">SP</div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs sm:text-sm font-bold text-[#2c2a28] truncate">{{ $item->product->name }}</p>
                                        <p class="text-xs text-[#9a9490] mt-0.5">Số lượng: x{{ $item->quantity }}</p>
                                    </div>
                                    <span class="font-serif font-bold text-sm sm:text-base text-[#2c2a28]">
                                        {{ number_format($item->price * $item->quantity, 0, ',', '.') }} ₫
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Terms Agreement Checkbox --}}
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 shadow-sm">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="agree_terms" value="1" required
                                   class="mt-1 h-4 w-4 text-[#b8847e] border-[#efe8e3] rounded focus:ring-[#b8847e]">
                            <span class="text-xs sm:text-sm text-[#6b6560] leading-relaxed">
                                Tôi đã đọc và đồng ý với
                                <a href="{{ route('pages.terms') }}" target="_blank" rel="noopener" class="text-[#b8847e] font-semibold underline">Điều Kiện Giao Dịch Chung</a>
                                và
                                <a href="{{ route('pages.privacy') }}" target="_blank" rel="noopener" class="text-[#b8847e] font-semibold underline">Chính sách bảo mật</a>
                                <span class="text-red-500">*</span>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Right 5 cols: Order Summary --}}
                <div class="lg:col-span-5">
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-7 shadow-sm sticky top-28 space-y-5">
                        <h2 class="font-serif text-xl font-bold text-[#2c2a28] pb-4 border-b border-[#efe8e3]">Tổng đơn hàng</h2>

                        <div class="space-y-3 text-xs sm:text-sm">
                            <div class="flex justify-between text-[#6b6560]">
                                <span>Tạm tính</span>
                                <span class="font-medium text-[#2c2a28]">{{ number_format($cartTotal, 0, ',', '.') }} ₫</span>
                            </div>

                            @if($discount > 0)
                                <div class="flex justify-between text-emerald-700">
                                    <span>Giảm giá{{ $couponCode ? ' ('.$couponCode.')' : '' }}</span>
                                    <span class="font-bold">-{{ number_format($discount, 0, ',', '.') }} ₫</span>
                                </div>
                            @endif

                            <div class="flex justify-between text-[#6b6560]">
                                <span>Phí vận chuyển</span>
                                @if($shippingFee > 0)
                                    <span class="font-medium text-[#2c2a28]">{{ number_format($shippingFee, 0, ',', '.') }} ₫</span>
                                @else
                                    <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full text-xs">Miễn phí</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 -mt-2" data-testid="shipping-hint">
                                {{ $shippingRule['zone'] ?? 'Phí mặc định' }} · phí {{ number_format($shippingRule['fee'], 0, ',', '.') }} ₫,
                                @if($shippingFee > 0)
                                    mua thêm {{ number_format(max(0, $shippingRule['threshold'] - $cartTotal), 0, ',', '.') }} ₫ để được miễn phí.
                                @else
                                    miễn phí cho đơn từ {{ number_format($shippingRule['threshold'], 0, ',', '.') }} ₫.
                                @endif
                            </p>

                            @if($taxAmount > 0)
                                <div class="flex justify-between text-[#6b6560]">
                                    <span>{{ $taxLabel }} ({{ rtrim(rtrim(number_format($taxRate, 2, ',', ''), '0'), ',') }}%)</span>
                                    <span class="font-medium text-[#2c2a28]">{{ number_format($taxAmount, 0, ',', '.') }} ₫</span>
                                </div>
                            @endif

                            <div class="flex justify-between items-baseline border-t border-[#efe8e3] pt-3 text-base">
                                <span class="font-bold text-[#2c2a28]">Tổng cộng</span>
                                <span class="font-serif text-2xl font-bold text-[#b8847e]">{{ number_format($total, 0, ',', '.') }} ₫</span>
                            </div>
                            <p class="text-xs text-[#9a9490] mt-2" data-testid="quote-expiry">
                                Giá trên được giữ đến {{ $quoteExpiresAt->format('H:i') }}. Nếu giá, khuyến mãi, phí ship hoặc thuế thay đổi trước khi bạn đặt,
                                hệ thống sẽ báo để bạn xác nhận lại — bạn chỉ thanh toán đúng số tiền đã xem.
                            </p>
                        </div>

                        <button type="submit" :disabled="submitting"
                                class="w-full bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white py-4 rounded-2xl font-bold hover:shadow-lg hover:shadow-[#b8847e]/30 transition-all text-sm btn-shine mt-4 disabled:opacity-60 disabled:cursor-wait">
                            <span x-text="submitting ? 'Đang xử lý...' : 'Đặt hàng'">Đặt hàng</span>
                        </button>

                        <a href="{{ route('checkout.index') }}"
                           class="block text-center text-xs text-[#9a9490] mt-3 hover:text-[#b8847e] transition-colors">
                            ← Quay lại nhập thông tin
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app>
