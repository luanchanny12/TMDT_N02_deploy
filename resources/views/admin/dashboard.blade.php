<x-layouts.admin :title="'Dashboard'" :header="'Tổng quan'">

    {{-- 4 Hero KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        {{-- Doanh thu tháng --}}
        <div class="bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-5 sm:p-6 hover:border-[#c9a9a6] hover-lift transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs uppercase tracking-wider font-semibold text-[#9a9490]">Doanh thu tháng</span>
                <span class="w-11 h-11 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e] shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <p class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">{{ number_format($totalRevenue, 0, ',', '.') }}₫</p>
            <div class="flex items-center gap-1.5 mt-2.5">
                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg">↑ Tăng trưởng</span>
                <span class="text-[11px] text-[#9a9490]">tháng này</span>
            </div>
        </div>

        {{-- Đơn hàng hôm nay --}}
        <div class="bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-5 sm:p-6 hover:border-[#c9a9a6] hover-lift transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs uppercase tracking-wider font-semibold text-[#9a9490]">Đơn hôm nay</span>
                <span class="w-11 h-11 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e] shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </span>
            </div>
            <p class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">{{ $todayOrders }} đơn</p>
            <div class="flex items-center gap-1.5 mt-2.5">
                <span class="text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-lg">Hoạt động</span>
                <span class="text-[11px] text-[#9a9490]">trong 24 giờ qua</span>
            </div>
        </div>

        {{-- Tổng sản phẩm --}}
        <div class="bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-5 sm:p-6 hover:border-[#c9a9a6] hover-lift transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs uppercase tracking-wider font-semibold text-[#9a9490]">Tổng sản phẩm</span>
                <span class="w-11 h-11 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e] shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </span>
            </div>
            <p class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">{{ $totalProducts }} mẫu</p>
            <div class="flex items-center gap-1.5 mt-2.5">
                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg">Kho hàng</span>
                <span class="text-[11px] text-[#9a9490]">đang lưu hành</span>
            </div>
        </div>

        {{-- Tổng khách hàng --}}
        <div class="bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-5 sm:p-6 hover:border-[#c9a9a6] hover-lift transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs uppercase tracking-wider font-semibold text-[#9a9490]">Tổng khách hàng</span>
                <span class="w-11 h-11 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e] shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </span>
            </div>
            <p class="font-serif text-2xl sm:text-3xl font-bold text-[#2c2a28]">{{ $totalUsers }} thành viên</p>
            <div class="flex items-center gap-1.5 mt-2.5">
                <span class="text-[11px] font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-lg">Cộng đồng</span>
                <span class="text-[11px] text-[#9a9490]">khách mua sắm</span>
            </div>
        </div>
    </div>

    {{-- Chart + Order Summary --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">

        {{-- Revenue Chart (8 cols) --}}
        <div class="lg:col-span-8 bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-6 sm:p-7">
            <div class="flex items-center justify-between mb-6 pb-3 border-b border-[#efe8e3]">
                <div>
                    <h2 class="font-serif text-lg sm:text-xl font-bold text-[#2c2a28]">Doanh thu 7 ngày gần nhất</h2>
                    <p class="text-xs text-[#9a9490]">Biểu đồ doanh số bán hàng thực tế hàng ngày</p>
                </div>
                <span class="text-xs font-semibold text-[#b8847e] bg-[#faf7f4] border border-[#efe8e3] px-3.5 py-1 rounded-full shadow-sm">
                    Doanh thu (₫)
                </span>
            </div>
            <div class="relative" style="height: 290px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        {{-- Order Summary Metrics (4 cols) --}}
        <div class="lg:col-span-4 bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-6 sm:p-7 flex flex-col justify-between">
            <div>
                <h2 class="font-serif text-lg sm:text-xl font-bold text-[#2c2a28] mb-1">Tóm tắt đơn hàng</h2>
                <p class="text-xs text-[#9a9490] mb-5">Hiệu suất vận hành trong kỳ</p>
                <div class="space-y-3.5">
                    <div class="flex justify-between items-center p-3.5 bg-[#faf7f4] border border-[#efe8e3] rounded-2xl">
                        <span class="text-xs font-medium text-[#6b6560]">Đơn hàng tháng này</span>
                        <span class="font-serif font-bold text-base text-[#2c2a28]">{{ $monthOrders }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3.5 bg-[#faf7f4] border border-[#efe8e3] rounded-2xl">
                        <span class="text-xs font-medium text-[#6b6560]">Đơn hàng hôm nay</span>
                        <span class="font-serif font-bold text-base text-[#b8847e]">{{ $todayOrders }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3.5 bg-[#faf7f4] border border-[#efe8e3] rounded-2xl">
                        <span class="text-xs font-medium text-[#6b6560]">Tổng sản phẩm</span>
                        <span class="font-serif font-bold text-base text-[#2c2a28]">{{ $totalProducts }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3.5 bg-[#faf7f4] border border-[#efe8e3] rounded-2xl">
                        <span class="text-xs font-medium text-[#6b6560]">Tổng người dùng</span>
                        <span class="font-serif font-bold text-base text-[#2c2a28]">{{ $totalUsers }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#efe8e3] text-center">
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-[#b8847e] hover:underline">
                    Quản lý toàn bộ đơn hàng →
                </a>
            </div>
        </div>
    </div>

    {{-- Behavior Stats: Top Viewed + Top Sold + Recent Users --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Top Viewed: Phải chứa chuỗi "Sản phẩm xem nhiều" cho bài test --}}
        <div class="bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-[#efe8e3]">
                <h2 class="font-serif text-lg font-bold text-[#2c2a28]">Sản phẩm xem nhiều</h2>
                <span class="text-[10px] font-bold text-[#b8847e] uppercase">Lượt xem</span>
            </div>
            <div class="space-y-3">
                @forelse($topViewed as $item)
                    <div class="flex justify-between items-center p-3 bg-[#faf7f4] border border-[#efe8e3] rounded-2xl hover:border-[#c9a9a6] transition-colors">
                        <a href="{{ route('products.show', $item->slug) }}" target="_blank"
                           class="text-xs font-bold text-[#2c2a28] hover:text-[#b8847e] truncate mr-2">
                            {{ $item->name }}
                        </a>
                        <span class="font-serif font-bold text-[#b8847e] text-xs whitespace-nowrap bg-white px-2 py-0.5 rounded-lg border border-[#efe8e3]">
                            {{ $item->views_count }} lượt
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-[#9a9490] py-4 text-center">Chưa có lượt xem.</p>
                @endforelse
            </div>
        </div>

        {{-- Top Sold --}}
        <div class="bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-[#efe8e3]">
                <h2 class="font-serif text-lg font-bold text-[#2c2a28]">Bán chạy</h2>
                <span class="text-[10px] font-bold text-emerald-700 uppercase">Đã bán</span>
            </div>
            <div class="space-y-3">
                @forelse($topSelling as $item)
                    <div class="flex justify-between items-center p-3 bg-[#faf7f4] border border-[#efe8e3] rounded-2xl hover:border-[#c9a9a6] transition-colors">
                        <a href="{{ route('products.show', $item->product?->slug) }}" target="_blank"
                           class="text-xs font-bold text-[#2c2a28] hover:text-[#b8847e] truncate mr-2">
                            {{ $item->product?->name ?? 'SP đã xóa' }}
                        </a>
                        <span class="font-serif font-bold text-emerald-700 text-xs whitespace-nowrap bg-white px-2 py-0.5 rounded-lg border border-[#efe8e3]">
                            {{ $item->sold }} SP
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-[#9a9490] py-4 text-center">Chưa có đơn bán.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Users --}}
        <div class="bg-white rounded-3xl shadow-sm border border-[#efe8e3] p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-[#efe8e3]">
                <h2 class="font-serif text-lg font-bold text-[#2c2a28]">Khách mới</h2>
                <span class="text-[10px] font-bold text-sky-700 uppercase">Mới tạo</span>
            </div>
            <div class="space-y-3">
                @forelse($recentUsers as $u)
                    <div class="flex justify-between items-center p-3 bg-[#faf7f4] border border-[#efe8e3] rounded-2xl hover:border-[#c9a9a6] transition-colors">
                        <div class="min-w-0 mr-2 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#faf7f4] to-[#e8c4c4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e] font-bold text-xs flex-shrink-0">
                                {{ mb_substr($u->name, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-[#2c2a28] truncate">{{ $u->name }}</p>
                                <p class="text-[11px] text-[#9a9490] truncate">{{ $u->email }}</p>
                            </div>
                        </div>
                        <span class="text-[10px] text-[#9a9490] whitespace-nowrap bg-white px-2 py-0.5 rounded-lg border border-[#efe8e3]">
                            {{ optional($u->created_at)->format('d/m') }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-[#9a9490] py-4 text-center">Chưa có khách.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Orders Table --}}
    <div class="bg-white rounded-3xl shadow-sm border border-[#efe8e3] overflow-hidden">
        <div class="px-6 py-5 border-b border-[#efe8e3] flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-serif text-lg sm:text-xl font-bold text-[#2c2a28]">Đơn hàng gần đây</h2>
                <p class="text-xs text-[#9a9490]">Các đơn hàng mới nhất cần quản trị viên theo dõi</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-[#b8847e] hover:underline">
                Xem tất cả đơn hàng →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs sm:text-sm">
                <thead class="bg-[#faf7f4]">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-[#9a9490] uppercase tracking-wide">Mã đơn</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-[#9a9490] uppercase tracking-wide">Khách hàng</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-[#9a9490] uppercase tracking-wide">Tổng tiền</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-[#9a9490] uppercase tracking-wide">Trạng thái</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-[#9a9490] uppercase tracking-wide">Ngày đặt</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-[#9a9490] uppercase tracking-wide">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#efe8e3]">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-[#faf7f4]/60 transition-colors">
                            <td class="px-6 py-4 font-serif font-bold text-[#2c2a28]">#{{ $order->order_code }}</td>
                            <td class="px-6 py-4 text-[#2c2a28] font-medium">{{ $order->user->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 font-serif font-bold text-[#b8847e] text-sm sm:text-base">
                                {{ number_format($order->total, 0, ',', '.') }}₫
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold
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
                            </td>
                            <td class="px-6 py-4 text-[#9a9490] text-xs">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="inline-flex items-center gap-1 text-xs font-bold text-[#b8847e] hover:text-[#a6736d] bg-[#faf7f4] border border-[#efe8e3] px-3 py-1.5 rounded-xl transition-all shadow-sm">
                                    Xem
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-[#9a9490] text-sm">Chưa có đơn hàng nào.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Chart.js CDN + Init --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;

            const labels = @json(array_column($chartData, 'date'));
            const data = @json(array_column($chartData, 'total'));

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Doanh thu (₫)',
                        data: data,
                        borderColor: '#b8847e',
                        backgroundColor: 'rgba(184, 132, 126, 0.12)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#b8847e',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#181716',
                            titleColor: '#faf7f4',
                            bodyColor: '#faf7f4',
                            borderColor: '#262422',
                            borderWidth: 1,
                            cornerRadius: 12,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    return new Intl.NumberFormat('vi-VN').format(context.parsed.y) + '₫';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#9a9490', font: { size: 12 } }
                        },
                        y: {
                            grid: { color: '#efe8e3' },
                            ticks: {
                                color: '#9a9490',
                                font: { size: 11 },
                                callback: function(value) {
                                    if (value >= 1000000) return (value / 1000000).toFixed(0) + 'tr';
                                    if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
                                    return value;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

</x-layouts.admin>
