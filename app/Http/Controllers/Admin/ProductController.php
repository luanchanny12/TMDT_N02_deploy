<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    private const VARIANT_RULES = [
        'variants' => 'nullable|array|max:100',
        'variants.*.id' => 'nullable|integer',
        'variants.*.size' => 'nullable|string|max:50',
        'variants.*.color' => 'nullable|string|max:50',
        'variants.*.stock' => 'nullable|integer|min:0|max:1000000',
        'variants.*.price' => 'nullable|integer|min:0',
    ];

    public function index(Request $request)
    {
        $query = Product::with('category', 'images');

        if ($search = $request->input('search')) {
            $query->whereLike('name', "%{$search}%");
        }

        if ($categoryId = $request->input('category')) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->latest()->paginate(12)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'cost_price' => 'nullable|integer|min:0',
            'sale_ends_at' => 'nullable|date|after:now|required_with_all:sale_price,flash_sale',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|integer|min:0',
            'brand' => 'nullable|string',
            'image' => 'nullable|mimes:jpg,jpeg,png,webp|max:4096',
            'status' => 'sometimes|in:active,inactive',
            ...self::VARIANT_RULES,
        ], [
            'image.mimes' => 'Hình ảnh bìa phải là JPG, PNG hoặc WebP.',
            'sale_ends_at.after' => 'Thời điểm kết thúc khuyến mãi phải ở tương lai.',
            'sale_ends_at.required_with_all' => 'Flash Sale cần thời điểm kết thúc.',
            'image.max' => 'Hình ảnh bìa tối đa 4MB.',
        ]);

        $validated['slug'] = Product::uniqueSlug($validated['name']);
        $validated['status'] = $request->input('status', 'active');

        $product = Product::create(collect($validated)->only(['name', 'slug', 'description', 'price', 'sale_price', 'sale_ends_at', 'cost_price', 'category_id', 'stock', 'brand', 'status'])->toArray());

        $this->syncVariants($product, $request);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images/products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Thêm sản phẩm thành công!');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products,name,'.$product->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'cost_price' => 'nullable|integer|min:0',
            'sale_ends_at' => 'nullable|date|after:now|required_with_all:sale_price,flash_sale',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|integer|min:0',
            'brand' => 'nullable|string',
            'image' => 'nullable|mimes:jpg,jpeg,png,webp|max:4096',
            'status' => 'sometimes|in:active,inactive,out_of_stock',
            ...self::VARIANT_RULES,
        ], [
            'image.mimes' => 'Hình ảnh bìa phải là JPG, PNG hoặc WebP.',
            'sale_ends_at.after' => 'Thời điểm kết thúc khuyến mãi phải ở tương lai.',
            'sale_ends_at.required_with_all' => 'Flash Sale cần thời điểm kết thúc.',
            'image.max' => 'Hình ảnh bìa tối đa 4MB.',
        ]);

        $validated['slug'] = Product::uniqueSlug($validated['name'], $product->id);

        $product->update(collect($validated)->only(['name', 'slug', 'description', 'price', 'sale_price', 'sale_ends_at', 'cost_price', 'category_id', 'stock', 'brand', 'status'])->toArray());

        $this->syncVariants($product, $request);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images/products', 'public');

            $oldImages = $product->images()->where('is_primary', true)->get();

            $product->images()->where('is_primary', true)->delete();

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => true,
                'sort_order' => 0,
            ]);

            foreach ($oldImages as $oldImage) {
                $stillUsed = ProductImage::where('image_path', $oldImage->image_path)
                    ->where('id', '!=', $oldImage->id)
                    ->exists();

                if (! $stillUsed && str_starts_with($oldImage->image_path, 'images/products/')) {
                    Storage::disk('public')->delete($oldImage->image_path);
                }
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công!');
    }

    private function syncVariants(Product $product, Request $request): void
    {
        // Form luôn gửi variants_submitted → xóa hết dòng trên form nghĩa là xóa hết biến thể
        if (! $request->has('variants') && ! $request->has('variants_submitted')) {
            return;
        }

        $rows = $request->input('variants', []);
        $keepIds = [];

        foreach ($rows as $row) {
            $size = trim((string) ($row['size'] ?? ''));
            $color = trim((string) ($row['color'] ?? ''));
            $stock = (int) ($row['stock'] ?? 0);
            $price = isset($row['price']) && $row['price'] !== '' ? (int) $row['price'] : null;
            $id = $row['id'] ?? null;

            if ($size === '' && $color === '') {
                continue;
            }

            $variant = $id
                ? ProductVariant::where('product_id', $product->id)->find($id)
                : ProductVariant::where('product_id', $product->id)
                    ->where('size', $size)
                    ->where('color', $color)
                    ->first();

            if ($variant) {
                $variant->update([
                    'size' => $size ?: null,
                    'color' => $color ?: null,
                    'stock' => $stock,
                    'price' => $price,
                ]);
            } else {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $size ?: null,
                    'color' => $color ?: null,
                    'stock' => $stock,
                    'price' => $price,
                ]);
            }

            $keepIds[] = $variant->id;
        }

        ProductVariant::where('product_id', $product->id)
            ->when(! empty($keepIds), fn ($q) => $q->whereNotIn('id', $keepIds))
            ->delete();

        if ($product->variants()->exists()) {
            $product->update([
                'stock' => (int) $product->variants()->sum('stock'),
            ]);
        }
    }

    public function destroy(Product $product)
    {
        // Sản phẩm đã có trong đơn hàng → không xóa cứng (giữ lịch sử đơn), chỉ ngừng kinh doanh
        if ($product->orderItems()->exists()) {
            $product->update(['status' => 'inactive']);

            return redirect()->route('admin.products.index')
                ->with('error', 'Sản phẩm đã có trong đơn hàng nên không thể xóa — đã chuyển sang ngừng kinh doanh.');
        }

        $imagePaths = $product->images()->pluck('image_path');

        $product->delete();

        foreach ($imagePaths as $path) {
            $stillUsed = ProductImage::where('image_path', $path)->exists();

            if (! $stillUsed && str_starts_with($path, 'images/products/')) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã xóa sản phẩm!');
    }

    public function toggleActive(Product $product)
    {
        $newStatus = $product->status === 'active' ? 'inactive' : 'active';
        $product->update(['status' => $newStatus]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $product->fresh()->status,
            ]);
        }

        return back()->with('success', 'Cập nhật trạng thái thành công!');
    }

    public function addImages(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'images.required' => 'Vui lòng chọn ít nhất một ảnh.',
            'images.min' => 'Vui lòng chọn ít nhất một ảnh.',
            'images.*.mimes' => 'Ảnh sản phẩm phải là JPG, PNG hoặc WebP.',
            'images.*.max' => 'Mỗi ảnh sản phẩm tối đa 4MB.',
        ]);

        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        $sortBase = $product->images()->max('sort_order') ?? -1;

        foreach ($request->file('images') as $i => $file) {
            $path = $file->store('images/products', 'public');

            $isPrimary = ! $hasPrimary && $i === 0;

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => $isPrimary,
                'sort_order' => $sortBase + $i + 1,
            ]);

            $hasPrimary = $hasPrimary || $isPrimary;
        }

        return back()->with('success', 'Đã thêm '.count($request->file('images')).' ảnh!');
    }

    public function deleteImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            abort(403);
        }

        $wasPrimary = $image->is_primary;

        $stillUsed = ProductImage::where('image_path', $image->image_path)
            ->where('id', '!=', $image->id)
            ->exists();

        if (! $stillUsed && str_starts_with($image->image_path, 'images/products/')) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        // Nếu ảnh vừa xoá là ảnh chính → tự động chọn ảnh đầu tiên còn lại làm primary
        if ($wasPrimary) {
            $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Đã xoá ảnh!');
    }
}
