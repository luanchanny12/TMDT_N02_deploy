@php
    $money = fn ($v) => $v === null ? '—' : number_format((int) round($v), 0, ',', '.').'₫';
    $pct = fn ($v) => $v === null ? '—' : number_format((float) $v, 1, ',', '.').'%';
    $card = 'bg-white rounded-xl shadow-sm border border-[#efe8e3]';
    $th = 'px-4 py-3 text-xs font-semibold text-[#9a9490] uppercase tracking-wide';
    $stepKeys = array_keys($funnelSteps);
@endphp

{{-- Phễu 5 bước tách theo kênh --}}
<div class="{{ $card }} overflow-hidden mb-6" data-testid="funnel-by-channel">
    <div class="px-6 pt-6 pb-3">
        <h2 class="font-serif text-lg font-semibold text-[#3d3d3d]">Phễu chuyển đổi theo kênh</h2>
        <p class="text-xs text-[#9a9490] mt-1">Số phiên duy nhất ở mỗi bước; % là tỷ lệ chuyển tiếp từ bước trước. Sự kiện tương ứng GA4: session_start → view_item → add_to_cart → begin_checkout → purchase.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-[#faf7f4]">
                <tr>
                    <th class="{{ $th }} text-left">Kênh</th>
                    @foreach($funnelSteps as $label)
                        <th class="{{ $th }} text-right">{{ $label }}</th>
                    @endforeach
                    <th class="{{ $th }} text-right">CR tổng</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#efe8e3]">
                @foreach($funnelByChannel as $key => $channel)
                    @php $steps = $channel['steps']; @endphp
                    <tr class="{{ $key === 'all' ? 'bg-[#faf7f4] font-semibold' : '' }}">
                        <td class="px-4 py-3 text-[#3d3d3d]">{{ $channel['label'] }}</td>
                        @foreach($stepKeys as $i => $step)
                            <td class="px-4 py-3 text-right">
                                {{ number_format($steps[$step], 0, ',', '.') }}
                                @if($i > 0)
                                    @php $prev = $steps[$stepKeys[$i - 1]]; @endphp
                                    <span class="block text-[11px] text-[#9a9490] font-normal">{{ $prev > 0 ? $pct(min(100, $steps[$step] / $prev * 100)) : '—' }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right">{{ $steps['visit'] > 0 ? $pct($steps['purchase'] / $steps['visit'] * 100) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-6">
    {{-- Chẩn đoán điểm rơi --}}
    <div class="lg:col-span-3 {{ $card }} overflow-hidden" data-testid="drop-off">
        <div class="px-6 pt-6 pb-3">
            <h2 class="font-serif text-lg font-semibold text-[#3d3d3d]">Chẩn đoán điểm rơi</h2>
            <p class="text-xs text-[#9a9490] mt-1">Số đơn tăng thêm ≈ Lượng vào bước × Δ tỷ lệ chuyển tiếp (+{{ rtrim(rtrim(number_format($deltaPoints, 1, ',', ''), '0'), ',') }} điểm %) × Tỷ lệ chuyển đổi các bước sau.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#faf7f4]">
                    <tr>
                        <th class="{{ $th }} text-left">Bước chuyển</th>
                        <th class="{{ $th }} text-right">Vào bước</th>
                        <th class="{{ $th }} text-right">Tỷ lệ</th>
                        <th class="{{ $th }} text-right">CR sau</th>
                        <th class="{{ $th }} text-right">Đơn tăng thêm</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#efe8e3]">
                    @foreach($dropOff as $row)
                        <tr class="{{ $row['priority'] ? 'bg-amber-50' : '' }}">
                            <td class="px-4 py-3 text-[#3d3d3d]">
                                {{ $row['from'] }} → {{ $row['to'] }}
                                @if($row['priority'])
                                    <span class="ml-1 text-[11px] font-semibold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">Ưu tiên can thiệp</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($row['entering'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ $pct($row['rate']) }}</td>
                            <td class="px-4 py-3 text-right">{{ $pct($row['downstream']) }}</td>
                            <td class="px-4 py-3 text-right font-semibold">+{{ number_format($row['extra_orders'], 1, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Kinh tế đơn vị --}}
    <div class="lg:col-span-2 {{ $card }} p-6" data-testid="unit-economics">
        <h2 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-1">Kinh tế đơn vị</h2>
        <p class="text-xs text-[#9a9490] mb-4">Biên gộp tính từ giá vốn đã chụp vào từng dòng đơn trong kỳ.</p>
        @php
            $ltvCac = $unitEconomics['ltv_cac'];
            $items = [
                ['Biên lợi nhuận gộp', $unitEconomics['gross_margin'] === null ? '—' : $pct($unitEconomics['gross_margin'] * 100), 'Doanh thu hàng − giá vốn'],
                ['ROAS hòa vốn', $unitEconomics['break_even_roas'] === null ? '—' : number_format($unitEconomics['break_even_roas'], 2, ',', '.').'x', '= 1 / Biên gộp; ROAS thấp hơn là lỗ'],
                ['Giá trị đơn trung bình (AOV)', $money($unitEconomics['aov']), number_format($unitEconomics['orders_per_customer'], 2, ',', '.').' đơn / khách'],
                ['LTV (lợi nhuận gộp vòng đời)', $money($unitEconomics['ltv']), '= AOV × Biên gộp × Số đơn / khách'],
                ['CAC (chi phí có 1 khách mới)', $money($unitEconomics['cac']), $unitEconomics['acquired_customers'].' khách mới từ quảng cáo'],
                ['LTV / CAC', $ltvCac === null ? '—' : number_format($ltvCac, 2, ',', '.').'x', 'Mục tiêu ≥ '.\App\Services\Analytics\FunnelReportService::TARGET_LTV_CAC.($ltvCac === null ? '' : ($ltvCac >= \App\Services\Analytics\FunnelReportService::TARGET_LTV_CAC ? ' · Đạt' : ' · Chưa đạt'))],
                ['CPC hòa vốn', $money($unitEconomics['break_even_cpc']), '= AOV × Biên gộp × CR'],
                ['CPC tối đa (giữ LTV/CAC ≥ 3)', $money($unitEconomics['max_cpc']), '= LTV × CR / 3'],
            ];
        @endphp
        <dl class="space-y-3">
            @foreach($items as [$label, $value, $hint])
                <div class="flex justify-between gap-4 border-b border-[#efe8e3] pb-2 last:border-0">
                    <dt class="text-sm text-[#3d3d3d]">{{ $label }}<span class="block text-[11px] text-[#9a9490]">{{ $hint }}</span></dt>
                    <dd class="text-sm font-bold text-[#3d3d3d] whitespace-nowrap">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</div>
