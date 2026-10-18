@php
    $isEdit = $zone->exists;
    $selected = old('provinces', $zone->provinces ?? []);
    $input = 'w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all';
@endphp
<x-layouts.admin :title="$isEdit ? 'Sửa khu vực giao hàng' : 'Thêm khu vực giao hàng'" :header="$isEdit ? 'Sửa khu vực giao hàng' : 'Thêm khu vực giao hàng'">
    <form method="POST" action="{{ $isEdit ? route('admin.shipping-zones.update', $zone) : route('admin.shipping-zones.store') }}"
          x-data="{ selected: @js(array_values($selected)) }">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-serif text-lg font-semibold text-[#3d3d3d]">Tỉnh / thành phố trong khu vực</h3>
                    <span class="text-sm text-[#9a9490]">Đã chọn <strong x-text="selected.length"></strong></span>
                </div>
                @error('provinces') <p class="text-red-500 text-sm mb-3">{{ $message }}</p> @enderror

                @foreach(config('vietnam.regions') as $region => $provinces)
                    @php $free = array_values(array_diff($provinces, array_keys($taken))); @endphp
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-[#3d3d3d]">{{ $region }}</p>
                            <button type="button" class="text-xs text-[#b8847e] hover:underline"
                                    @click="selected = [...new Set([...selected, ...@js($free)])]">Chọn cả miền</button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach($provinces as $province)
                                @php $owner = $taken[$province] ?? null; @endphp
                                <label class="flex items-start gap-2 text-sm rounded-lg border border-[#efe8e3] px-3 py-2 {{ $owner ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:border-[#c9a9a6]' }}"
                                       @if($owner) title="Đang thuộc khu vực: {{ $owner }}" @endif>
                                    <input type="checkbox" name="provinces[]" value="{{ $province }}" x-model="selected" @disabled($owner)
                                           class="mt-0.5 rounded border-[#efe8e3] text-[#b8847e] focus:ring-[#c9a9a6]">
                                    <span>
                                        {{ $province }}
                                        @if($owner)<span class="block text-[11px] text-[#9a9490]">{{ $owner }}</span>@endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Tên khu vực *</label>
                        <input type="text" name="name" required maxlength="100" value="{{ old('name', $zone->name) }}" class="{{ $input }}" placeholder="VD: Miền Trung">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Phí ship (₫) *</label>
                        <input type="number" name="fee" min="0" step="1000" required value="{{ old('fee', $zone->fee) }}" class="{{ $input }}">
                        @error('fee') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Miễn phí ship từ (₫)</label>
                        <input type="number" name="free_shipping_threshold" min="0" step="1000" value="{{ old('free_shipping_threshold', $zone->free_shipping_threshold) }}" class="{{ $input }}" placeholder="Để trống = theo mặc định">
                        @error('free_shipping_threshold') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Thứ tự hiển thị</label>
                        <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $zone->sort_order ?? 0) }}" class="{{ $input }}">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $zone->is_active)) class="rounded border-[#efe8e3] text-[#b8847e]">
                        Đang áp dụng
                    </label>
                </div>
                <button type="submit" class="w-full bg-[#b8847e] text-white py-3 rounded-lg font-semibold hover:bg-[#a6736d] transition">
                    {{ $isEdit ? 'Lưu thay đổi' : 'Thêm khu vực' }}
                </button>
                <a href="{{ route('admin.settings.index') }}" class="block text-center text-sm text-[#9a9490] hover:text-[#3d3d3d]">Quay lại Cài đặt</a>
            </div>
        </div>
    </form>
</x-layouts.admin>
