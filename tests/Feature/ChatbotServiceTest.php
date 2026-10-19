<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChatbotFaq;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatbotService $chatbotService;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.gemini.key', 'test_api_key');
        Config::set('services.gemini.model', 'gemini-3.5-flash');
        $this->chatbotService = new ChatbotService;
    }

    public function test_getters()
    {
        $this->assertEquals('test_api_key', $this->chatbotService->getApiKey());
        $this->assertEquals('gemini-3.5-flash', $this->chatbotService->getModel());
        $this->assertStringContainsString('Bạn là trợ lý ảo của SocialShop', $this->chatbotService->getSystemPrompt());
    }

    public function test_send_message_uses_fallback_if_no_api_key()
    {
        Config::set('services.gemini.key', '');
        $service = new ChatbotService;
        $response = $service->sendMessage('xin chào');
        $this->assertStringContainsString('Xin chào! Rất vui được hỗ trợ bạn', $response);
    }

    public function test_send_message_uses_fallback_if_test_api_key()
    {
        Config::set('services.gemini.key', 'test');
        $service = new ChatbotService;
        $response = $service->sendMessage('xin chào');
        $this->assertStringContainsString('Xin chào! Rất vui được hỗ trợ bạn', $response);
    }

    public function test_send_message_api_success()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Hello from Gemini!'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->chatbotService->sendMessage('hi', [
            ['type' => 'user', 'content' => 'hello previously'],
            ['type' => 'model', 'content' => 'hi back'],
        ]);

        $this->assertEquals('Hello from Gemini!', $response);
    }

    public function test_send_message_api_rate_limit()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 429),
        ]);

        $response = $this->chatbotService->sendMessage('xin chào');
        $this->assertStringContainsString('Xin chào! Rất vui được hỗ trợ bạn', $response);
    }

    public function test_send_message_api_error()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500),
        ]);

        $response = $this->chatbotService->sendMessage('xin chào');
        $this->assertStringContainsString('Xin chào! Rất vui được hỗ trợ bạn', $response);
    }

    public function test_send_message_api_exception()
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $response = $this->chatbotService->sendMessage('xin chào');
        $this->assertStringContainsString('Xin chào! Rất vui được hỗ trợ bạn', $response);
    }

    public function test_build_context_with_faqs_products_orders()
    {
        $category = Category::factory()->create(['name' => 'Shirts']);
        Product::factory()->create(['name' => 'Áo thun', 'price' => 200000, 'category_id' => $category->id, 'status' => 'active', 'stock' => 10]);

        ChatbotFaq::create(['question' => 'Q1', 'answer' => 'A1', 'keywords' => 'áo thun', 'sort_order' => 1, 'is_active' => true]);

        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'order_code' => 'ORD-12345678-ABC', 'status' => 'shipping', 'total' => 200000]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => 1,
            'product_name' => 'Áo thun',
            'quantity' => 1,
            'price' => 200000,
            'subtotal' => 200000,
        ]);

        $context = $this->chatbotService->buildContext('tôi mua áo thun mã đơn ORD-12345678-ABC', $user->id);

        $this->assertStringContainsString('Áo thun', $context);
        $this->assertStringContainsString('Q1', $context);
        $this->assertStringContainsString('Đang giao hàng', $context);
    }

    public function test_search_products_empty_keyword_returns_empty_array()
    {
        $products = $this->chatbotService->searchProducts('không có từ khóa');
        $this->assertEmpty($products);
    }

    public function test_search_products_general_keyword_returns_latest()
    {
        $category = Category::factory()->create(['name' => 'Category 1']);
        Product::factory()->create(['name' => 'Test', 'price' => 100000, 'category_id' => $category->id, 'status' => 'active', 'stock' => 10]);

        $products = $this->chatbotService->searchProducts('sản phẩm');
        $this->assertCount(1, $products);
        $this->assertEquals('Test', $products[0]['name']);
    }

    public function test_search_products_specific_keyword()
    {
        $category = Category::factory()->create(['name' => 'Category 1']);
        Product::factory()->create(['name' => 'Đôi giày thể thao', 'price' => 100000, 'category_id' => $category->id, 'status' => 'active', 'stock' => 0]);

        $products = $this->chatbotService->searchProducts('giày');
        $this->assertCount(1, $products);
        $this->assertEquals('Đôi giày thể thao', $products[0]['name']);
        $this->assertFalse($products[0]['in_stock']);
    }

    public function test_search_faqs()
    {
        ChatbotFaq::create(['question' => 'What?', 'answer' => 'This', 'keywords' => 'giao hàng, ship', 'sort_order' => 1, 'is_active' => true]);
        ChatbotFaq::create(['question' => 'Who?', 'answer' => 'Me', 'keywords' => 'giá, price', 'sort_order' => 2, 'is_active' => true]);

        $faqs = $this->chatbotService->searchFaqs('hỏi về ship');
        $this->assertCount(1, $faqs);
        $this->assertEquals('What?', $faqs[0]['question']);
    }

    public function test_get_user_order_info_no_keywords()
    {
        $info = $this->chatbotService->getUserOrderInfo(1, 'không có gì');
        $this->assertEquals('', $info);
    }

    public function test_get_user_order_info_not_found()
    {
        $user = User::factory()->create();
        $info = $this->chatbotService->getUserOrderInfo($user->id, 'mã đơn ORD-12345678-XXX');
        $this->assertEquals('Không tìm thấy đơn hàng nào.', $info);
    }

    public function test_fallback_response()
    {
        $category = Category::factory()->create(['name' => 'Shirts']);
        Product::factory()->create(['name' => 'Áo sơ mi', 'price' => 200000, 'category_id' => $category->id, 'status' => 'active', 'stock' => 10]);

        $response = $this->chatbotService->fallbackResponse('Áo sơ mi');
        $this->assertStringContainsString('Áo sơ mi', $response);

        ChatbotFaq::create(['question' => 'faq 1', 'answer' => 'faq answer 1', 'keywords' => 'giờ mở cửa', 'sort_order' => 1, 'is_active' => true]);
        $response = $this->chatbotService->fallbackResponse('giờ mở cửa');
        $this->assertStringContainsString('faq answer 1', $response);

        // Clear products to avoid searchProducts returning top 5 products when query becomes empty
        Product::query()->delete();

        $response = $this->chatbotService->fallbackResponse('giá bao nhiêu');
        $this->assertStringContainsString('xem giá chi tiết', $response);

        $response = $this->chatbotService->fallbackResponse('giao hàng toàn quốc');
        $this->assertStringContainsString('giao hàng toàn quốc', $response);

        $response = $this->chatbotService->fallbackResponse('thanh toán cod');
        $this->assertStringContainsString('hỗ trợ thanh toán COD', $response);

        $response = $this->chatbotService->fallbackResponse('liên hệ hotline');
        $this->assertStringContainsString('hotline', $response);

        $response = $this->chatbotService->fallbackResponse('random question');
        $this->assertStringContainsString('Cảm ơn bạn đã nhắn tin', $response);
    }
}
