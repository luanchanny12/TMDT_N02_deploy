<x-layouts.admin :title="'Cài đặt cửa hàng'" :header="'Cài đặt cửa hàng'">
    @php
        $input = 'w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all';
    @endphp

    <form action="{{ route('admin.settings.update') }}" method="POST"
          x-data="{ enabled: {{ old('tax_enabled', $taxEnabled) ? 'true' : 'false' }}, rate: {{ (float) old('tax_rate', $taxRate) }} }">
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
                    <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-5">Phí vận chuyển</h3>
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
                            <dd x-text="new Intl.NumberFormat('vi-VN').format(Math.round(1000000 * rate / 100)) + '₫'"></dd>
                        </div>
                        <div class="flex justify-between border-t border-[#efe8e3] pt-2 font-bold">
                            <dt>Tổng cộng</dt>
                            <dd class="text-[#b8847e]" x-text="new Intl.NumberFormat('vi-VN').format(1000000 + {{ $example['shipping_fee'] }} + (enabled ? Math.round(1000000 * rate / 100) : 0)) + '₫'"></dd>
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
</x-layouts.admin>
