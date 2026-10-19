<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\ChatbotService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_demo_orders_and_reviews_for_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(10, Order::count());
        $this->assertTrue(Order::where('status', 'delivered')->exists());
        $this->assertTrue(Order::where('status', 'cancelled')->exists());
        $this->assertTrue(Order::where('created_at', '>=', now()->subDays(6)->startOfDay())->where('status', '!=', 'cancelled')->exists());
        $this->assertTrue(Review::where('status', 'approved')->exists());
        $this->assertTrue(Review::where('status', 'pending')->exists());

        // Danh mục nhiều cấp: có danh mục con và sản phẩm nằm trong danh mục con
        $child = Category::where('slug', 'giay-sneaker')->firstOrFail();
        $this->assertSame('giay-dep', $child->parent->slug);
        $this->assertTrue(Product::where('category_id', $child->id)->exists());
        $this->get(route('categories.show', 'giay-dep'))->assertOk()->assertSee('Nike Air Max 90');

        // Shop chuyên thời trang: không còn sản phẩm điện tử, mọi sản phẩm đều có ảnh
        $this->assertFalse(Category::whereIn('slug', ['dien-thoai', 'laptop'])->exists());
        $this->assertFalse(Product::where('name', 'like', '%iPhone%')->exists());
        $this->assertSame(0, Product::doesntHave('images')->count());

        // TH11: tối thiểu 40 sản phẩm trong >= 4 danh mục
        $this->assertGreaterThanOrEqual(40, Product::where('status', 'active')->count());
        $this->assertSame('ao-nu', Product::where('slug', 'ao-so-mi-nu-tay-bong')->firstOrFail()->category->slug);

        // Chatbot AI được cung cấp danh sách sản phẩm thật của shop để tư vấn
        $context = app(ChatbotService::class)->buildContext('gợi ý quà tặng');
        $this->assertStringContainsString('Đầm Dạ Hội', $context);
        $this->assertStringNotContainsString('iPhone', $context);

        // Có chiến dịch + lượt truy cập để trang phân tích hiển thị số liệu
        $this->assertSame(3, Campaign::count());
        $this->assertTrue(Order::whereNotNull('campaign_id')->exists());
        $this->assertGreaterThan(100, AnalyticsEvent::where('type', 'visit')->count());

        $admin = User::where('email', 'admin@socialshop.vn')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.analytics.index'))->assertOk()->assertSee('Sale Thu 2026 - Facebook Ads');
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reviews.index', ['status' => 'pending']))->assertOk();
    }
}
