<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin-product-test@socialshop.vn',
        ]);

        $this->category = Category::factory()->create();

        $this->actingAs($this->admin);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sản phẩm test',
            'description' => 'Mô tả sản phẩm test',
            'price' => 100000,
            'sale_price' => null,
            'category_id' => $this->category->id,
            'stock' => 10,
            'brand' => 'TestBrand',
            'status' => 'active',
        ], $overrides);
    }

    // ─── Index / Create form ─────────────────────────────────────────────────

    public function test_admin_can_view_products_index(): void
    {
        Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->get(route('admin.products.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_view_create_form_with_cover_image_field(): void
    {
        $response = $this->get(route('admin.products.create'));

        $response->assertStatus(200);
        $response->assertSee('name="image"', false);
    }

    // ─── Store ───────────────────────────────────────────────────────────────

    public function test_admin_can_create_product_without_image(): void
    {
        $response = $this->post(route('admin.products.store'), $this->validPayload());

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $product = Product::where('name', 'Sản phẩm test')->firstOrFail();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'slug' => Str::slug('Sản phẩm test'),
            'status' => 'active',
        ]);

        $this->assertSame(0, $product->images()->count());
    }

    public function test_admin_can_create_product_with_cover_image(): void
    {
        Storage::fake('public');

        $response = $this->post(route('admin.products.store'), $this->validPayload([
            'image' => UploadedFile::fake()->image('cover.jpg'),
        ]));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $product = Product::where('name', 'Sản phẩm test')->firstOrFail();

        $image = $product->images()->firstOrFail();

        $this->assertTrue($image->is_primary);
        $this->assertStringStartsWith('images/products/', $image->image_path);
        Storage::disk('public')->assertExists($image->image_path);
    }

    public function test_admin_product_validation_requires_name_and_existing_category(): void
    {
        $response = $this->post(route('admin.products.store'), $this->validPayload([
            'name' => '',
            'category_id' => 999999,
        ]));

        $response->assertSessionHasErrors(['name', 'category_id']);
        $this->assertDatabaseMissing('products', ['name' => '']);
    }

    public function test_admin_product_cover_image_rejects_oversized_file(): void
    {
        Storage::fake('public');

        $response = $this->post(route('admin.products.store'), $this->validPayload([
            'image' => UploadedFile::fake()->create('huge.jpg', 5000, 'image/jpeg'),
        ]));

        $response->assertSessionHasErrors(['image' => 'Hình ảnh bìa tối đa 4MB.']);
        $this->assertDatabaseMissing('products', ['name' => 'Sản phẩm test']);
    }

    public function test_admin_product_cover_image_rejects_wrong_format_with_vietnamese_message(): void
    {
        Storage::fake('public');

        $response = $this->post(route('admin.products.store'), $this->validPayload([
            'image' => UploadedFile::fake()->create('animation.gif', 100, 'image/gif'),
        ]));

        $response->assertSessionHasErrors(['image' => 'Hình ảnh bìa phải là JPG, PNG hoặc WebP.']);
        $this->assertDatabaseMissing('products', ['name' => 'Sản phẩm test']);
    }

    public function test_admin_product_cover_image_accepts_large_jpg_up_to_4mb(): void
    {
        Storage::fake('public');

        $response = $this->post(route('admin.products.store'), $this->validPayload([
            'image' => UploadedFile::fake()->create('big.jpg', 3000, 'image/jpeg'),
        ]));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $product = Product::where('name', 'Sản phẩm test')->firstOrFail();
        Storage::disk('public')->assertExists($product->images()->firstOrFail()->image_path);
    }

    // ─── ProductImage URL ────────────────────────────────────────────────────

    public function test_uploaded_image_url_uses_storage_prefix(): void
    {
        $image = ProductImage::factory()->primary()->create([
            'image_path' => 'images/products/abc123.jpg',
        ]);

        $this->assertSame(asset('storage/images/products/abc123.jpg'), Str::before($image->url, '?v='));
        $this->assertMatchesRegularExpression('/\?v=\d+$/', $image->url);
    }

    public function test_seeded_image_url_stays_relative_to_public_root(): void
    {
        $image = ProductImage::factory()->primary()->create([
            'image_path' => 'product-images/macbook-air-m3.jpg',
        ]);

        $this->assertSame(asset('product-images/macbook-air-m3.jpg'), Str::before($image->url, '?v='));
        $this->assertMatchesRegularExpression('/\?v=\d+$/', $image->url);
    }

    public function test_remote_image_url_is_returned_unchanged(): void
    {
        $image = ProductImage::factory()->primary()->create([
            'image_path' => 'https://picsum.photos/seed/abc/400/400',
        ]);

        $this->assertSame('https://picsum.photos/seed/abc/400/400', $image->url);
    }

    // ─── Update ──────────────────────────────────────────────────────────────

    public function test_admin_can_update_product_and_replace_cover_image(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create(['category_id' => $this->category->id]);
        Storage::disk('public')->put('images/products/old.jpg', 'old');

        $oldImage = ProductImage::factory()->primary()->create([
            'product_id' => $product->id,
            'image_path' => 'images/products/old.jpg',
        ]);

        $response = $this->put(route('admin.products.update', $product), $this->validPayload([
            'name' => 'Tên sản phẩm mới',
            'image' => UploadedFile::fake()->image('new.jpg'),
        ]));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Tên sản phẩm mới',
        ]);

        $this->assertDatabaseMissing('product_images', ['id' => $oldImage->id]);
        Storage::disk('public')->assertMissing('images/products/old.jpg');

        $newImage = $product->images()->firstOrFail();
        $this->assertTrue($newImage->is_primary);
        Storage::disk('public')->assertExists($newImage->image_path);
    }

    // ─── Gallery (images[]) ──────────────────────────────────────────────────

    public function test_admin_can_add_multiple_images_to_product(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->post(route('admin.products.images.add', $product), [
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.png'),
            ],
        ]);

        $response->assertSessionHas('success');

        $this->assertSame(2, $product->images()->count());
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
        $this->assertTrue($product->images()->orderBy('sort_order')->first()->is_primary);

        Storage::disk('public')->assertExists($product->images()->first()->image_path);
    }

    public function test_admin_can_delete_product_image_and_file(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create(['category_id' => $this->category->id]);
        Storage::disk('public')->put('images/products/to-delete.jpg', 'x');

        $image = ProductImage::factory()->primary()->create([
            'product_id' => $product->id,
            'image_path' => 'images/products/to-delete.jpg',
        ]);

        $response = $this->delete(route('admin.products.images.delete', [$product, $image]));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing('images/products/to-delete.jpg');
    }

    public function test_admin_cannot_delete_image_of_another_product(): void
    {
        $productA = Product::factory()->create(['category_id' => $this->category->id]);
        $productB = Product::factory()->create(['category_id' => $this->category->id]);

        $image = ProductImage::factory()->primary()->create(['product_id' => $productA->id]);

        $response = $this->delete(route('admin.products.images.delete', [$productB, $image]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('product_images', ['id' => $image->id]);
    }

    // ─── Toggle / Destroy ────────────────────────────────────────────────────

    public function test_admin_can_toggle_product_status(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'status' => 'active',
        ]);

        $response = $this->post(route('admin.products.toggle', $product));

        $response->assertSessionHas('success');
        $this->assertSame('inactive', $product->fresh()->status);

        $this->post(route('admin.products.toggle', $product));
        $this->assertSame('active', $product->fresh()->status);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    // ─── Authorization ───────────────────────────────────────────────────────

    public function test_customer_cannot_access_admin_products(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get(route('admin.products.index'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_admin_products(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $response = $this->get(route('admin.products.index'));

        $response->assertRedirect('/login');
    }
}
