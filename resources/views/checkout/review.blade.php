<x-layouts.app :title="'Xác nhận đơn hàng'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center gap-3 mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Xác nhận đơn hàng</h1>
            <span class="text-xs font-semibold bg-[#fdf2f0] text-[#b8847e] px-2 py-1 rounded-full">Bước 2/2</span>
        </div>

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('checkout.confirm') }}" method="POST"
              x-data="{ submitting: false }" @submit="if (submitting) { $event.preventDefault() } submitting = true">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    {{-- Shipping info --}}
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Thông tin giao hàng</h2>
                            <a href="{{ route('checkout.index') }}" class="text-sm text-[#b8847e] hover:underline">Sửa</a>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex gap-3">
                                <dt class="w-32 text-gray-500">Họ tên</dt>
                                <dd class="font-medium text-gray-900">{{ $checkout['shipping_name'] }}</dd>
                            </div>
                            <div class="flex gap-3">
                                <dt class="w-32 text-gray-500">Số điện thoại</dt>
                                <dd class="font-medium text-gray-900">{{ $checkout['shipping_phone'] }}</dd>
                            </div>
                            <div class="flex gap-3">
                                <dt class="w-32 text-gray-500">Địa chỉ</dt>
                                <dd class="font-medium text-gray-900">{{ $checkout['shipping_address'] }}</dd>
                            </div>
                            @if(!empty($checkout['shipping_notes']))
                                <div class="flex gap-3">
                                    <dt class="w-32 text-gray-500">Ghi chú</dt>
                                    <dd class="font-medium text-gray-900">{{ $checkout['shipping_notes'] }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Payment --}}
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Phương thức thanh toán</h2>
                            <a href="{{ route('checkout.index') }}" class="text-sm text-[#b8847e] hover:underline">Sửa</a>
                        </div>
                        <p class="text-sm font-medium text-gray-900">
                            @if($checkout['payment_method'] === 'vnpay')
                                VNPay — Thanh toán trực tuyến
                            @else
                                Thanh toán khi nhận hàng (COD)
                            @endif
                        </p>
                    </div>

                    {{-- Items --}}
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Sản phẩm ({{ $cartItems->sum('quantity') }})</h2>
                        <div class="space-y-4">
                            @foreach($cartItems as $item)
                                <div class="flex items-center gap-4">
                                    @if($item->product->image_url)
                                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}"
                                             class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                                    @else
                                        <div class="w-16 h-16 bg-gray-100 rounded-lg border border-gray-200"></div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $item->product->name }}</p>
                                        <p class="text-xs text-gray-500">x{{ $item->quantity }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">
                                        {{ number_format($item->price * $item->quantity, 0, ',', '.') }} ₫
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Agree terms --}}
                    <div class="bg-white rounded-lg shadow p-6">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="agree_terms" value="1" required
                                   class="mt-1 h-4 w-4 text-[#b8847e] border-gray-300 rounded focus:ring-[#b8847e]">
                            <span class="text-sm text-gray-700">
                                Tôi đã đọc và đồng ý với
                                <a href="{{ route('pages.terms') }}" target="_blank" rel="noopener" class="text-[#b8847e] underline">Điều Kiện Giao Dịch Chung</a>
                                và
                                <a href="{{ route('pages.privacy') }}" target="_blank" rel="noopener" class="text-[#b8847e] underline">Chính sách bảo mật</a>
                                <span class="text-red-500">*</span>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Tổng đơn hàng</h2>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tạm tính</span>
                                <span class="font-medium">{{ number_format($cartTotal, 0, ',', '.') }} ₫</span>
                            </div>

                            @if($discount > 0)
                                <div class="flex justify-between">
                                    <span class="text-green-600">Giảm giá{{ $couponCode ? ' ('.$couponCode.')' : '' }}</span>
                                    <span class="text-green-600">-{{ number_format($discount, 0, ',', '.') }} ₫</span>
                                </div>
                            @endif

                            <div class="flex justify-between">
                                <span class="text-gray-600">Phí vận chuyển</span>
                                @if($shippingFee > 0)
                                    <span class="font-medium">{{ number_format($shippingFee, 0, ',', '.') }} ₫</span>
                                @else
                                    <span class="text-green-600 font-medium">Miễn phí</span>
                                @endif
                            </div>

                            @if($taxAmount > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ $taxLabel }} ({{ rtrim(rtrim(number_format($taxRate, 2, ',', ''), '0'), ',') }}%)</span>
                                    <span class="font-medium">{{ number_format($taxAmount, 0, ',', '.') }} ₫</span>
                                </div>
                            @endif

                            <div class="flex justify-between text-lg font-bold border-t pt-3">
                                <span>Tổng cộng</span>
                                <span class="text-[#b8847e]">{{ number_format($total, 0, ',', '.') }} ₫</span>
                            </div>
                        </div>

                        <button type="submit" :disabled="submitting"
                                class="w-full bg-[#b8847e] text-white py-3 rounded-lg font-semibold hover:bg-[#a6736d] transition mt-6 disabled:opacity-60 disabled:cursor-wait">
                            <span x-text="submitting ? 'Đang xử lý...' : 'Đặt hàng'">Đặt hàng</span>
                        </button>

                        <a href="{{ route('checkout.index') }}"
                           class="block text-center text-sm text-gray-500 mt-3 hover:text-[#b8847e]">
                            ← Quay lại nhập thông tin
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app>
