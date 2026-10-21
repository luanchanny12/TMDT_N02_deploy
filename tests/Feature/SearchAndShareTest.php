<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAndShareTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'name' => $name,
            'description' => $name,
            'status' => 'active',
            'category_id' => Category::factory()->create()->id,
        ], $attrs));
    }

    public function test_no_result_search_shows_partially_matching_suggestions(): void
    {
        $this->product('Áo Polo Nam');
        $this->product('Đầm Dạ Hội');

        // Không sản phẩm nào chứa cả cụm "polo màu tím" nhưng có từ "polo"
        $this->get(route('products.index', ['search' => 'polo màu tím']))
            ->assertOk()
            ->assertSee('Không tìm thấy sản phẩm phù hợp')
            ->assertSee('Có thể bạn sẽ thích')
            ->assertSeeInOrder(['Có thể bạn sẽ thích', 'Áo Polo Nam']);
    }

    public function test_gibberish_search_falls_back_to_popular_products(): void
    {
        $this->product('Giày Sneaker Trắng', ['views_count' => 500]);
        $this->product('Hidden', ['status' => 'inactive', 'views_count' => 9999]);

        $this->get(route('products.index', ['search' => 'zzqxw']))
            ->assertOk()
            ->assertSee('Có thể bạn sẽ thích')
            ->assertSee('Giày Sneaker Trắng')
            ->assertDontSee('Hidden');
    }

    public function test_suggestions_hidden_when_search_has_results(): void
    {
        $this->product('Áo Polo Nam');

        $this->get(route('products.index', ['search' => 'Polo']))
            ->assertOk()
            ->assertDontSee('Có thể bạn sẽ thích');
    }

    public function test_search_box_has_keyboard_shortcut(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="site-search"', false)
            ->assertSee('aria-keyshortcuts="/ Control+K"', false);
    }

    public function test_product_page_offers_more_share_targets(): void
    {
        $product = $this->product('Túi Tote');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Chia sẻ...')
            ->assertSee("shareTo('x')", false)
            ->assertSee("shareTo('telegram')", false)
            ->assertSee('Sao chép link');
    }

    public function test_share_link_contains_referral_code_for_logged_in_user(): void
    {
        $user = User::factory()->create(['referral_code' => 'REFQUAN1']);
        $product = $this->product('Túi Tote');

        $this->actingAs($user)->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('?ref=REFQUAN1', false);
    }

    public function test_guest_visiting_referral_link_sees_welcome_banner(): void
    {
        $referrer = User::factory()->create(['name' => 'Nguyễn Văn Quân', 'referral_code' => 'REFQUAN99']);
        $product = $this->product('Túi Tote');

        $this->get(route('products.show', ['product' => $product->slug, 'ref' => 'REFQUAN99']))
            ->assertOk()
            ->assertSee('Bạn được bạn')
            ->assertSee('Nguyễn Văn Quân')
            ->assertSee('Đăng ký nhận quà');
    }

    public function test_register_page_prefills_referral_code_and_shows_referrer_info(): void
    {
        $referrer = User::factory()->create(['name' => 'Trần Thị Mai', 'referral_code' => 'REFMAI88']);

        $this->get(route('register', ['ref' => 'REFMAI88']))
            ->assertOk()
            ->assertSee('value="REFMAI88"', false)
            ->assertSee('Được giới thiệu bởi bạn')
            ->assertSee('Trần Thị Mai');
    }
}
