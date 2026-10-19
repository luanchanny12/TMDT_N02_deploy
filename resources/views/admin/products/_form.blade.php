@php
    $isEdit = isset($product);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Main Info --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Name --}}
        <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
            <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Thông tin cơ bản</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Tên sản phẩm *</label>
                    <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                           placeholder="Nhập tên sản phẩm..."
                           class="w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Mô tả sản phẩm</label>
                    <textarea name="description" rows="5"
                              placeholder="Mô tả chi tiết sản phẩm..."
                              class="w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all">{{ old('description', $product->description ?? '') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
            <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Giá bán</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Giá gốc (₫) *</label>
                    <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" required min="0"
                           placeholder="0"
                           class="w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all">
                    @error('price')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Giá khuyến mãi (₫)</label>
                    <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price ?? '') }}" min="0"
                           placeholder="Để trống nếu không giảm"
                           class="w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all">
                    @error('sale_price')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Khuyến mãi kết thúc lúc</label>
                    <input type="datetime-local" name="sale_ends_at"
                           value="{{ old('sale_ends_at', isset($product) && $product->sale_ends_at ? $product->sale_ends_at->format('Y-m-d\TH:i') : '') }}"
                           class="w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all">
                    <label class="mt-2 flex items-center gap-2 text-xs text-[#6b6560]">
                        <input type="checkbox" name="flash_sale" value="1" class="rounded border-[#efe8e3] text-[#b8847e]" @checked(old('flash_sale', isset($product) && $product->sale_ends_at))>
                        Đưa vào Flash Sale (trang chủ đếm ngược tới thời điểm này; hết giờ tự về giá gốc)
                    </label>
                    @error('sale_ends_at')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2" x-data="{ price: {{ (int) old('price', $product->price ?? 0) }}, sale: @js(old('sale_price', $product->sale_price ?? null)), cost: @js(old('cost_price', $product->cost_price ?? null)), get margin() { const p = Number(this.sale) > 0 ? Number(this.sale) : Number(this.price); return (this.cost === null || this.cost === '' || !p) ? null : Math.round((p - Number(this.cost)) / p * 1000) / 10; } }"
                     @input.window="if ($event.target.name === 'price') price = $event.target.value; if ($event.target.name === 'sale_price') sale = $event.target.value">
                    <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Giá vốn (₫)</label>
                    <input type="number" name="cost_price" x-model="cost" min="0" placeholder="Giá nhập / giá thành — chỉ admin thấy"
                           class="w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all">
                    <p class="text-xs mt-1" :class="margin !== null && margin < 0 ? 'text-red-600' : 'text-[#9a9490]'"
                       x-text="margin === null ? 'Dùng để tính lợi nhuận gộp và ROAS hòa vốn.' : 'Biên lợi nhuận gộp: ' + margin + '%'"></p>
                    @error('cost_price')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
        {{-- Variants (Size/Màu) — dùng cho cả thêm mới và sửa --}}
        @php
            $variantRows = old('variants', $isEdit
                ? $product->variants()->get(['id', 'size', 'color', 'stock', 'price'])
                    ->map(fn ($v) => ['id' => $v->id, 'size' => $v->size, 'color' => $v->color, 'stock' => $v->stock, 'price' => $v->price])
                    ->values()->all()
                : []);
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6" data-testid="variants-section" x-data="{
            rows: @js(array_values($variantRows)),
            sizes: '',
            colors: '',
            stock: 10,
            generate() {
                const split = v => v.split(',').map(s => s.trim()).filter(Boolean);
                const sizes = split(this.sizes).length ? split(this.sizes) : [''];
                const colors = split(this.colors).length ? split(this.colors) : [''];
                for (const size of sizes) {
                    for (const color of colors) {
                        if (!size && !color) continue;
                        if (this.rows.some(r => (r.size || '') === size && (r.color || '') === color)) continue;
                        this.rows.push({ id: null, size, color, stock: Number(this.stock) || 0, price: null });
                    }
                }
            },
            get totalStock() { return this.rows.reduce((t, r) => t + (Number(r.stock) || 0), 0); }
        }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-serif text-lg font-semibold text-[#3d3d3d]">Biến thể Size/Màu</h3>
                <button type="button" @click="rows.push({id:null,size:'',color:'',stock:0,price:null})"
                        class="text-sm text-[#b8847e] font-medium hover:underline">+ Thêm 1 dòng</button>
            </div>

            {{-- Tạo nhanh mọi tổ hợp Size × Màu --}}
            <div class="grid grid-cols-12 gap-3 items-end bg-[#faf7f4] rounded-lg p-3 mb-4">
                <div class="col-span-12 sm:col-span-4">
                    <label class="block text-xs text-[#9a9490] mb-1">Các size (phẩy ngăn cách)</label>
                    <input type="text" x-model="sizes" placeholder="S, M, L, XL" class="w-full border border-[#efe8e3] rounded-lg px-3 py-2 text-sm bg-white">
                </div>
                <div class="col-span-12 sm:col-span-4">
                    <label class="block text-xs text-[#9a9490] mb-1">Các màu</label>
                    <input type="text" x-model="colors" placeholder="Đen, Trắng" class="w-full border border-[#efe8e3] rounded-lg px-3 py-2 text-sm bg-white">
                </div>
                <div class="col-span-6 sm:col-span-2">
                    <label class="block text-xs text-[#9a9490] mb-1">Tồn kho mỗi dòng</label>
                    <input type="number" min="0" x-model.number="stock" class="w-full border border-[#efe8e3] rounded-lg px-3 py-2 text-sm bg-white">
                </div>
                <div class="col-span-6 sm:col-span-2">
                    <button type="button" @click="generate()" class="w-full bg-[#3d3a37] text-white rounded-lg py-2 text-sm hover:bg-[#b8847e] transition">Tạo tổ hợp</button>
                </div>
            </div>

            <div class="space-y-3">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="grid grid-cols-12 gap-3 items-end">
                        <div class="col-span-3">
                            <label class="block text-xs text-[#9a9490] mb-1">Size</label>
                            <input type="text" :name="'variants['+index+'][size]'" x-model="row.size" placeholder="S/M/L"
                                   class="w-full border border-[#efe8e3] rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-[#9a9490] mb-1">Màu</label>
                            <input type="text" :name="'variants['+index+'][color]'" x-model="row.color" placeholder="Đen/Trắng"
                                   class="w-full border border-[#efe8e3] rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-[#9a9490] mb-1">Tồn kho</label>
                            <input type="number" min="0" :name="'variants['+index+'][stock]'" x-model.number="row.stock"
                                   class="w-full border border-[#efe8e3] rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-[#9a9490] mb-1">Giá riêng (₫)</label>
                            <input type="number" min="0" :name="'variants['+index+'][price]'" x-model="row.price" placeholder="Theo SP"
                                   class="w-full border border-[#efe8e3] rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-1">
                            <template x-if="row.id">
                                <input type="hidden" :name="'variants['+index+'][id]'" :value="row.id">
                            </template>
                            <button type="button" @click="rows.splice(index,1)"
                                    class="w-full text-red-500 hover:text-red-700 text-sm py-2" title="Xóa dòng">✕</button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Gửi mảng rỗng khi xóa hết biến thể để server xóa các biến thể cũ --}}
            <input type="hidden" name="variants_submitted" value="1">

            <p class="text-xs text-[#9a9490] mt-3" x-show="rows.length">
                <span x-text="rows.length"></span> biến thể · tổng tồn kho <strong x-text="totalStock"></strong>
                (ô "Tồn kho" của sản phẩm sẽ tự cập nhật bằng tổng này).
            </p>
            <p class="text-xs text-[#9a9490] mt-3" x-show="!rows.length">
                Sản phẩm không có size/màu thì bỏ trống phần này. Khi có biến thể, khách phải chọn size/màu và tồn kho tính theo từng dòng.
            </p>
        </div>


    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">

        {{-- Cover image --}}
        <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6"
             x-data="{ preview: @js($isEdit ? ($product->image_url ?? '') : '') }">
            <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Ảnh bìa</h3>

            <div x-show="preview" class="mb-3">
                <img :src="preview" alt="Xem trước ảnh bìa"
                     class="w-28 h-28 object-cover rounded-lg border border-[#efe8e3]">
            </div>

            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   @change="
                       const file = $event.target.files[0];
                       if (file) {
                           const reader = new FileReader();
                           reader.onload = e => preview = e.target.result;
                           reader.readAsDataURL(file);
                       }
                   "
                   class="w-full border border-[#efe8e3] rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-[#faf7f4] file:text-[#b8847e] hover:file:bg-[#f5f0ec]">
            @error('image')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-[#9a9490] mt-2">JPG, PNG hoặc WebP, tối đa 4MB (ảnh từ điện thoại chụp thường nặng — hãy chọn ảnh dưới 4MB). Chọn file mới nếu muốn thay ảnh đang có; thêm nhiều ảnh khác ở khu vực "Ảnh sản phẩm" phía dưới (khi sửa).</p>
        </div>

        {{-- Category + Stock --}}
        <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
            <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Phân loại & Kho hàng</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Danh mục *</label>
                    <select name="category_id" required
                            class="w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all bg-white">
                        <option value="">-- Chọn danh mục --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Số lượng tồn kho *</label>
                    <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required min="0"
                           class="w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all">
                    @error('stock')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#3d3d3d] mb-1.5">Thương hiệu</label>
                    <input type="text" name="brand" value="{{ old('brand', $product->brand ?? '') }}"
                           placeholder="VD: Apple, Samsung..."
                           class="w-full border border-[#efe8e3] rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all">
                    @error('brand')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] p-6">
            <h3 class="font-serif text-lg font-semibold text-[#3d3d3d] mb-4">Trạng thái</h3>

            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="radio" name="status" value="active"
                           {{ old('status', $product->status ?? 'active') === 'active' ? 'checked' : '' }}
                           class="w-4 h-4 border-gray-300 text-[#b8847e] focus:ring-[#c9a9a6]">
                    <span class="text-sm text-[#3d3d3d]">Đang bán</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="radio" name="status" value="inactive"
                           {{ old('status', $product->status ?? '') === 'inactive' ? 'checked' : '' }}
                           class="w-4 h-4 border-gray-300 text-[#b8847e] focus:ring-[#c9a9a6]">
                    <span class="text-sm text-[#3d3d3d]">Ngừng bán</span>
                </label>
            </div>
        </div>
    </div>
</div>

{{-- Actions --}}
<div class="flex items-center justify-end gap-3 mt-6">
    <a href="{{ route('admin.products.index') }}"
       class="px-5 py-2.5 border border-[#c9a9a6] text-[#b8847e] rounded-lg text-sm font-medium hover:bg-[#e8c4c4] transition-colors">
        Hủy
    </a>
    <button type="submit"
            class="px-5 py-2.5 bg-[#b8847e] text-white rounded-lg text-sm font-medium hover:bg-[#a6736d] transition-colors btn-shine">
        {{ $isEdit ? 'Cập nhật' : 'Thêm sản phẩm' }}
    </button>
</div>
