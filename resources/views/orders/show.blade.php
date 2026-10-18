<x-layouts.app :title="'Đơn hàng ' . $order->order_code">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">

        {{-- Page Header --}}
        <div class="flex items-center justify-between flex-wrap gap-4 mb-8 pb-5 border-b border-[#efe8e3]">
            <div>
                <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-[#9a9490] hover:text-[#b8847e] flex items-center gap-1 mb-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Quay lại danh sách đơn hàng
                </a>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">
                    Đơn hàng #{{ $order->order_code }}
                </h1>
                <p class="text-xs sm:text-sm text-[#9a9490] mt-1">
                    Đặt ngày {{ $order->created_at->format('d/m/Y H:i') }}
                </p>
            </div>

            {{-- Status Pill --}}
            <span class="px-4 py-1.5 rounded-full text-xs font-bold inline-flex items-center gap-2
                @if($order->status === 'pending') bg-amber-50 text-amber-800 border border-amber-200
                @elseif($order->status === 'confirmed') bg-blue-50 text-blue-800 border border-blue-200
                @elseif($order->status === 'shipping') bg-rose-50 text-rose-800 border border-rose-200
                @elseif($order->status === 'delivered') bg-emerald-50 text-emerald-800 border border-emerald-200
                @elseif($order->status === 'cancelled') bg-red-50 text-red-800 border border-red-200
                @else bg-gray-50 text-gray-800 border border-gray-200
                @endif">
                <span class="w-2 h-2 rounded-full
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

        {{-- Order Progress Timeline Stepper --}}
        @if($order->status !== 'cancelled')
            <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 shadow-sm mb-8">
                <h3 class="font-serif text-lg font-bold text-[#2c2a28] mb-6">Tiến độ đơn hàng</h3>
                <div class="grid grid-cols-4 relative text-center">
                    {{-- Timeline connector line --}}
                    <div class="absolute left-[12%] right-[12%] top-5 h-1 bg-[#efe8e3] z-0 rounded-full">
                        <div class="h-full bg-gradient-to-r from-[#b8847e] to-[#a6736d] rounded-full transition-all duration-500"
                             style="width:
                                @if($order->status === 'pending') 0%
                                @elseif($order->status === 'confirmed') 33%
                                @elseif($order->status === 'shipping') 66%
                                @elseif($order->status === 'delivered') 100%
                                @endif"></div>
                    </div>

                    {{-- Step 1: Đã đặt --}}
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#b8847e] to-[#a6736d] text-white flex items-center justify-center font-bold text-xs shadow-md">
                            ✓
                        </div>
                        <span class="text-xs font-bold text-[#2c2a28] mt-2.5">Đã đặt hàng</span>
                        <span class="text-[10px] text-[#9a9490]">{{ $order->created_at->format('H:i d/m') }}</span>
                    </div>

                    {{-- Step 2: Đã xác nhận --}}
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full text-xs font-bold shadow-md flex items-center justify-center
                            {{ in_array($order->status, ['confirmed', 'shipping', 'delivered']) ? 'bg-gradient-to-br from-[#b8847e] to-[#a6736d] text-white' : 'bg-white border-2 border-[#efe8e3] text-[#9a9490]' }}">
                            {{ in_array($order->status, ['confirmed', 'shipping', 'delivered']) ? '✓' : '2' }}
                        </div>
                        <span class="text-xs font-bold {{ in_array($order->status, ['confirmed', 'shipping', 'delivered']) ? 'text-[#2c2a28]' : 'text-[#9a9490]' }} mt-2.5">
                            Đã xác nhận
                        </span>
                        <span class="text-[10px] text-[#9a9490]">Chuẩn bị hàng</span>
                    </div>

                    {{-- Step 3: Đang giao --}}
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full text-xs font-bold shadow-md flex items-center justify-center
                            {{ in_array($order->status, ['shipping', 'delivered']) ? 'bg-gradient-to-br from-[#b8847e] to-[#a6736d] text-white' : 'bg-white border-2 border-[#efe8e3] text-[#9a9490]' }}
                            {{ $order->status === 'shipping' ? 'ring-4 ring-[#b8847e]/20' : '' }}">
                            {{ in_array($order->status, ['shipping', 'delivered']) ? '🚚' : '3' }}
                        </div>
                        <span class="text-xs font-bold {{ in_array($order->status, ['shipping', 'delivered']) ? 'text-[#b8847e]' : 'text-[#9a9490]' }} mt-2.5">
                            Đang giao
                        </span>
                        <span class="text-[10px] text-[#9a9490]">Đang vận chuyển</span>
                    </div>

                    {{-- Step 4: Đã giao --}}
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full text-xs font-bold shadow-md flex items-center justify-center
                            {{ $order->status === 'delivered' ? 'bg-emerald-500 text-white' : 'bg-white border-2 border-[#efe8e3] text-[#9a9490]' }}">
                            {{ $order->status === 'delivered' ? '✓' : '4' }}
                        </div>
                        <span class="text-xs font-bold {{ $order->status === 'delivered' ? 'text-emerald-700' : 'text-[#9a9490]' }} mt-2.5">
                            Đã giao
                        </span>
                        <span class="text-[10px] text-[#9a9490]">Nhận thành công</span>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            {{-- Left 7 cols: Order Items --}}
            <div class="lg:col-span-7 space-y-6">
                <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 shadow-sm">
                    <h2 class="font-serif text-xl font-bold text-[#2c2a28] pb-4 mb-5 border-b border-[#efe8e3]">
                        Danh sách sản phẩm ({{ $order->items->count() }})
                    </h2>

                    <div class="divide-y divide-[#efe8e3]">
                        @foreach($order->items as $item)
                            <div class="py-4 first:pt-0 last:pb-0 space-y-3">
                                <div class="flex items-center gap-4">
                                    @if($item->product && $item->product->image_url)
                                        <img src="{{ $item->product->image_url }}"
                                             alt="{{ $item->product_name }}"
                                             class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-2xl border border-[#efe8e3] flex-shrink-0">
                                    @else
                                        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-[#faf7f4] rounded-2xl border border-[#efe8e3] flex items-center justify-center text-[#c9a9a6] text-xs font-bold flex-shrink-0">SP</div>
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-sm sm:text-base text-[#2c2a28] truncate">
                                            {{ $item->product_name ?? $item->product->name ?? 'Sản phẩm đã xóa' }}
                                        </p>
                                        <p class="text-xs text-[#9a9490] mt-1">
                                            Đơn giá: {{ number_format($item->price, 0, ',', '.') }} ₫ · Số lượng: x{{ $item->quantity }}
                                        </p>
                                    </div>

                                    <div class="text-right flex-shrink-0">
                                        <span class="text-xs text-[#9a9490] block sm:hidden">Thành tiền</span>
                                        <span class="font-serif font-bold text-sm sm:text-base text-[#b8847e]">
                                            {{ number_format($item->subtotal ?? ($item->quantity * $item->price), 0, ',', '.') }} ₫
                                        </span>
                                    </div>
                                </div>

                                {{-- Review link when delivered --}}
                                @if($order->status === 'delivered' && $item->product)
                                    <div class="flex justify-end pt-1">
                                        <a href="{{ route('products.show', $item->product) }}#reviews"
                                           class="inline-flex items-center gap-1.5 text-xs font-bold text-[#b8847e] bg-[#faf7f4] border border-[#efe8e3] px-3.5 py-1.5 rounded-xl hover:bg-[#b8847e] hover:text-white transition-all shadow-sm">
                                            ★ Đánh giá sản phẩm này
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Shipping Information Card --}}
                <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 shadow-sm">
                    <h3 class="font-serif text-lg font-bold text-[#2c2a28] pb-3 mb-4 border-b border-[#efe8e3]">Giao hàng</h3>
                    <div class="space-y-3 text-xs sm:text-sm">
                        <div class="flex gap-4">
                            <span class="text-[#9a9490] w-32 flex-shrink-0">Người nhận:</span>
                            <span class="font-bold text-[#2c2a28]">{{ $order->shipping_name }}</span>
                        </div>
                        <div class="flex gap-4">
                            <span class="text-[#9a9490] w-32 flex-shrink-0">Điện thoại:</span>
                            <span class="font-semibold text-[#2c2a28]">{{ $order->shipping_phone }}</span>
                        </div>
                        <div class="flex gap-4">
                            <span class="text-[#9a9490] w-32 flex-shrink-0">Địa chỉ:</span>
                            <p class="font-medium text-[#2c2a28] leading-relaxed">{{ $order->shipping_address }}@if($order->shipping_province), {{ $order->shipping_province }}@endif</p>
                        </div>
                        @if($order->note)
                            <div class="flex gap-4">
                                <span class="text-[#9a9490] w-32 flex-shrink-0">Ghi chú:</span>
                                <p class="text-[#6b6560] italic">{{ $order->note }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right 5 cols: Payment & Calculation & Cancel button --}}
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-7 shadow-sm sticky top-28 space-y-5">
                    <h3 class="font-serif text-xl font-bold text-[#2c2a28] pb-3 border-b border-[#efe8e3]">Thanh toán</h3>

                    <div class="space-y-3 text-xs sm:text-sm">
                        <div class="flex justify-between items-center text-[#6b6560]">
                            <span>Phương thức:</span>
                            <span class="font-bold text-[#2c2a28]">
                                @if($order->payment_method == 'cod') COD (Tiền mặt) @else VNPay Sandbox @endif
                            </span>
                        </div>

                        <div class="flex justify-between items-center text-[#6b6560]">
                            <span>Trạng thái thanh toán:</span>
                            @if($order->payment_status === 'paid')
                                <span class="font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full">Đã thanh toán</span>
                            @elseif($order->payment_status === 'failed')
                                <span class="font-bold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full">Thanh toán thất bại</span>
                            @elseif($order->payment_method === 'cod')
                                <span class="font-medium text-[#2c2a28]">Thanh toán khi nhận hàng</span>
                            @else
                                <span class="font-bold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full">Chưa thanh toán</span>
                            @endif
                        </div>

                        <div class="border-t border-[#efe8e3] pt-3 space-y-2.5">
                            <div class="flex justify-between text-[#6b6560]">
                                <span>Tạm tính:</span>
                                <span class="font-medium text-[#2c2a28]">{{ number_format($order->subtotal, 0, ',', '.') }} ₫</span>
                            </div>

                            @if($order->discount > 0)
                                <div class="flex justify-between text-emerald-700">
                                    <span>Giảm giá:</span>
                                    <span class="font-bold">-{{ number_format($order->discount, 0, ',', '.') }} ₫</span>
                                </div>
                            @endif

                            <div class="flex justify-between text-[#6b6560]">
                                <span>Phí vận chuyển:</span>
                                <span class="font-medium text-[#2c2a28]">{{ number_format($order->shipping_fee, 0, ',', '.') }} ₫</span>
                            </div>

                            @if($order->tax_amount > 0)
                                <div class="flex justify-between text-[#6b6560]">
                                    <span>Thuế VAT ({{ rtrim(rtrim(number_format($order->tax_rate, 2, ',', ''), '0'), ',') }}%):</span>
                                    <span class="font-medium text-[#2c2a28]">{{ number_format($order->tax_amount, 0, ',', '.') }} ₫</span>
                                </div>
                            @endif

                            <div class="flex justify-between items-baseline border-t border-[#efe8e3] pt-3 text-base">
                                <span class="font-bold text-[#2c2a28]">Tổng cộng:</span>
                                <span class="font-serif text-2xl font-bold text-[#b8847e]">{{ number_format($order->total, 0, ',', '.') }} ₫</span>
                            </div>
                        </div>
                    </div>

                    {{-- Cancel Order Button for Pending Orders --}}
                    @if($order->status === 'pending' && $order->payment_status !== 'paid')
                        <div class="pt-2">
                            <form method="POST" action="{{ route('orders.cancel', $order->id) }}"
                                  onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?')">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-3 rounded-2xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-bold transition-colors shadow-sm">
                                    Hủy đơn hàng
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
