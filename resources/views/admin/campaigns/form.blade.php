@php
    $isEdit = $campaign->exists;
    $input = 'w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all';
    $label = 'block text-sm font-medium text-[#3d3d3d] mb-1.5';
@endphp
<x-layouts.admin :title="$isEdit ? 'Sửa chiến dịch' : 'Tạo chiến dịch'" :header="$isEdit ? 'Sửa chiến dịch' : 'Tạo chiến dịch quảng cáo'">
    <form method="POST" action="{{ $isEdit ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store') }}"
          x-data="{
              base: @js(rtrim(config('app.url'), '/')),
              name: @js(old('name', $campaign->name)),
              platform: @js(old('platform', $campaign->platform)),
              source: @js(old('utm_source', $campaign->utm_source)),
              medium: @js(old('utm_medium', $campaign->utm_medium)),
              code: @js(old('utm_campaign', $campaign->utm_campaign)),
              path: @js(old('landing_path', $campaign->landing_path)),
              slug(v) { return (v || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/gi, 'd').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); },
              get url() {
                  const p = '/' + (this.path || '/').replace(/^\/+/, '');
                  const q = new URLSearchParams({ utm_source: this.slug(this.source || this.platform), utm_medium: this.slug(this.medium || 'cpc'), utm_campaign: this.slug(this.code || this.name) });
                  return this.base + p + (p.includes('?') ? '&' : '?') + q.toString();
              }
          }">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6 space-y-4">
                    <h3 class="font-serif text-lg font-semibold text-[#3d3d3d]">Thông tin chiến dịch</h3>

                    <div>
                        <label class="{{ $label }}">Tên chiến dịch *</label>
                        <input type="text" name="name" x-model="name" required maxlength="255" class="{{ $input }}" placeholder="VD: Sale thu 2026 - Facebook">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $label }}">Nền tảng *</label>
                            <select name="platform" x-model="platform" class="{{ $input }} bg-white">
                                @foreach(\App\Models\Campaign::PLATFORMS as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('platform') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Trạng thái *</label>
                            <select name="status" class="{{ $input }} bg-white">
                                @foreach(['active' => 'Đang chạy', 'paused' => 'Tạm dừng', 'ended' => 'Đã kết thúc'] as $key => $name)
                                    <option value="{{ $key }}" @selected(old('status', $campaign->status) === $key)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="{{ $label }}">Trang đích *</label>
                        <input type="text" name="landing_path" x-model="path" required class="{{ $input }}" placeholder="/ hoặc /danh-muc/giay-dep hoặc /products/ao-polo-nam">
                        <p class="text-xs text-[#9a9490] mt-1">Đường dẫn trên website mà quảng cáo trỏ tới.</p>
                        @error('landing_path') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $label }}">Ngày bắt đầu</label>
                            <input type="date" name="starts_on" value="{{ old('starts_on', $campaign->starts_on?->format('Y-m-d')) }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Ngày kết thúc</label>
                            <input type="date" name="ends_on" value="{{ old('ends_on', $campaign->ends_on?->format('Y-m-d')) }}" class="{{ $input }}">
                            @error('ends_on') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="{{ $label }}">Chi phí quảng cáo đã chi (₫) *</label>
                        <input type="number" name="spend" min="0" step="1000" required value="{{ old('spend', $campaign->spend ?? 0) }}" class="{{ $input }}">
                        <p class="text-xs text-[#9a9490] mt-1">Cập nhật theo số liệu trong Meta Ads Manager / TikTok Ads / Google Ads để tính ROAS.</p>
                        @error('spend') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $label }}">Ghi chú</label>
                        <textarea name="note" rows="3" class="{{ $input }}" placeholder="Nội dung quảng cáo, đối tượng nhắm tới...">{{ old('note', $campaign->note) }}</textarea>
                    </div>
                </div>

                <details class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6" @if($errors->hasAny(['utm_source', 'utm_medium', 'utm_campaign'])) open @endif>
                    <summary class="font-serif text-lg font-semibold text-[#3d3d3d] cursor-pointer">Tham số UTM (nâng cao)</summary>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="{{ $label }}">utm_source</label>
                            <input type="text" name="utm_source" x-model="source" class="{{ $input }}" :placeholder="platform">
                        </div>
                        <div>
                            <label class="{{ $label }}">utm_medium</label>
                            <input type="text" name="utm_medium" x-model="medium" class="{{ $input }}" placeholder="cpc">
                        </div>
                        <div>
                            <label class="{{ $label }}">utm_campaign</label>
                            <input type="text" name="utm_campaign" x-model="code" class="{{ $input }}" :placeholder="slug(name)">
                            @error('utm_campaign') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-xs text-[#9a9490] mt-2">Để trống sẽ tự tạo từ nền tảng và tên chiến dịch.</p>
                </details>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
                    <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-3">Link quảng cáo</h3>
                    <p class="text-xs text-[#9a9490] mb-2">Dán link này vào quảng cáo / bio / mô tả video:</p>
                    <p class="text-xs break-all bg-[#faf7f4] border border-[#efe8e3] rounded-lg p-3 text-[#3d3d3d]" x-text="url"></p>
                </div>
                <button type="submit" class="w-full bg-[#b8847e] text-white py-3 rounded-lg font-semibold hover:bg-[#a6736d] transition">
                    {{ $isEdit ? 'Lưu thay đổi' : 'Tạo chiến dịch' }}
                </button>
                <a href="{{ route('admin.campaigns.index') }}" class="block text-center text-sm text-[#9a9490] hover:text-[#3d3d3d]">Quay lại</a>
            </div>
        </div>
    </form>
</x-layouts.admin>
