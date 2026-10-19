<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_is_accessible(): void
    {
        $this->get('/dieu-khoan-giao-dich')
            ->assertOk()
            ->assertSee('Điều Kiện Giao Dịch')
            ->assertSee('Quy trình giao kết')
            ->assertSee('Phương thức thanh toán');
    }

    public function test_about_page_is_accessible(): void
    {
        $this->get('/gioi-thieu')
            ->assertOk()
            ->assertSee('Thông Tin Người Bán')
            ->assertSee('SocialShop')
            ->assertSee('Phạm vi kinh doanh');
    }

    public function test_privacy_page_contains_required_items(): void
    {
        $this->get('/chinh-sach-bao-mat')
            ->assertOk()
            ->assertSee('91/2025/QH15') // Luật BVDLCN thay căn cứ NĐ 13/2023
            ->assertSee('Quyền của chủ thể')
            ->assertSee('Biện pháp bảo mật')
            ->assertSee('Đầu mối khiếu nại');
    }

    public function test_footer_contains_legal_links(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('pages.terms'), false)
            ->assertSee(route('pages.about'), false)
            ->assertSee(route('pages.return-policy'), false)
            ->assertSee(route('pages.privacy'), false);
    }

    public function test_checkout_review_links_to_terms(): void
    {
        $user = User::factory()->create();
        $cat = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $cat->id,
            'status' => 'active',
            'price' => 100000,
            'stock' => 10,
        ]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100000,
        ]);

        $this->actingAs($user)
            ->withSession(['checkout' => [
                'shipping_name' => 'Test',
                'shipping_phone' => '0900000000',
                'shipping_address' => '123 Test', 'shipping_province' => 'Quảng Ninh',
                'payment_method' => 'cod',
            ]])
            ->get('/checkout/review')
            ->assertOk()
            ->assertSee(route('pages.terms'), false);
    }

    public function test_buying_guide_page_is_accessible(): void
    {
        $this->get('/huong-dan-mua-hang')->assertOk();
    }

    public function test_return_policy_page_is_accessible(): void
    {
        $this->get('/chinh-sach-doi-tra')->assertOk();
    }

    public function test_faq_page_is_accessible(): void
    {
        $this->get('/faq')->assertOk();
    }
}
