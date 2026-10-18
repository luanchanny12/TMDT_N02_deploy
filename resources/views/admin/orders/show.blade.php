@php
    $statusColors = [
        'pending'    => 'bg-amber-50 text-amber-800 border border-amber-200',
        'confirmed'  => 'bg-sky-50 text-sky-800 border border-sky-200',
        'shipping'   => 'bg-rose-50 text-rose-800 border border-rose-200',
        'delivered'  => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
        'cancelled'  => 'bg-neutral-100 text-neutral-600 border border-neutral-200',
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
        'pending' => 'bg-amber-50 text-amber-800 border border-amber-200',
        'paid'    => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
        'failed'  => 'bg-rose-50 text-rose-800 border border-rose-200',
    ];
    $paymentLabels = [
        'pending' => 'Chưa thanh toán',
        'paid'    => 'Đã thanh toán',
        'failed'  => 'Thất bại',
    ];
@endphp

<x-layouts.admin :title="'Đơn ' . $order->order_code" :header="'Chi tiết đơn hàng'">

    {{-- Header & Breadcrumb --}}
    <div class="mb-6 space-y-4">
        <div class="flex items-center gap-2 text-xs text-[#9a9490]">
            <a href="{{ route('admin.orders.index') }}" class="hover:text-[#2c2a28] transition-colors inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Quay lại danh sách đơn</span>
            </a>
            <span>/</span>
            <span class="text-[#2c2a28] font-mono font-medium">{{ $order->order_code }}</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-[#efe8e3] shadow-xs">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="font-serif text-2xl font-bold text-[#2c2a28]">{{ $order->order_code }}</h1>
                    <span id="status-badge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$order->status] ?? 'bg-neutral-100 text-neutral-800' }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                </div>
                <p class="text-xs text-[#9a9490] mt-1">
                    Đặt lúc: <span class="font-medium text-[#2c2a28]">{{ $order->created_at->format('d/m/Y H:i:s') }}</span> ({{ $order->created_at->diffForHumans() }})
                </p>
            </div>

            {{-- Status Update Controller --}}
            <script>
                window.adminStatusColors = @json($statusColors);
                window.adminStatusLabels = @json($statusLabels);
            </script>
            <div class="flex items-center gap-2.5" x-data="{
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
                            badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ' + (colors[data.status] || 'bg-neutral-100 text-neutral-800');
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
                <span class="text-xs font-medium text-[#78716c] hidden sm:inline">Chuyển trạng thái:</span>
                <div class="relative">
                    <select
                        @change="updateStatus($event.target.value)"
                        :disabled="updating"
                        class="border border-[#efe8e3] rounded-xl px-3.5 py-2 text-xs font-medium bg-[#faf7f4]/60 text-[#2c2a28] focus:bg-white focus:ring-2 focus:ring-[#b8847e]/20 focus:border-[#b8847e] transition-all disabled:opacity-50 cursor-pointer">
                        @foreach($allStatuses as $s)
                            <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>
                                {{ $statusLabels[$s] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Order Items & Financial Summary --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Products Table --}}
            <div class="bg-white rounded-2xl shadow-xs border border-[#efe8e3] overflow-hidden">
                <div class="px-6 py-4 border-b border-[#efe8e3] bg-[#faf7f4]/40 flex items-center justify-between">
                    <h2 class="font-serif text-lg font-bold text-[#2c2a28]">Sản phẩm trong đơn</h2>
                    <span class="text-xs text-[#9a9490] font-medium">{{ $order->items->count() }} mặt hàng</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-[#faf7f4]/80 border-b border-[#efe8e3] text-xs font-semibold text-[#78716c] uppercase tracking-wider">
                                <th class="px-5 py-3.5 text-left">Sản phẩm</th>
                                <th class="px-5 py-3.5 text-left">Đơn giá</th>
                                <th class="px-5 py-3.5 text-center">SL</th>
                                <th class="px-5 py-3.5 text-right">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#efe8e3]/70">
                            @foreach($order->items as $item)
                                <tr class="hover:bg-[#faf7f4]/50 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3.5">
                                            <div class="w-12 h-12 rounded-xl bg-neutral-100 border border-[#efe8e3] overflow-hidden flex-shrink-0">
                                                @if($item->product && $item->product->image_url)
                                                    <img src="{{ $item->product->image_url }}"
                                                         alt="{{ $item->product_name }}"
                                                         class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full bg-gradient-to-br from-[#f5f0ec] to-[#efe8e3] flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-[#2c2a28] text-sm">{{ $item->product_name ?? $item->product->name ?? 'Sản phẩm đã gỡ' }}</p>
                                                @if($item->product)
                                                    <a href="{{ route('admin.products.edit', $item->product) }}" class="text-2xs text-[#b8847e] hover:underline inline-flex items-center gap-0.5 mt-0.5">
                                                        <span>Xem sản phẩm</span>
                                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                        </svg>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-[#78716c] font-medium whitespace-nowrap">{{ number_format($item->price, 0, ',', '.') }}₫</td>
                                    <td class="px-5 py-4 text-center font-bold text-[#2c2a28] whitespace-nowrap">{{ $item->quantity }}</td>
                                    <td class="px-5 py-4 text-right font-bold text-[#b8847e] whitespace-nowrap">{{ number_format($item->subtotal ?? $item->quantity * $item->price, 0, ',', '.') }}₫</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Financial Summary --}}
            <div class="bg-white rounded-2xl shadow-xs border border-[#efe8e3] p-6">
                <h2 class="font-serif text-lg font-bold text-[#2c2a28] mb-4">Tóm tắt thanh toán</h2>
                <div class="space-y-3 max-w-sm ml-auto">
                    <div class="flex justify-between text-sm">
                        <span class="text-[#78716c]">Tạm tính hàng hóa</span>
                        <span class="font-medium text-[#2c2a28]">{{ number_format($order->subtotal, 0, ',', '.') }}₫</span>
                    </div>
                    @if($order->discount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-[#78716c]">Voucher giảm giá</span>
                            <span class="font-medium text-emerald-600">-{{ number_format($order->discount, 0, ',', '.') }}₫</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-[#78716c]">Phí giao hàng</span>
                        <span class="font-medium text-[#2c2a28]">{{ number_format($order->shipping_fee, 0, ',', '.') }}₫</span>
                    </div>
                    @if($order->tax_amount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-[#78716c]">Thuế VAT ({{ rtrim(rtrim(number_format($order->tax_rate, 2, ',', ''), '0'), ',') }}%)</span>
                            <span class="font-medium text-[#2c2a28]">{{ number_format($order->tax_amount, 0, ',', '.') }}₫</span>
                        </div>
                    @endif
                    <div class="border-t border-[#efe8e3] pt-3 flex justify-between items-baseline">
                        <span class="font-bold text-[#2c2a28]">Tổng thanh toán</span>
                        <span class="font-serif font-extrabold text-2xl text-[#b8847e]">{{ number_format($order->total, 0, ',', '.') }}₫</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Customer Info, Shipping & Payment Status --}}
        <div class="space-y-6">

            {{-- Customer Card --}}
            <div class="bg-white rounded-2xl shadow-xs border border-[#efe8e3] p-6">
                <h2 class="font-serif text-lg font-bold text-[#2c2a28] mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Khách hàng</span>
                </h2>
                <div class="space-y-3.5 text-sm">
                    <div class="flex items-center gap-3 pb-3 border-b border-[#efe8e3]">
                        <div class="w-10 h-10 rounded-full bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center font-bold text-[#b8847e]">
                            {{ mb_strtoupper(mb_substr($order->shipping_name ?? 'K', 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-bold text-[#2c2a28]">{{ $order->shipping_name }}</p>
                            <p class="text-xs text-[#9a9490]">Thành viên</p>
                        </div>
                    </div>
                    <div>
                        <span class="text-xs text-[#9a9490] block mb-0.5">Email tài khoản</span>
                        <p class="font-medium text-[#2c2a28]">{{ $order->user->email ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-[#9a9490] block mb-0.5">Số điện thoại liên hệ</span>
                        <a href="tel:{{ $order->shipping_phone }}" class="font-medium text-[#b8847e] hover:underline">{{ $order->shipping_phone }}</a>
                    </div>
                </div>
            </div>

            {{-- Shipping Address Card --}}
            <div class="bg-white rounded-2xl shadow-xs border border-[#efe8e3] p-6">
                <h2 class="font-serif text-lg font-bold text-[#2c2a28] mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Địa chỉ nhận hàng</span>
                </h2>
                <div class="p-3.5 bg-[#faf7f4] rounded-xl border border-[#efe8e3]">
                    <p class="text-sm text-[#2c2a28] leading-relaxed">
                        {{ $order->shipping_address }}@if($order->shipping_province), <strong>{{ $order->shipping_province }}</strong>@endif
                    </p>
                </div>
                @if($order->note)
                    <div class="mt-3.5 p-3.5 bg-amber-50/50 rounded-xl border border-amber-200/60">
                        <span class="text-xs font-semibold text-amber-800 block mb-1">Ghi chú từ khách:</span>
                        <p class="text-xs text-amber-900 italic">{{ $order->note }}</p>
                    </div>
                @endif
            </div>

            {{-- Payment Card --}}
            <div class="bg-white rounded-2xl shadow-xs border border-[#efe8e3] p-6">
                <h2 class="font-serif text-lg font-bold text-[#2c2a28] mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span>Cổng thanh toán</span>
                </h2>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-[#78716c]">Phương thức:</span>
                        <span class="font-semibold text-xs px-2.5 py-1 rounded-lg bg-[#faf7f4] border border-[#efe8e3] text-[#2c2a28]">
                            {{ strtoupper($order->payment_method) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-[#78716c]">Tình trạng tiền:</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $paymentColors[$order->payment_status] ?? 'bg-neutral-100 text-neutral-800' }}">
                            {{ $order->payment_method === 'cod' && $order->payment_status === 'pending' ? 'Thu khi giao (COD)' : ($paymentLabels[$order->payment_status] ?? $order->payment_status) }}
                        </span>
                    </div>
                    @if($order->payment_method !== 'cod' && $order->payment_status !== 'paid' && $order->status !== 'cancelled')
                        <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 mt-2">
                            <p class="text-xs text-rose-700">Đơn chưa nhận được tiền — không thể xác nhận/giao hàng.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-layouts.admin>
