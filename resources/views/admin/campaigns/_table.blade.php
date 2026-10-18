@php
    $money = fn ($v) => number_format((int) $v, 0, ',', '.').'₫';
    $th = 'px-4 py-3 text-xs font-semibold text-[#9a9490] uppercase tracking-wide';
    $compact = $compact ?? false;
@endphp
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-[#faf7f4]">
            <tr>
                <th class="{{ $th }} text-left">Chiến dịch</th>
                <th class="{{ $th }} text-right">Chi phí</th>
                <th class="{{ $th }} text-right">Truy cập</th>
                <th class="{{ $th }} text-right">Đơn</th>
                <th class="{{ $th }} text-right">Doanh thu</th>
                <th class="{{ $th }} text-right">CR</th>
                <th class="{{ $th }} text-right">CPA</th>
                <th class="{{ $th }} text-right">ROAS</th>
                @unless($compact)
                    <th class="{{ $th }} text-right">Thao tác</th>
                @endunless
            </tr>
        </thead>
        <tbody class="divide-y divide-[#efe8e3]">
            @forelse($campaigns as $row)
                @php $c = $row['model']; @endphp
                <tr class="align-top">
                    <td class="px-4 py-3">
                        <p class="font-medium text-[#3d3d3d]">{{ $c->name }}</p>
                        <p class="text-xs text-[#9a9490]">
                            {{ $c->platformLabel() }} ·
                            <span class="{{ $c->status === 'active' ? 'text-green-600' : 'text-[#9a9490]' }}">
                                {{ ['active' => 'Đang chạy', 'paused' => 'Tạm dừng', 'ended' => 'Đã kết thúc'][$c->status] ?? $c->status }}
                            </span>
                        </p>
                        @unless($compact)
                            <div class="mt-2 flex items-center gap-2" x-data="{ copied: false }">
                                <input type="text" readonly value="{{ $c->trackingUrl() }}"
                                       class="w-72 max-w-full text-xs border border-[#efe8e3] rounded px-2 py-1 bg-[#faf7f4] text-[#3d3d3d]"
                                       onclick="this.select()">
                                <button type="button"
                                        @click="navigator.clipboard?.writeText(@js($c->trackingUrl())); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="text-xs px-2 py-1 rounded border border-[#efe8e3] hover:border-[#b8847e] text-[#b8847e]"
                                        x-text="copied ? 'Đã chép ✓' : 'Chép link'">Chép link</button>
                            </div>
                        @endunless
                    </td>
                    <td class="px-4 py-3 text-right">{{ $money($row['spend']) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row['visits'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">{{ $row['orders'] }}</td>
                    <td class="px-4 py-3 text-right">{{ $money($row['revenue']) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row['conversion_rate'], 2, ',', '.') }}%</td>
                    <td class="px-4 py-3 text-right">{{ $row['cpa'] !== null ? $money($row['cpa']) : '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold {{ $row['roas'] === null ? '' : ($row['roas'] >= 1 ? 'text-green-600' : 'text-red-600') }}">
                        {{ $row['roas'] !== null ? number_format($row['roas'], 2, ',', '.').'x' : '—' }}
                    </td>
                    @unless($compact)
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.campaigns.edit', $c) }}" class="text-[#b8847e] hover:underline">Sửa</a>
                            <form action="{{ route('admin.campaigns.destroy', $c) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Xóa chiến dịch {{ addslashes($c->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ml-2 text-red-600 hover:underline">Xóa</button>
                            </form>
                        </td>
                    @endunless
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $compact ? 8 : 9 }}" class="px-4 py-8 text-center text-[#9a9490]">
                        Chưa có chiến dịch nào.
                        <a href="{{ route('admin.campaigns.create') }}" class="text-[#b8847e] hover:underline">Tạo chiến dịch đầu tiên</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
