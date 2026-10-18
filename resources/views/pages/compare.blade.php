<x-layouts.app title="So sánh sản phẩm - SocialShop">
    @php
        $money = fn ($v) => number_format((int) $v, 0, ',', '.').'₫';
        $badges = [
            'cheapest' => ['Giá tốt nhất', 'bg-green-100 text-green-700'],
            'discount' => ['Giảm nhiều nhất', 'bg-rose-100 text-rose-700'],
            'rating' => ['Đánh giá cao nhất', 'bg-amber-100 text-amber-700'],
            'bestseller' => ['Bán chạy nhất', 'bg-sky-100 text-sky-700'],
        ];
        $badgesFor = fn ($id) => collect($winners)->filter(fn ($w) => $w === $id)->keys();
    @endphp

    <div class="max-w-7xl mx-auto px-4 py-8" x-data="{ diffOnly: false }">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
            <div>
                <h1 class="font-serif text-3xl text-[#3d3d3d]">So sánh sản phẩm</h1>
                <p class="text-sm text-[#9a9490] mt-1">So sánh giá, giảm giá, đánh giá, lượt bán, size/màu còn hàng để chọn món phù hợp nhất (tối đa 4 sản phẩm).</p>
            </div>
            @if($products->isNotEmpty())
                <div class="flex items-center gap-4">
                    @if($products->count() > 1)
                        <label class="flex items-center gap-2 text-sm text-[#3d3d3d] cursor-pointer">
                            <input type="checkbox" x-model="diffOnly" class="rounded border-[#efe8e3] text-[#b8847e] focus:ring-[#c9a9a6]">
                            Chỉ hiện điểm khác nhau
                        </label>
                    @endif
                    <form action="{{ route('compare.clear') }}" method="POST">
                        @csrf
                        <button class="text-sm text-red-500 hover:text-red-700">Xóa tất cả</button>
                    </form>
                </div>
            @endif
        </div>

        @if($products->isEmpty())
            <div class="text-center py-12 bg-white rounded-xl border border-[#efe8e3]">
                <p class="text-[#3d3d3d] font-medium mb-1">Chưa có sản phẩm nào để so sánh</p>
                <p class="text-sm text-[#9a9490] mb-5">Bấm biểu tượng <strong>⇄ So sánh</strong> trên thẻ sản phẩm hoặc trang chi tiết để thêm.</p>
                <a href="{{ route('products.index') }}" class="bg-[#b8847e] text-white px-6 py-2 rounded-lg hover:bg-[#a6736d] transition">Xem sản phẩm</a>
            </div>
        @else
            {{-- Kết luận nhanh --}}
            @if($products->count() > 1 && collect($winners)->filter()->isNotEmpty())
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6" data-testid="compare-verdict">
                    @foreach($badges as $key => [$label, $class])
                        @php $winner = $products->firstWhere('id', $winners[$key] ?? null); @endphp
                        <div class="bg-white rounded-xl border border-[#efe8e3] p-4">
                            <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full {{ $class }}">{{ $label }}</span>
                            <p class="mt-2 text-sm font-medium text-[#3d3d3d] line-clamp-2">{{ $winner->name ?? 'Ngang nhau' }}</p>
                        </div>
                    @endforeach
                </div>
            @elseif($products->count() === 1)
                <div class="mb-6 rounded-xl border border-[#efe8e3] bg-[#faf7f4] p-4 text-sm text-[#3d3d3d]">
                    Thêm ít nhất <strong>1 sản phẩm nữa</strong> để thấy món nào rẻ hơn, được đánh giá cao hơn hay bán chạy hơn.
                </div>
            @endif

            <div class="overflow-x-auto bg-white rounded-xl border border-[#efe8e3]">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr>
                            <th class="text-left p-4 bg-[#faf7f4] font-serif text-[#3d3d3d] w-40 sticky left-0">Tiêu chí</th>
                            @foreach($products as $product)
                                <th class="p-4 bg-[#faf7f4] min-w-[210px] align-top">
                                    <div class="text-center">
                                        <a href="{{ route('products.show', $product->slug) }}">
                                            @if($product->image_url)
                                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-32 h-32 object-cover mx-auto mb-2 rounded-lg">
                                            @else
                                                <div class="w-32 h-32 bg-[#efe8e3] mx-auto mb-2 rounded-lg"></div>
                                            @endif
                                        </a>
                                        <a href="{{ route('products.show', $product->slug) }}" class="font-serif text-[#3d3d3d] hover:text-[#b8847e] block mb-2">{{ $product->name }}</a>
                                        <div class="flex flex-wrap justify-center gap-1 mb-2">
                                            @foreach($badgesFor($product->id) as $key)
                                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $badges[$key][1] }}">{{ $badges[$key][0] }}</span>
                                            @endforeach
                                        </div>
                                        <form action="{{ route('compare.remove', $product->id) }}" method="POST">
                                            @csrf
                                            <button class="text-red-500 hover:text-red-700 text-xs font-normal">Bỏ khỏi so sánh</button>
                                        </form>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#efe8e3]">
                        <tr>
                            <td class="p-4 font-medium text-[#3d3d3d] sticky left-0 bg-white">Giá bán</td>
                            @foreach($products as $product)
                                <td class="p-4 text-center {{ ($winners['cheapest'] ?? null) === $product->id ? 'bg-green-50' : '' }}">
                                    <span class="text-[#b8847e] font-bold text-lg">{{ $money($product->effectivePrice()) }}</span>
                                    @if($product->sale_price)
                                        <span class="text-gray-400 line-through text-xs block">{{ $money($product->price) }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="p-4 font-medium text-[#3d3d3d] sticky left-0 bg-white">Giảm giá</td>
                            @foreach($products as $product)
                                <td class="p-4 text-center">
                                    @if($product->sale_price)
                                        <span class="font-semibold text-rose-600">-{{ round(($product->price - $product->sale_price) / max(1, $product->price) * 100) }}%</span>
                                        <span class="block text-xs text-[#9a9490]">Tiết kiệm {{ $money($product->price - $product->sale_price) }}</span>
                                    @else
                                        <span class="text-[#9a9490]">Không</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="p-4 font-medium text-[#3d3d3d] sticky left-0 bg-white">Đánh giá</td>
                            @foreach($products as $product)
                                <td class="p-4 text-center {{ ($winners['rating'] ?? null) === $product->id ? 'bg-amber-50' : '' }}">
                                    @if($product->reviews_count)
                                        <span class="text-yellow-500">{{ str_repeat('★', (int) round($product->average_rating)) }}<span class="text-gray-300">{{ str_repeat('★', 5 - (int) round($product->average_rating)) }}</span></span>
                                        <span class="block text-xs text-[#9a9490]">{{ number_format((float) $product->average_rating, 1) }}/5 · {{ $product->reviews_count }} đánh giá</span>
                                    @else
                                        <span class="text-[#9a9490]">Chưa có đánh giá</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="p-4 font-medium text-[#3d3d3d] sticky left-0 bg-white">Đã bán</td>
                            @foreach($products as $product)
                                <td class="p-4 text-center {{ ($winners['bestseller'] ?? null) === $product->id ? 'bg-sky-50' : '' }}">{{ number_format((int) $product->sold_count, 0, ',', '.') }}</td>
                            @endforeach
                        </tr>
                        @foreach($rows as $row)
                            <tr @if($row['same']) x-show="!diffOnly" @endif>
                                <td class="p-4 font-medium text-[#3d3d3d] sticky left-0 bg-white">{{ $row['label'] }}</td>
                                @foreach($row['values'] as $value)
                                    <td class="p-4 text-center text-[#3d3d3d] {{ $row['same'] ? 'text-[#9a9490]' : '' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr x-show="!diffOnly">
                            <td class="p-4 font-medium text-[#3d3d3d] sticky left-0 bg-white align-top">Mô tả</td>
                            @foreach($products as $product)
                                <td class="p-4 text-left text-xs text-[#6b6560] align-top">{{ \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 160) }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="p-4 font-medium text-[#3d3d3d] sticky left-0 bg-white">Mua</td>
                            @foreach($products as $product)
                                <td class="p-4 text-center">
                                    @if($product->stock <= 0)
                                        <span class="text-red-500 text-sm">Hết hàng</span>
                                    @elseif($product->variants->isNotEmpty())
                                        {{-- Có size/màu: phải chọn biến thể ở trang chi tiết --}}
                                        <a href="{{ route('products.show', $product->slug) }}" class="inline-block bg-[#3d3a37] text-white px-4 py-2 rounded-lg hover:bg-[#b8847e] transition text-sm">Chọn size/màu</a>
                                    @else
                                        <form action="{{ route('cart.add') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="bg-[#b8847e] text-white px-4 py-2 rounded-lg hover:bg-[#a6736d] transition text-sm">Thêm vào giỏ</button>
                                        </form>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        @if($suggestions->isNotEmpty())
            <div class="mt-10" data-testid="compare-suggestions">
                <h2 class="font-serif text-xl text-[#3d3d3d] mb-4">{{ $products->isEmpty() ? 'Sản phẩm nổi bật để so sánh' : 'Thêm sản phẩm tương tự để so sánh' }}</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach($suggestions as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
