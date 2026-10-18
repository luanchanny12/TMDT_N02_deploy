<x-layouts.app :title="'Đặt hàng thành công'">

    <div class="max-w-xl mx-auto px-4 py-12 md:py-20">
        <div class="bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-8 md:p-12 text-center animate-fade-in-up">

            {{-- Success Icon with soft pulse --}}
            <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-full flex items-center justify-center text-emerald-600 shadow-sm">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            {{-- Heading --}}
            <h1 class="font-serif text-2xl md:text-3xl font-bold text-[#2c2a28] mb-2">
                Cảm ơn bạn đã đặt hàng!
            </h1>
            <p class="text-[#9a9490] text-xs sm:text-sm mb-8 leading-relaxed max-w-sm mx-auto">
                Đơn hàng của bạn đã được tiếp nhận thành công. SocialShop sẽ chuẩn bị và giao hàng trong thời gian sớm nhất.
            </p>

            {{-- Order Code Card --}}
            <div class="bg-gradient-to-br from-[#faf7f4] to-[#f5f0ec] border border-[#efe8e3] rounded-2xl p-5 mb-8 shadow-inner">
                <p class="text-[11px] text-[#9a9490] uppercase tracking-wider font-semibold mb-1">Mã đơn hàng</p>
                <p class="font-serif text-2xl sm:text-3xl font-bold text-[#b8847e] tracking-wide">
                    #{{ $order->order_code }}
                </p>
            </div>

            {{-- Order Summary Details --}}
            <div class="text-left space-y-3.5 mb-8 p-5 bg-[#faf7f4]/40 border border-[#efe8e3] rounded-2xl text-xs sm:text-sm">
                <div class="flex justify-between items-center text-[#6b6560]">
                    <span>Tổng thanh toán</span>
                    <span class="font-serif font-bold text-base text-[#b8847e]">{{ number_format($order->total, 0, ',', '.') }}₫</span>
                </div>
                <div class="flex justify-between items-center text-[#6b6560]">
                    <span>Phương thức thanh toán</span>
                    <span class="font-bold text-[#2c2a28]">{{ strtoupper($order->payment_method) }}</span>
                </div>
                <div class="flex justify-between items-start text-[#6b6560] gap-4">
                    <span class="flex-shrink-0">Địa chỉ nhận hàng</span>
                    <span class="font-medium text-[#2c2a28] text-right max-w-[65%] leading-relaxed">{{ $order->shipping_address }}</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('orders.show', $order) }}"
                   class="flex-1 bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white px-6 py-3.5 rounded-2xl font-bold text-xs sm:text-sm hover:shadow-lg hover:shadow-[#b8847e]/30 transition-all text-center btn-shine shadow-sm">
                    Xem chi tiết đơn hàng
                </a>
                <a href="{{ route('products.index') }}"
                   class="flex-1 bg-[#faf7f4] border border-[#efe8e3] text-[#2c2a28] hover:text-[#b8847e] px-6 py-3.5 rounded-2xl font-semibold text-xs sm:text-sm hover:bg-white transition-all text-center shadow-sm">
                    Tiếp tục mua sắm
                </a>
            </div>
        </div>
    </div>

</x-layouts.app>
