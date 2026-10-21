<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'customer',
        ]);
    }

    public function test_wishlist_page_requires_login(): void
    {
        $this->get('/wishlist')
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_wishlist(): void
    {
        $this->actingAs($this->user)
            ->get('/wishlist')
            ->assertOk();
    }

    public function test_user_can_add_product_to_wishlist(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user)
            ->postJson("/wishlist/{$product->id}/toggle")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_wishlisted' => true,
            ]);

        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
        ]);
    }

    public function test_user_can_remove_product_from_wishlist(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $wishlist = Wishlist::create(['user_id' => $this->user->id]);
        WishlistItem::create([
            'wishlist_id' => $wishlist->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($this->user)
            ->postJson("/wishlist/{$product->id}/toggle")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_wishlisted' => false,
            ]);

        $this->assertDatabaseMissing('wishlist_items', [
            'product_id' => $product->id,
        ]);
    }

    public function test_guest_can_view_public_shared_wishlist_and_receives_referral_cookie(): void
    {
        $owner = User::factory()->create([
            'name' => 'Nguyễn Minh Anh',
            'referral_code' => 'REFANH123',
        ]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Đầm Dạ Hội Shared',
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $wishlist = Wishlist::create(['user_id' => $owner->id]);
        WishlistItem::create([
            'wishlist_id' => $wishlist->id,
            'product_id' => $product->id,
        ]);

        $response = $this->get(route('wishlist.shared', ['code' => 'REFANH123']));

        $response->assertOk()
            ->assertSee('Nguyễn Minh Anh')
            ->assertSee('Đầm Dạ Hội Shared')
            ->assertSee('Bộ sưu tập thời trang yêu thích')
            ->assertCookie('referral_code', 'REFANH123');
    }

    public function test_user_can_save_all_shared_items_to_own_wishlist(): void
    {
        $owner = User::factory()->create(['referral_code' => 'REFANH456']);
        $category = Category::factory()->create();
        $p1 = Product::factory()->create(['category_id' => $category->id, 'status' => 'active']);
        $p2 = Product::factory()->create(['category_id' => $category->id, 'status' => 'active']);

        $ownerWishlist = Wishlist::create(['user_id' => $owner->id]);
        WishlistItem::create(['wishlist_id' => $ownerWishlist->id, 'product_id' => $p1->id]);
        WishlistItem::create(['wishlist_id' => $ownerWishlist->id, 'product_id' => $p2->id]);

        $viewer = User::factory()->create();

        $response = $this->actingAs($viewer)
            ->post(route('wishlist.shared.save-all', ['code' => 'REFANH456']));

        $response->assertRedirect();

        $viewerWishlist = $viewer->wishlist()->first();
        $this->assertNotNull($viewerWishlist);
        $this->assertDatabaseHas('wishlist_items', ['wishlist_id' => $viewerWishlist->id, 'product_id' => $p1->id]);
        $this->assertDatabaseHas('wishlist_items', ['wishlist_id' => $viewerWishlist->id, 'product_id' => $p2->id]);
    }

    public function test_shared_wishlist_returns_404_for_invalid_code(): void
    {
        $this->get(route('wishlist.shared', ['code' => 'INVALID_CODE_999']))
            ->assertNotFound();
    }

    public function test_shared_wishlist_renders_when_owner_wishlist_is_empty(): void
    {
        User::factory()->create(['referral_code' => 'REFEMPTY01']);

        $this->get(route('wishlist.shared', ['code' => 'REFEMPTY01']))
            ->assertOk()
            ->assertSee('Bộ sưu tập hiện đang trống');
    }
}
