@php
    $statusColors = [
        'pending'    => 'bg-yellow-100 text-yellow-800',
        'confirmed'  => 'bg-blue-100 text-blue-800',
        'shipping'   => 'bg-rose-100 text-rose-800',
        'delivered'  => 'bg-green-100 text-green-800',
        'cancelled'  => 'bg-red-100 text-red-800',
    ];
    $statusLabels = [
        'pending'    => 'Chờ xử lý',
        'confirmed'  => 'Đã xác nhận',
        'shipping'   => 'Đang giao',
        'delivered'  => 'Đã giao',
        'cancelled'  => 'Đã hủy',
    ];
    $allStatuses = ['pending', 'confirmed', 'shipping', 'delivered', 'cancelled'];
    $paymentColors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'paid'    => 'bg-green-100 text-green-800',
        'failed'  => 'bg-red-100 text-red-800',
    ];
    $paymentLabels = [
        'pending' => 'Chưa thanh toán',
        'paid'    => 'Đã thanh toán',
        'failed'  => 'Thất bại',
    ];
@endphp

<x-layouts.admin :title="'Đơn ' . $order->order_code" :header="'Chi tiết đơn hàng'">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-[#9a9490]">Mã đơn: <span class="font-semibold text-[#3d3d3d]">{{ $order->order_code }}</span></p>
            <p class="text-sm text-[#9a9490]">Đặt ngày: {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>

        {{-- Status Update --}}
        <script>
            window.adminStatusColors = @json($statusColors);
            window.adminStatusLabels = @json($statusLabels);
        </script>
        <div class="flex items-center gap-3" x-data="{
            currentStatus: '{{ $order->status }}',
            updating: false,
            updateStatus(newStatus) {
                if (newStatus === this.currentStatus) return;
                this.updating = true;
                fetch('{{ route('admin.orders.update-status', $order) }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                })
                .then(r => r.json())
                .then(data => {
                    this.updating = false;
                    if (!data.success) {
                        this.$el.querySelector('select').value = this.currentStatus;
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: data.message || 'Không thể cập nhật trạng thái.', type: 'error' }
                        }));
                        return;
                    }
                    if (data.success) {
                        this.currentStatus = data.status;
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: 'Cập nhật trạng thái thành công!', type: 'success' }
                        }));
                        const badge = document.getElementById('status-badge');
                        const colors = window.adminStatusColors;
                        const labels = window.adminStatusLabels;
                        badge.className = 'inline-flex px-3 py-1.5 rounded-full text-sm font-semibold ' + (colors[data.status] || 'bg-gray-100 text-gray-800');
                        badge.textContent = labels[data.status] || data.status;
                    }
                })
                .catch(() => {
                    this.updating = false;
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: 'Có lỗi xảy ra, thử lại sau.', type: 'error' }
                    }));
                });
            }
        }">
            <span id="status-badge" class="inline-flex px-3 py-1.5 rounded-full text-sm font-semibold {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                {{ $statusLabels[$order->status] ?? $order->status }}
            </span>
            <select
                @change="updateStatus($event.target.value)"
                :disabled="updating"
                class="border border-[#efe8e3] rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent disabled:opacity-50">
                @foreach($allStatuses as $s)
                    <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>
                        {{ $statusLabels[$s] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Order Items --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Products Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] overflow-hidden">
                <div class="px-6 py-4 border-b border-[#efe8e3]">
                    <h2 class="font-serif text-lg font-semibold text-[#3d3d3d]">Sản phẩm trong đơn</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#faf7f4]">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-[#9a9490] uppercase">Sản phẩm</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-[#9a9490] uppercase">Đơn giá</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-[#9a9490] uppercase">SL</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-[#9a9490] uppercase">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#efe8e3]">
                            @foreach($order->items as $item)
                                <tr class="hover:bg-[#faf7f4] transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            @if($item->product && $item->product->image_url)
                                                <img src="{{ $item->product->image_url }}"
                                                     alt="{{ $item->product_name }}"
                                                     class="w-11 h-11 rounded-lg object-cover flex-shrink-0">
                                            @else
                                                <div class="w-11 h-11 rounded-lg bg-gradient-to-br from-[#f5f0ec] to-[#efe8e3] flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-5 h-5 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="font-medium text-[#3d3d3d]">{{ $item->product_name ?? $item->product->name ?? 'SP đã xóa' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-[#9a9490]">{{ number_format($item->price, 0, ',', '.') }}₫</td>
                                    <td class="px-5 py-4 text-[#3d3d3d]">{{ $item->quantity }}</td>
                                    <td class="px-5 py-4 text-right font-bold text-[#b8847e]">{{ number_format($item->subtotal ?? $item->quantity * $item->price, 0, ',', '.') }}₫</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
                <h2 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Tóm tắt đơn hàng</h2>
                <div class="space-y-3 max-w-xs ml-auto">
                    <div class="flex justify-between text-sm">
                        <span class="text-[#9a9490]">Tạm tính</span>
                        <span class="font-medium text-[#3d3d3d]">{{ number_format($order->subtotal, 0, ',', '.') }}₫</span>
                    </div>
                    @if($order->discount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-[#9a9490]">Giảm giá</span>
                            <span class="font-medium text-green-600">-{{ number_format($order->discount, 0, ',', '.') }}₫</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-[#9a9490]">Phí vận chuyển</span>
                        <span class="font-medium text-[#3d3d3d]">{{ number_format($order->shipping_fee, 0, ',', '.') }}₫</span>
                    </div>
                    @if($order->tax_amount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-[#9a9490]">Thuế VAT ({{ rtrim(rtrim(number_format($order->tax_rate, 2, ',', ''), '0'), ',') }}%)</span>
                            <span class="font-medium text-[#3d3d3d]">{{ number_format($order->tax_amount, 0, ',', '.') }}₫</span>
                        </div>
                    @endif
                    <div class="border-t border-[#efe8e3] pt-3 flex justify-between">
                        <span class="font-bold text-[#3d3d3d]">Tổng cộng</span>
                        <span class="font-extrabold text-lg text-[#b8847e]">{{ number_format($order->total, 0, ',', '.') }}₫</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Customer Info --}}
        <div class="space-y-6">

            {{-- Customer --}}
            <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
                <h2 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Thông tin khách hàng</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-[#9a9490] block mb-0.5">Họ tên</span>
                        <p class="font-medium text-[#3d3d3d]">{{ $order->shipping_name }}</p>
                    </div>
                    <div>
                        <span class="text-[#9a9490] block mb-0.5">Email</span>
                        <p class="text-[#3d3d3d]">{{ $order->user->email ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-[#9a9490] block mb-0.5">Số điện thoại</span>
                        <p class="text-[#3d3d3d]">{{ $order->shipping_phone }}</p>
                    </div>
                </div>
            </div>

            {{-- Shipping Address --}}
            <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
                <h2 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Địa chỉ giao hàng</h2>
                <p class="text-sm text-[#3d3d3d] leading-relaxed">{{ $order->shipping_address }}</p>
                @if($order->note)
                    <div class="mt-3 p-3 bg-[#faf7f4] rounded-lg">
                        <span class="text-xs text-[#9a9490] block mb-1">Ghi chú:</span>
                        <p class="text-sm text-[#3d3d3d] italic">{{ $order->note }}</p>
                    </div>
                @endif
            </div>

            {{-- Payment --}}
            <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
                <h2 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Thanh toán</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#9a9490]">Phương thức:</span>
                        <span class="font-medium text-[#3d3d3d]">{{ strtoupper($order->payment_method) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#9a9490]">Thanh toán:</span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $paymentColors[$order->payment_status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $order->payment_method === 'cod' && $order->payment_status === 'pending' ? 'Thu khi giao (COD)' : ($paymentLabels[$order->payment_status] ?? $order->payment_status) }}
                        </span>
                    </div>
                    @if($order->payment_method !== 'cod' && $order->payment_status !== 'paid' && $order->status !== 'cancelled')
                        <p class="text-xs text-red-600 pt-1">Đơn chưa nhận được tiền — không thể xác nhận/giao hàng.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-layouts.admin>
