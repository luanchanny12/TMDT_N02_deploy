<x-layouts.admin :title="'Quản lý đơn hàng'" :header="'Đơn hàng'">

    {{-- Header & Toolbar --}}
    <div class="mb-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="font-serif text-2xl font-bold text-[#2c2a28]">Danh sách đơn hàng</h1>
                <p class="text-xs text-[#9a9490] mt-0.5">Theo dõi luồng xử lý đơn, xác nhận thanh toán và thông tin giao nhận</p>
            </div>
        </div>

        {{-- Search Bar --}}
        <div class="bg-white p-4 rounded-2xl border border-[#efe8e3] shadow-xs">
            <form action="{{ route('admin.orders.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 w-full">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Tìm theo mã đơn (#DH...), tên hoặc số điện thoại khách..."
                           class="w-full pl-10 pr-10 py-2.5 border border-[#efe8e3] rounded-xl bg-[#faf7f4]/40 focus:bg-white focus:ring-2 focus:ring-[#b8847e]/20 focus:border-[#b8847e] transition-all text-sm placeholder:text-[#9a9490]">
                    <svg class="absolute left-3.5 top-3 h-4 w-4 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    @if(request('search'))
                        <a href="{{ route('admin.orders.index', request()->except('search')) }}"
                           class="absolute right-3.5 top-2.5 text-xs text-[#9a9490] hover:text-[#2c2a28] p-0.5">✕</a>
                    @endif
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="submit"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-[#2c2a28] text-white rounded-xl text-sm font-medium hover:bg-black transition-colors shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Tìm kiếm</span>
                    </button>

                    @if(request('search') || request('status'))
                        <a href="{{ route('admin.orders.index') }}"
                           class="inline-flex items-center justify-center px-3.5 py-2.5 bg-neutral-100 text-[#6b6560] rounded-xl text-sm font-medium hover:bg-neutral-200 transition-colors"
                           title="Đặt lại bộ lọc">
                            <span>Tất cả</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Status Filter Tabs --}}
        @php
            $statuses = [
                '' => 'Tất cả đơn',
                'pending' => 'Chờ xử lý',
                'confirmed' => 'Đã xác nhận',
                'shipping' => 'Đang giao',
                'delivered' => 'Đã giao',
                'cancelled' => 'Đã hủy',
            ];
        @endphp
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
            @foreach($statuses as $value => $label)
                @php
                    $isActive = request('status') === $value || (!request('status') && $value === '');
                @endphp
                <a href="{{ route('admin.orders.index', array_merge(request()->except('status', 'page'), $value ? ['status' => $value] : [])) }}"
                   data-status="{{ $value }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all
                          {{ $isActive
                              ? 'bg-[#2c2a28] text-white shadow-xs'
                              : 'bg-white border border-[#efe8e3] text-[#6b6560] hover:text-[#2c2a28] hover:border-[#b8847e]/40 hover:bg-[#faf7f4]' }}">
                    @if($value === 'pending')
                        <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-amber-400' : 'bg-amber-500' }}"></span>
                    @elseif($value === 'shipping')
                        <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-rose-400' : 'bg-[#b8847e]' }}"></span>
                    @elseif($value === 'delivered')
                        <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-emerald-400' : 'bg-emerald-500' }}"></span>
                    @endif
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Orders Table --}}
    @if($orders->count())
        <div class="bg-white rounded-2xl shadow-xs border border-[#efe8e3] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[#faf7f4]/80 border-b border-[#efe8e3]">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Mã đơn</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Khách hàng</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Tổng tiền</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Trạng thái</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Thanh toán</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Ngày đặt</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-[#78716c] uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#efe8e3]/70">
                        @foreach($orders as $order)
                            <tr class="hover:bg-[#faf7f4]/60 transition-colors group">
                                {{-- Order Code --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="font-mono font-bold text-sm text-[#2c2a28] hover:text-[#b8847e] transition-colors">
                                        {{ $order->order_code }}
                                    </a>
                                </td>

                                {{-- Customer Info --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#faf7f4] to-[#efe8e3] border border-[#efe8e3] flex items-center justify-center text-xs font-bold text-[#b8847e] flex-shrink-0">
                                            {{ mb_strtoupper(mb_substr($order->shipping_name ?? 'K', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-sm text-[#2c2a28] truncate">{{ $order->shipping_name }}</p>
                                            <p class="text-xs text-[#9a9490]">{{ $order->shipping_phone }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Total --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-bold text-sm text-[#b8847e]">{{ number_format($order->total, 0, ',', '.') }}₫</p>
                                    @if($order->items_count ?? false)
                                        <p class="text-2xs text-[#9a9490]">{{ $order->items_count }} sản phẩm</p>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @php
                                        $statusBadges = [
                                            'pending' => 'bg-amber-50 text-amber-800 border-amber-200 dot-amber-500',
                                            'confirmed' => 'bg-sky-50 text-sky-800 border-sky-200 dot-sky-500',
                                            'shipping' => 'bg-rose-50 text-rose-800 border-rose-200 dot-[#b8847e]',
                                            'delivered' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dot-emerald-500',
                                            'cancelled' => 'bg-neutral-100 text-neutral-600 border-neutral-200 dot-neutral-400',
                                        ];
                                        $statusLabels = [
                                            'pending' => 'Chờ xử lý',
                                            'confirmed' => 'Đã xác nhận',
                                            'shipping' => 'Đang giao',
                                            'delivered' => 'Đã giao',
                                            'cancelled' => 'Đã hủy',
                                        ];
                                        $badgeClass = $statusBadges[$order->status] ?? 'bg-neutral-100 text-neutral-700 border-neutral-200 dot-neutral-400';
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $order->status === 'pending' ? 'bg-amber-500 animate-pulse' : ($order->status === 'delivered' ? 'bg-emerald-500' : ($order->status === 'shipping' ? 'bg-[#b8847e]' : 'bg-neutral-400')) }}"></span>
                                        <span>{{ $statusLabels[$order->status] ?? $order->status }}</span>
                                    </span>
                                </td>

                                {{-- Payment --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-[#2c2a28]">
                                            @if($order->payment_method === 'cod')
                                                <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span>
                                                COD
                                            @else
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                VNPay
                                            @endif
                                        </span>
                                        @if($order->payment_method !== 'cod')
                                            <span class="text-2xs font-semibold {{ $order->payment_status === 'paid' ? 'text-emerald-700' : ($order->payment_status === 'failed' ? 'text-rose-600' : 'text-amber-700') }}">
                                                {{ ['paid' => 'Đã thanh toán', 'failed' => 'Thất bại'][$order->payment_status] ?? 'Chưa thanh toán' }}
                                            </span>
                                        @else
                                            <span class="text-2xs text-[#9a9490]">Thu khi giao</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Created At --}}
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-[#9a9490]">
                                    <p class="font-medium text-[#2c2a28]">{{ $order->created_at->format('d/m/Y') }}</p>
                                    <p class="text-2xs text-[#9a9490]">{{ $order->created_at->format('H:i') }}</p>
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-medium text-[#b8847e] hover:text-white hover:bg-[#b8847e] border border-[#efe8e3] hover:border-[#b8847e] transition-colors shadow-2xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>Chi tiết</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="px-6 py-4 border-t border-[#efe8e3] bg-[#faf7f4]/40 flex items-center justify-between">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="bg-white rounded-2xl border border-[#efe8e3] p-12 text-center shadow-xs">
            <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-[#faf7f4] to-[#efe8e3] rounded-2xl flex items-center justify-center text-[#b8847e] shadow-inner">
                <svg class="h-10 w-10 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h2 class="font-serif text-xl font-bold text-[#2c2a28] mb-1.5">Không tìm thấy đơn hàng</h2>
            <p class="text-[#9a9490] text-sm max-w-sm mx-auto mb-6">
                @if(request('search') || request('status'))
                    Thử thay đổi bộ lọc trạng thái hoặc từ khóa tìm kiếm.
                @else
                    Chưa có đơn hàng nào phát sinh trong hệ thống.
                @endif
            </p>
            @if(request('search') || request('status'))
                <a href="{{ route('admin.orders.index') }}"
                   class="inline-flex items-center px-5 py-2.5 rounded-xl text-sm font-medium text-[#2c2a28] bg-neutral-100 hover:bg-neutral-200 transition-colors">
                    Xem tất cả đơn hàng
                </a>
            @endif
        </div>
    @endif

</x-layouts.admin>
