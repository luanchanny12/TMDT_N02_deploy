<x-layouts.admin :title="'Phân tích dữ liệu'" :header="'Phân tích dữ liệu'">
    @php
        $money = fn ($v) => number_format((int) $v, 0, ',', '.').'₫';
        $pct = fn ($v) => number_format((float) $v, 2, ',', '.').'%';
        $card = 'bg-white rounded-xl shadow-sm border border-[#efe8e3]';
        $th = 'px-4 py-3 text-xs font-semibold text-[#9a9490] uppercase tracking-wide';
    @endphp

    @if($hasDemoData)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4" data-testid="demo-data-notice">
            <p class="text-sm text-amber-800">
                Trang đang gồm <strong>dữ liệu mẫu</strong> (lượt truy cập, chiến dịch demo) để minh họa.
                Mọi thao tác thật của khách vẫn được cộng thêm. Xóa dữ liệu mẫu để chỉ xem số liệu thật.
            </p>
            <form method="POST" action="{{ route('admin.analytics.clear-demo') }}"
                  onsubmit="return confirm('Xóa toàn bộ dữ liệu mẫu? Số liệu thật và đơn hàng được giữ nguyên.')">
                @csrf
                @method('DELETE')
                <button class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Xóa dữ liệu mẫu</button>
            </form>
        </div>
    @endif

    {{-- Bộ lọc thời gian --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <p class="text-sm text-[#9a9490]">
            Từ <strong class="text-[#3d3d3d]">{{ $from->format('d/m/Y') }}</strong>
            đến <strong class="text-[#3d3d3d]">{{ $to->format('d/m/Y') }}</strong>
        </p>
        <div class="inline-flex rounded-lg border border-[#efe8e3] bg-white p-1">
            @foreach($ranges as $value => $label)
                <a href="{{ route('admin.analytics.index', ['days' => $value]) }}"
                   class="px-3 py-1.5 text-sm rounded-md {{ $days === $value ? 'bg-[#b8847e] text-white' : 'text-[#3d3d3d] hover:bg-[#faf7f4]' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach([
            ['Lượt truy cập', number_format($kpis['visits'], 0, ',', '.'), 'Số phiên truy cập website'],
            ['Lượt xem sản phẩm', number_format($kpis['product_views'], 0, ',', '.'), 'Tổng lượt mở trang chi tiết'],
            ['Tỷ lệ chuyển đổi', $pct($kpis['conversion_rate']), 'Đơn hàng / lượt truy cập'],
            ['Doanh thu', $money($kpis['revenue']), $kpis['orders'].' đơn · TB '.$money($kpis['aov']).'/đơn'],
            ['Chi phí quảng cáo', $money($kpis['ad_spend']), 'Tổng các chiến dịch'],
            ['Doanh thu từ quảng cáo', $money($kpis['ad_revenue']), 'Đơn đến từ link chiến dịch'],
            ['ROAS', $kpis['ad_spend'] > 0 ? number_format($kpis['roas'], 2, ',', '.').'x' : '—', 'Doanh thu QC / chi phí QC'],
        ] as [$label, $value, $hint])
            <div class="{{ $card }} p-5" data-kpi="{{ $label }}">
                <p class="text-xs text-[#9a9490] font-medium uppercase tracking-wide">{{ $label }}</p>
                <p class="text-xl font-extrabold text-[#3d3d3d] mt-1">{{ $value }}</p>
                <p class="text-xs text-[#9a9490] mt-1">{{ $hint }}</p>
            </div>
        @endforeach
        <a href="{{ route('admin.campaigns.index') }}" class="{{ $card }} p-5 flex flex-col justify-center hover:border-[#b8847e] transition-colors">
            <p class="text-sm font-semibold text-[#b8847e]">Quản lý chiến dịch →</p>
            <p class="text-xs text-[#9a9490] mt-1">Tạo link UTM cho Facebook, YouTube, TikTok</p>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Biểu đồ theo ngày --}}
        <div class="lg:col-span-2 {{ $card }} p-6">
            <h2 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Lượt truy cập & đơn hàng theo ngày</h2>
            <div class="relative" style="height: 280px;">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        {{-- Phễu chuyển đổi --}}
        <div class="{{ $card }} p-6">
            <h2 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Phễu chuyển đổi</h2>
            @php $top = max(1, $funnel[0]['value']); @endphp
            <div class="space-y-4">
                @foreach($funnel as $i => $step)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-[#3d3d3d]">{{ $step['label'] }}</span>
                            <span class="font-semibold text-[#3d3d3d]">
                                {{ number_format($step['value'], 0, ',', '.') }}
                                @if($i > 0)
                                    <span class="text-xs font-normal text-[#9a9490]">({{ $pct($step['value'] / $top * 100) }})</span>
                                @endif
                            </span>
                        </div>
                        <div class="h-2.5 rounded-full bg-[#faf7f4] overflow-hidden">
                            <div class="h-full rounded-full bg-[#b8847e]" style="width: {{ min(100, round($step['value'] / $top * 100, 1)) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Nguồn truy cập --}}
        <div class="{{ $card }} overflow-hidden">
            <h2 class="font-serif text-lg font-semibold text-[#3d3d3d] px-6 pt-6 pb-3">Nguồn truy cập</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#faf7f4]">
                        <tr>
                            <th class="{{ $th }} text-left">Nguồn</th>
                            <th class="{{ $th }} text-right">Truy cập</th>
                            <th class="{{ $th }} text-right">Đơn</th>
                            <th class="{{ $th }} text-right">Doanh thu</th>
                            <th class="{{ $th }} text-right">CR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#efe8e3]">
                        @forelse($sources as $row)
                            <tr>
                                <td class="px-4 py-3 font-medium text-[#3d3d3d] capitalize">{{ $row['source'] }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($row['visits'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['orders'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $money($row['revenue']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $pct($row['conversion_rate']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-[#9a9490]">Chưa có dữ liệu trong khoảng thời gian này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sản phẩm được quan tâm --}}
        <div class="{{ $card }} overflow-hidden">
            <h2 class="font-serif text-lg font-semibold text-[#3d3d3d] px-6 pt-6 pb-3">Sản phẩm được quan tâm</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#faf7f4]">
                        <tr>
                            <th class="{{ $th }} text-left">Sản phẩm</th>
                            <th class="{{ $th }} text-right">Lượt xem</th>
                            <th class="{{ $th }} text-right">Thêm giỏ</th>
                            <th class="{{ $th }} text-right">Tỷ lệ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#efe8e3]">
                        @forelse($topProducts as $row)
                            <tr>
                                <td class="px-4 py-3 text-[#3d3d3d]">{{ $row->product->name ?? 'Đã xóa' }}</td>
                                <td class="px-4 py-3 text-right">{{ (int) $row->views }}</td>
                                <td class="px-4 py-3 text-right">{{ (int) $row->carts }}</td>
                                <td class="px-4 py-3 text-right">{{ $row->views > 0 ? $pct($row->carts / $row->views * 100) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-[#9a9490]">Chưa có lượt xem sản phẩm.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Chiến dịch --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="flex items-center justify-between px-6 pt-6 pb-3">
            <h2 class="font-serif text-lg font-semibold text-[#3d3d3d]">Hiệu quả chiến dịch quảng cáo</h2>
            <span class="text-xs text-[#9a9490]">Tính trên toàn thời gian chạy chiến dịch</span>
        </div>
        @include('admin.campaigns._table', ['campaigns' => $campaigns, 'compact' => true])
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('trafficChart');
            if (!el || typeof Chart === 'undefined') return;
            const rows = @json($daily);

            new Chart(el, {
                data: {
                    labels: rows.map(r => r.label),
                    datasets: [
                        { type: 'line', label: 'Lượt truy cập', data: rows.map(r => r.visits), borderColor: '#b8847e', backgroundColor: 'rgba(184,132,126,.15)', fill: true, tension: .3, yAxisID: 'y' },
                        { type: 'bar', label: 'Đơn hàng', data: rows.map(r => r.orders), backgroundColor: '#3d3a37', borderRadius: 4, yAxisID: 'y1' },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { labels: { color: '#3d3d3d' } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#9a9490', maxTicksLimit: 12 } },
                        y: { beginAtZero: true, grid: { color: '#efe8e3' }, ticks: { color: '#9a9490', precision: 0 }, title: { display: true, text: 'Truy cập' } },
                        y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { color: '#9a9490', precision: 0 }, title: { display: true, text: 'Đơn' } },
                    },
                },
            });
        });
    </script>
</x-layouts.admin>
