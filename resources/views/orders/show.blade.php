<x-layouts.app :title="'Đơn hàng ' . $order->order_code">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Đơn hàng {{ $order->order_code }}</h1>
                <p class="text-gray-500">Đặt ngày {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium
                @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                @elseif($order->status === 'confirmed') bg-blue-100 text-blue-800
                @elseif($order->status === 'shipping') bg-rose-100 text-rose-800
                @elseif($order->status === 'delivered') bg-green-100 text-green-800
                @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                @else bg-gray-100 text-gray-800
                @endif">
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Order Items --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            @if($item->product && $item->product->image_url)
                                                <img src="{{ $item->product->image_url }}"
                                                     alt="{{ $item->product_name }}" class="h-12 w-12 object-cover rounded">
                                            @endif
                                            <div class="ml-3">
                                                <p class="font-medium text-gray-900">{{ $item->product_name ?? $item->product->name ?? 'SP đã xóa' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ number_format($item->price, 0, ',', '.') }} ₫
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ number_format($item->subtotal ?? $item->quantity * $item->price, 0, ',', '.') }} ₫
                            </td>
                        </tr>
                        @if($order->status === 'delivered' && $item->product)
                            <tr class="bg-green-50/40">
                                <td colspan="4" class="px-6 py-2 text-right">
                                    <a href="{{ route('products.show', $item->product) }}#reviews"
                                       class="inline-flex items-center gap-1 text-sm font-medium text-[#b8847e] hover:text-[#a6736d] transition">
                                        ★ Đánh giá sản phẩm này
                                    </a>
                                </td>
                            </tr>
                        @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Order Info --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Payment --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Thanh toán</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phương thức:</span>
                            <span class="font-medium">
                                @if($order->payment_method == 'cod') COD @else VNPay @endif
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Trạng thái thanh toán:</span>
                            @if($order->payment_status === 'paid')
                                <span class="font-medium text-green-600">Đã thanh toán</span>
                            @elseif($order->payment_status === 'failed')
                                <span class="font-medium text-red-600">Thanh toán thất bại</span>
                            @elseif($order->payment_method === 'cod')
                                <span class="font-medium text-gray-700">Thanh toán khi nhận hàng</span>
                            @else
                                <span class="font-medium text-yellow-700">Chưa thanh toán</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tạm tính:</span>
                            <span>{{ number_format($order->subtotal, 0, ',', '.') }} ₫</span>
                        </div>
                        @if($order->discount > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Giảm giá:</span>
                                <span class="text-green-600">-{{ number_format($order->discount, 0, ',', '.') }} ₫</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phí vận chuyển:</span>
                            <span>{{ number_format($order->shipping_fee, 0, ',', '.') }} ₫</span>
                        </div>
                        @if($order->tax_amount > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Thuế VAT ({{ rtrim(rtrim(number_format($order->tax_rate, 2, ',', ''), '0'), ',') }}%):</span>
                                <span>{{ number_format($order->tax_amount, 0, ',', '.') }} ₫</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-lg font-bold border-t pt-2">
                            <span>Tổng cộng:</span>
                            <span class="text-[#b8847e]">{{ number_format($order->total, 0, ',', '.') }} ₫</span>
                        </div>
                    </div>
                </div>

                @if($order->status === 'pending' && $order->payment_status !== 'paid')
                    <form method="POST" action="{{ route('orders.cancel', $order->id) }}"
                          onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?')">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50 transition">
                            Hủy đơn hàng
                        </button>
                    </form>
                @endif

                {{-- Shipping --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Giao hàng</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Người nhận:</span>
                            <span class="font-medium">{{ $order->shipping_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Điện thoại:</span>
                            <span>{{ $order->shipping_phone }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Địa chỉ:</span>
                            <p class="mt-1">{{ $order->shipping_address }}</p>
                        </div>
                        @if($order->note)
                            <div>
                                <span class="text-gray-600">Ghi chú:</span>
                                <p class="mt-1">{{ $order->note }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
