<x-layouts.admin :title="'Cài đặt cửa hàng'" :header="'Cài đặt cửa hàng'">
    @php
        $input = 'w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all';
    @endphp

    <form action="{{ route('admin.settings.update') }}" method="POST"
          x-data="{ enabled: {{ old('tax_enabled', $taxEnabled) ? 'true' : 'false' }}, taxShip: {{ old('tax_on_shipping', $taxOnShipping) ? 'true' : 'false' }}, rate: {{ (float) old('tax_rate', $taxRate) }} }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- Thuế --}}
                <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
                    <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-1">Thuế giá trị gia tăng (VAT)</h3>
                    <p class="text-sm text-[#9a9490] mb-5">VAT được cộng thêm vào tổng tiền, tính trên giá trị hàng sau khi trừ mã giảm giá.</p>

                    <label class="flex items-center gap-3 mb-5 cursor-pointer">
                        <input type="hidden" name="tax_enabled" value="0">
                        <input type="checkbox" name="tax_enabled" value="1" x-model="enabled"
                               class="h-5 w-5 rounded border-[#efe8e3] text-[#b8847e] focus:ring-[#c9a9a6]">
                        <span class="text-sm font-medium text-[#3d3d3d]">Áp dụng thuế VAT cho đơn hàng</span>
                    </label>

                    <label class="flex items-start gap-3 mb-5 cursor-pointer" :class="enabled ? '' : 'opacity-50'">
                        <input type="hidden" name="tax_on_shipping" value="0">
                        <input type="checkbox" name="tax_on_shipping" value="1" x-model="taxShip"
                               class="mt-0.5 h-5 w-5 rounded border-[#efe8e3] text-[#b8847e] focus:ring-[#c9a9a6]">
                        <span class="text-sm text-[#3d3d3d]">
                            <span class="font-medium">Tính VAT trên cả phí vận chuyển</span>
                            <span class="block text-xs text-[#9a9490]">Phí giao hàng thu của khách là một phần giá trị giao dịch chịu thuế. Tắt nếu phí ship do đơn vị vận chuyển xuất hóa đơn riêng.</span>
                        </span>
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" :class="enabled ? '' : 'opacity-50'">
                        <div>
                            <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Thuế suất (%) *</label>
                            <input type="number" name="tax_rate" step="0.01" min="0" max="100" required x-model.number="rate"
                                   value="{{ old('tax_rate', $taxRate) }}" class="{{ $input }}">
                            <p class="text-xs text-[#9a9490] mt-1">Phổ biến: 8% (giảm thuế theo chính sách) hoặc 10%.</p>
                            @error('tax_rate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Tên hiển thị *</label>
                            <input type="text" name="tax_label" maxlength="50" required
                                   value="{{ old('tax_label', $taxLabel) }}" class="{{ $input }}">
                            @error('tax_label') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Vận chuyển --}}
                <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
                    <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-1">Phí vận chuyển mặc định</h3>
                    <p class="text-sm text-[#9a9490] mb-5">Áp dụng cho tỉnh/thành chưa thuộc khu vực giao hàng nào bên dưới.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Phí ship mặc định (₫) *</label>
                            <input type="number" name="shipping_fee" min="0" step="1000" required
                                   value="{{ old('shipping_fee', $shippingFee) }}" class="{{ $input }}">
                            @error('shipping_fee') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Miễn phí ship từ (₫) *</label>
                            <input type="number" name="free_shipping_threshold" min="0" step="1000" required
                                   value="{{ old('free_shipping_threshold', $freeShippingThreshold) }}" class="{{ $input }}">
                            @error('free_shipping_threshold') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Thông báo --}}
                <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
                    <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-1">Thông báo quản trị</h3>
                    <p class="text-sm text-[#9a9490] mb-4">Báo ở chuông khi có đơn mới, thanh toán, hủy đơn, đánh giá chờ duyệt, cần hoàn tiền và khi sản phẩm sắp hết.</p>
                    <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Cảnh báo sắp hết hàng khi tồn kho ≤</label>
                    <input type="number" name="low_stock_threshold" min="0" value="{{ old('low_stock_threshold', $lowStockThreshold) }}" class="{{ $input }} max-w-xs">
                    @error('low_stock_threshold') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Ví dụ --}}
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
                    <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Ví dụ đơn 1.000.000₫</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-[#9a9490]">Tạm tính</dt><dd>1.000.000₫</dd></div>
                        <div class="flex justify-between"><dt class="text-[#9a9490]">Phí vận chuyển</dt><dd>{{ $example['shipping_fee'] ? number_format($example['shipping_fee'], 0, ',', '.').'₫' : 'Miễn phí' }}</dd></div>
                        <div class="flex justify-between" x-show="enabled">
                            <dt class="text-[#9a9490]">VAT (<span x-text="rate"></span>%)</dt>
                            <dd x-text="new Intl.NumberFormat('vi-VN').format(Math.round((1000000 + (taxShip ? {{ $example['shipping_fee'] }} : 0)) * rate / 100)) + '₫'"></dd>
                        </div>
                        <div class="flex justify-between border-t border-[#efe8e3] pt-2 font-bold">
                            <dt>Tổng cộng</dt>
                            <dd class="text-[#b8847e]" x-text="new Intl.NumberFormat('vi-VN').format(1000000 + {{ $example['shipping_fee'] }} + (enabled ? Math.round((1000000 + (taxShip ? {{ $example['shipping_fee'] }} : 0)) * rate / 100) : 0)) + '₫'"></dd>
                        </div>
                    </dl>
                    <p class="text-xs text-[#9a9490] mt-4">Đơn đã đặt giữ nguyên thuế suất tại thời điểm đặt hàng.</p>
                </div>

                <button type="submit" class="w-full bg-[#b8847e] text-white py-3 rounded-lg font-semibold hover:bg-[#a6736d] transition">
                    Lưu cài đặt
                </button>
            </div>
        </div>
    </form>

    {{-- Khu vực giao hàng --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] mt-6 overflow-hidden" data-testid="shipping-zones">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-6 pb-4">
            <div>
                <h3 class="font-serif text-lg font-semibold text-[#3d3d3d]">Phí ship theo khu vực</h3>
                <p class="text-sm text-[#9a9490]">Mỗi khu vực gồm nhiều tỉnh/thành (34 đơn vị hành chính từ 01/7/2025), có phí và ngưỡng miễn phí riêng.</p>
            </div>
            <a href="{{ route('admin.shipping-zones.create') }}" class="bg-[#b8847e] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#a6736d] transition">+ Thêm khu vực</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#faf7f4]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#9a9490] uppercase">Khu vực</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#9a9490] uppercase">Tỉnh/thành</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-[#9a9490] uppercase">Phí ship</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-[#9a9490] uppercase">Miễn phí từ</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-[#9a9490] uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#efe8e3]">
                    @forelse($zones as $zone)
                        <tr class="align-top {{ $zone->is_active ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3 font-medium text-[#3d3d3d]">
                                {{ $zone->name }}
                                @unless($zone->is_active) <span class="text-xs text-[#9a9490]">(tắt)</span> @endunless
                            </td>
                            <td class="px-4 py-3 text-[#3d3d3d] max-w-md">{{ implode(', ', $zone->provinces ?? []) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($zone->fee, 0, ',', '.') }}₫</td>
                            <td class="px-4 py-3 text-right">{{ $zone->free_shipping_threshold !== null ? number_format($zone->free_shipping_threshold, 0, ',', '.').'₫' : 'Theo mặc định' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.shipping-zones.edit', $zone) }}" class="text-[#b8847e] hover:underline">Sửa</a>
                                <form action="{{ route('admin.shipping-zones.destroy', $zone) }}" method="POST" class="inline" onsubmit="return confirm('Xóa khu vực này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ml-2 text-red-600 hover:underline">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-[#9a9490]">Chưa có khu vực — mọi đơn dùng phí ship mặc định.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($unzonedProvinces)
            <p class="px-6 py-4 text-xs text-[#9a9490] border-t border-[#efe8e3]">
                Đang dùng phí mặc định: {{ implode(', ', $unzonedProvinces) }}
            </p>
        @endif
    </div>
</x-layouts.admin>

