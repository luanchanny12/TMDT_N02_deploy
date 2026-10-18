<x-layouts.app :title="'Đơn hàng của tôi'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">

        {{-- Page Header --}}
        <div class="flex items-center justify-between flex-wrap gap-4 mb-8 pb-5 border-b border-[#efe8e3]">
            <div>
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e] shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </span>
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">
                        Đơn hàng của tôi
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-[#9a9490] mt-1.5">
                    Quản lý và theo dõi hành trình giao nhận tất cả các đơn hàng của bạn
                </p>
            </div>

            @if($orders->count())
                <a href="{{ route('products.index') }}"
                   class="text-xs sm:text-sm font-semibold text-[#b8847e] hover:text-[#a6736d] flex items-center gap-1.5 transition-colors bg-[#faf7f4] border border-[#efe8e3] px-4 py-2 rounded-xl shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Khám phá thêm sản phẩm
                </a>
            @endif
        </div>

        @if($orders->count())
            <div class="space-y-5">
                @foreach($orders as $order)
                    <div class="bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-5 sm:p-7 hover:border-[#c9a9a6] hover-lift transition-all">
                        {{-- Card Header: Code, Date, Status --}}
                        <div class="flex items-center justify-between flex-wrap gap-3 pb-4 mb-4 border-b border-[#efe8e3]">
                            <div>
                                <span class="font-serif font-bold text-base sm:text-lg text-[#2c2a28] tracking-wide">
                                    #{{ $order->order_code }}
                                </span>
                                <span class="text-xs text-[#9a9490] ml-2">
                                    • Đặt ngày {{ $order->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>

                            {{-- Status Badge --}}
                            <span class="px-3.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1.5
                                @if($order->status === 'pending') bg-amber-50 text-amber-800 border border-amber-200
                                @elseif($order->status === 'confirmed') bg-blue-50 text-blue-800 border border-blue-200
                                @elseif($order->status === 'shipping') bg-rose-50 text-rose-800 border border-rose-200
                                @elseif($order->status === 'delivered') bg-emerald-50 text-emerald-800 border border-emerald-200
                                @elseif($order->status === 'cancelled') bg-red-50 text-red-800 border border-red-200
                                @else bg-gray-50 text-gray-800 border border-gray-200
                                @endif">
                                <span class="w-1.5 h-1.5 rounded-full
                                    @if($order->status === 'pending') bg-amber-500
                                    @elseif($order->status === 'confirmed') bg-blue-500
                                    @elseif($order->status === 'shipping') bg-rose-500 animate-pulse
                                    @elseif($order->status === 'delivered') bg-emerald-500
                                    @elseif($order->status === 'cancelled') bg-red-500
                                    @else bg-gray-400
                                    @endif"></span>
                                @switch($order->status)
                                    @case('pending') Chờ xử lý @break
                                    @case('confirmed') Đã xác nhận @break
                                    @case('shipping') Đang giao @break
                                    @case('delivered') Đã giao @break
                                    @case('cancelled') Đã hủy @break
                                    @default {{ $order->status }}
                                @endswitch
                            </span>
                        </div>

                        {{-- Items Preview --}}
                        <div class="space-y-3 py-1">
                            @foreach($order->items->take(2) as $item)
                                <div class="flex items-center gap-3.5">
                                    @if($item->product && $item->product->image_url)
                                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product_name }}"
                                             class="w-14 h-14 rounded-2xl object-cover border border-[#efe8e3] flex-shrink-0">
                                    @else
                                        <div class="w-14 h-14 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#c9a9a6] text-xs font-bold flex-shrink-0">SP</div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-xs sm:text-sm text-[#2c2a28] truncate">{{ $item->product_name ?? $item->product->name ?? 'Sản phẩm' }}</p>
                                        <p class="text-[11px] text-[#9a9490] mt-0.5">Số lượng: x{{ $item->quantity }} · Đơn giá: {{ number_format($item->price, 0, ',', '.') }} ₫</p>
                                    </div>
                                    <span class="font-serif font-bold text-xs sm:text-sm text-[#2c2a28] flex-shrink-0">
                                        {{ number_format($item->subtotal ?? ($item->quantity * $item->price), 0, ',', '.') }} ₫
                                    </span>
                                </div>
                            @endforeach

                            @if($order->items->count() > 2)
                                <p class="text-xs text-[#9a9490] italic pl-1">
                                    + {{ $order->items->count() - 2 }} sản phẩm khác trong đơn hàng này...
                                </p>
                            @endif
                        </div>

                        {{-- Card Footer: Total, Actions --}}
                        <div class="mt-4 pt-4 border-t border-[#efe8e3] flex items-center justify-between flex-wrap gap-4">
                            <div>
                                <span class="text-xs text-[#9a9490] block">Tổng thanh toán ({{ $order->items->count() }} sản phẩm)</span>
                                <span class="font-serif text-lg sm:text-xl font-bold text-[#b8847e]">
                                    {{ number_format($order->total, 0, ',', '.') }} ₫
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                @if($order->status === 'delivered')
                                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
                                        ✓ Hoàn tất
                                    </span>
                                @endif

                                <a href="{{ route('orders.show', $order) }}"
                                   class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-[#b8847e] to-[#a6736d] px-5 py-2.5 rounded-xl hover:shadow-md transition-all shadow-sm">
                                    <span>Xem chi tiết</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if(method_exists($orders, 'links'))
                <div class="mt-10">
                    {{ $orders->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="bg-white border border-[#efe8e3] rounded-3xl p-10 sm:p-16 text-center max-w-2xl mx-auto shadow-sm animate-fade-in-up">
                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-[#faf7f4] to-[#f0e8e4] border border-[#efe8e3] rounded-full flex items-center justify-center text-[#b8847e] shadow-inner">
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28] mb-2">Chưa có đơn hàng</h2>
                <p class="text-xs sm:text-sm text-[#9a9490] max-w-md mx-auto mb-8 leading-relaxed">
                    Bạn chưa đặt đơn hàng nào. Hãy mua sắm ngay để trải nghiệm những thiết kế thời trang tinh tế từ SocialShop!
                </p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-2 bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white px-8 py-4 rounded-2xl font-bold hover:shadow-lg hover:shadow-[#b8847e]/30 transition-all text-sm btn-shine">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Mua sắm ngay
                </a>
            </div>
        @endif
    </div>
</x-layouts.app>
