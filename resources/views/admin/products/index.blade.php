<x-layouts.admin :title="'Quản lý sản phẩm'" :header="'Sản phẩm'">

    {{-- Header & Toolbar --}}
    <div class="mb-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="font-serif text-2xl font-bold text-[#2c2a28]">Danh sách sản phẩm</h1>
                <p class="text-xs text-[#9a9490] mt-0.5">Quản lý kho hàng, giá niêm yết và biến thể thời trang SocialShop</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.products.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-gradient-to-r from-[#b8847e] to-[#a6736d] shadow-sm hover:shadow-md hover:from-[#a6736d] hover:to-[#95625c] transition-all transform active:scale-98 whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Thêm sản phẩm</span>
                </a>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="bg-white p-4 rounded-2xl border border-[#efe8e3] shadow-xs">
            <form action="{{ route('admin.products.index') }}" method="GET" class="flex flex-col md:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Tìm theo tên sản phẩm, mã SKU..."
                           class="w-full pl-10 pr-10 py-2.5 border border-[#efe8e3] rounded-xl bg-[#faf7f4]/40 focus:bg-white focus:ring-2 focus:ring-[#b8847e]/20 focus:border-[#b8847e] transition-all text-sm placeholder:text-[#9a9490]">
                    <svg class="absolute left-3.5 top-3 h-4 w-4 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    @if(request('search'))
                        <a href="{{ route('admin.products.index', request()->except('search')) }}"
                           class="absolute right-3.5 top-2.5 text-xs text-[#9a9490] hover:text-[#2c2a28] p-0.5">✕</a>
                    @endif
                </div>

                <div class="w-full md:w-56">
                    <select name="category"
                            class="w-full border border-[#efe8e3] rounded-xl px-3.5 py-2.5 text-sm bg-[#faf7f4]/40 focus:bg-white focus:ring-2 focus:ring-[#b8847e]/20 focus:border-[#b8847e] transition-all text-[#2c2a28]">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <button type="submit"
                            class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-[#2c2a28] text-white rounded-xl text-sm font-medium hover:bg-black transition-colors shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span>Lọc</span>
                    </button>

                    @if(request('search') || request('category'))
                        <a href="{{ route('admin.products.index') }}"
                           class="inline-flex items-center justify-center px-3.5 py-2.5 bg-neutral-100 text-[#6b6560] rounded-xl text-sm font-medium hover:bg-neutral-200 transition-colors"
                           title="Đặt lại bộ lọc">
                            <span>Xóa lọc</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Products Table --}}
    @if($products->count())
        <div class="bg-white rounded-2xl shadow-xs border border-[#efe8e3] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[#faf7f4]/80 border-b border-[#efe8e3]">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Sản phẩm</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Danh mục</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Giá bán</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Tồn kho</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#78716c] uppercase tracking-wider">Trạng thái</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-[#78716c] uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#efe8e3]/70">
                        @foreach($products as $product)
                            <tr class="hover:bg-[#faf7f4]/60 transition-colors group">
                                {{-- Product Name + Image --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="relative w-12 h-12 rounded-xl overflow-hidden bg-neutral-100 border border-[#efe8e3] flex-shrink-0 shadow-2xs">
                                            @if($product->image_url)
                                                <img src="{{ $product->image_url }}"
                                                     alt="{{ $product->name }}"
                                                     loading="lazy"
                                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                            @else
                                                <div class="w-full h-full bg-gradient-to-br from-[#f5f0ec] to-[#efe8e3] flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0 max-w-[260px]">
                                            <a href="{{ route('admin.products.edit', $product) }}"
                                               class="font-semibold text-[#2c2a28] hover:text-[#b8847e] transition-colors truncate block text-sm"
                                               title="{{ $product->name }}">
                                                {{ $product->name }}
                                            </a>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                @if($product->brand)
                                                    <span class="text-xs text-[#9a9490]">{{ $product->brand }}</span>
                                                    <span class="text-xs text-neutral-300">•</span>
                                                @endif
                                                <span class="text-2xs text-[#9a9490]">ID: #{{ $product->id }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Category --}}
                                <td class="px-5 py-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium bg-[#faf7f4] text-[#6b6560] border border-[#efe8e3]">
                                        {{ $product->category->name ?? 'Chưa phân loại' }}
                                    </span>
                                </td>

                                {{-- Price --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($product->sale_price)
                                        <p class="font-bold text-[#b8847e] text-sm">{{ number_format($product->sale_price, 0, ',', '.') }}₫</p>
                                        <p class="text-xs text-[#9a9490] line-through">{{ number_format($product->price, 0, ',', '.') }}₫</p>
                                    @else
                                        <p class="font-bold text-[#2c2a28] text-sm">{{ number_format($product->price, 0, ',', '.') }}₫</p>
                                    @endif
                                </td>

                                {{-- Stock --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($product->stock <= 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Hết hàng (0)
                                        </span>
                                    @elseif($product->stock <= 5)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Còn {{ $product->stock }}
                                        </span>
                                    @else
                                        <span class="font-semibold text-[#2c2a28]">
                                            {{ $product->stock }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Status Toggle --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($product->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Đang bán
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span>
                                            Ngừng bán
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        {{-- Toggle Status --}}
                                        <form action="{{ route('admin.products.toggle', $product) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="p-1.5 rounded-lg text-[#6b6560] hover:text-amber-700 hover:bg-amber-50 border border-transparent hover:border-amber-200 transition-colors"
                                                    title="{{ $product->status === 'active' ? 'Tạm ẩn sản phẩm' : 'Kích hoạt lại' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                </svg>
                                            </button>
                                        </form>

                                        {{-- Edit Link --}}
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-[#b8847e] hover:text-white hover:bg-[#b8847e] border border-[#efe8e3] hover:border-[#b8847e] transition-colors"
                                           title="Chỉnh sửa sản phẩm">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span>Sửa</span>
                                        </a>

                                        {{-- Gallery Link --}}
                                        <a href="{{ route('admin.products.edit', $product) }}#gallery"
                                           class="p-1.5 rounded-lg text-[#6b6560] hover:text-[#b8847e] hover:bg-[#faf7f4] border border-[#efe8e3] transition-colors"
                                           title="Quản lý bộ sưu tập ảnh">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </a>

                                        {{-- Delete Button --}}
                                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-1.5 rounded-lg text-[#6b6560] hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-colors"
                                                    title="Xóa sản phẩm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="px-6 py-4 border-t border-[#efe8e3] bg-[#faf7f4]/40 flex items-center justify-between">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="bg-white rounded-2xl border border-[#efe8e3] p-12 text-center shadow-xs">
            <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-[#faf7f4] to-[#efe8e3] rounded-2xl flex items-center justify-center text-[#b8847e] shadow-inner">
                <svg class="h-10 w-10 text-[#c9a9a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <h2 class="font-serif text-xl font-bold text-[#2c2a28] mb-1.5">Chưa có sản phẩm nào</h2>
            <p class="text-[#9a9490] mb-6 max-w-sm mx-auto text-sm">
                @if(request('search') || request('category'))
                    Không tìm thấy sản phẩm phù hợp với bộ lọc hiện tại. Hãy thử tìm từ khóa khác.
                @else
                    Bắt đầu bằng cách thêm sản phẩm thời trang đầu tiên vào cửa hàng SocialShop của bạn.
                @endif
            </p>
            <div class="flex items-center justify-center gap-3">
                @if(request('search') || request('category'))
                    <a href="{{ route('admin.products.index') }}"
                       class="px-5 py-2.5 rounded-xl text-sm font-medium text-[#2c2a28] bg-neutral-100 hover:bg-neutral-200 transition-colors">
                        Xóa bộ lọc
                    </a>
                @endif
                <a href="{{ route('admin.products.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-medium text-sm text-white bg-gradient-to-r from-[#b8847e] to-[#a6736d] hover:from-[#a6736d] hover:to-[#95625c] transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Thêm sản phẩm</span>
                </a>
            </div>
        </div>
    @endif

</x-layouts.admin>
