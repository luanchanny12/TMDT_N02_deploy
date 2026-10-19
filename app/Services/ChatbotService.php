<?php

namespace App\Services;

use App\Models\ChatbotFaq;
use App\Models\Order;
use App\Models\Product;
use App\Services\Order\PricingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    private string $apiKey;

    private string $model;

    private string $systemPrompt;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        $this->model = config('services.gemini.model', 'gemini-3.5-flash');
        $this->systemPrompt = <<<'PROMPT'
Bạn là trợ lý ảo của SocialShop — cửa hàng trực tuyến chuyên thời trang và phụ kiện: quần áo nam nữ, giày dép, túi xách, đồng hồ, mỹ phẩm & nước hoa.
Hãy trả lời ngắn gọn, thân thiện bằng tiếng Việt.
Khi khách cần tư vấn hoặc gợi ý, chỉ giới thiệu sản phẩm có trong danh sách sản phẩm của shop (kèm giá) và mời khách xem chi tiết trên website.
Nếu khách hỏi mặt hàng shop không kinh doanh (ví dụ điện thoại, laptop), hãy nói rõ shop chưa bán và gợi ý sản phẩm thời trang phù hợp.
Nếu hỏi về giao hàng, giao toàn quốc, phí ship theo khu vực, miễn phí ship cho đơn từ :free_ship.
Nếu hỏi về thanh toán, hỗ trợ COD và VNPay.
Nếu hỏi về liên hệ, hotline 077123456, email support@socialshop.vn.
Không trả lời các câu hỏi ngoài phạm vi cửa hàng.
PROMPT;
        $this->systemPrompt = str_replace(':free_ship', $this->freeShipText(), $this->systemPrompt);
    }

    private function freeShipText(): string
    {
        try {
            return number_format(app(PricingService::class)->nationwideFreeShippingThreshold(), 0, ',', '.').'đ';
        } catch (\Throwable) {
            return '300.000đ';
        }
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getSystemPrompt(): string
    {
        return $this->systemPrompt;
    }

    public function sendMessage(string $userMessage, array $history = [], ?int $userId = null): string
    {
        if (empty($this->apiKey) || $this->apiKey === 'test') {
            return $this->fallbackResponse($userMessage, $userId);
        }

        try {
            $contextParts = $this->buildContext($userMessage, $userId);

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

            $contents = [];

            foreach ($history as $msg) {
                $contents[] = [
                    'role' => $msg['type'] === 'user' ? 'user' : 'model',
                    'parts' => [['text' => $msg['content']]],
                ];
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userMessage]],
            ];

            $payload = [
                'systemInstruction' => [
                    'parts' => [['text' => $this->systemPrompt.$contextParts]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 256,
                ],
            ];

            $response = Http::timeout(15)
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->post($url, $payload);

            if ($response->status() === 429) {
                Log::warning('Gemini rate limited', ['status' => 429]);

                return $this->fallbackResponse($userMessage, $userId);
            }

            if ($response->successful()) {
                $data = $response->json();

                return $data['candidates'][0]['content']['parts'][0]['text']
                    ?? 'Xin lỗi, tôi chưa hiểu yêu cầu của bạn.';
            }

            Log::warning('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);

            return $this->fallbackResponse($userMessage, $userId);
        } catch (\Exception $e) {
            Log::error('Gemini API exception', ['message' => $e->getMessage()]);

            return $this->fallbackResponse($userMessage, $userId);
        }
    }

    public function buildContext(string $userMessage, ?int $userId = null): string
    {
        $context = $this->catalogOverview();

        $faqs = $this->searchFaqs($userMessage);
        if (! empty($faqs)) {
            $context .= "\n\n[Hãy ưu tiên trả lời dựa trên thông tin FAQ sau]";
            foreach ($faqs as $faq) {
                $context .= "\n- Q: {$faq['question']}\n  A: {$faq['answer']}";
            }
        }

        $products = $this->searchProducts($userMessage);
        if (! empty($products)) {
            $context .= "\n\n[Thông tin sản phẩm tìm thấy]";
            foreach ($products as $p) {
                $stock = $p['in_stock'] ? 'còn hàng' : 'hết hàng';
                $context .= "\n- {$p['name']} | Giá: {$p['price']} | Danh mục: {$p['category']} | {$stock}";
            }
        }

        if ($userId) {
            $orderInfo = $this->getUserOrderInfo($userId, $userMessage);
            if (! empty($orderInfo)) {
                $context .= "\n\n[Thông tin đơn hàng của khách]";
                $context .= "\n{$orderInfo}";
            }
        }

        return $context;
    }

    /**
     * Danh sách sản phẩm đang bán (rút gọn) để AI tư vấn đúng hàng của shop.
     */
    public function catalogOverview(int $limit = 60): string
    {
        $products = Product::where('status', 'active')
            ->with('category:id,name')
            ->orderBy('category_id')
            ->limit($limit)
            ->get(['id', 'name', 'price', 'sale_price', 'sale_ends_at', 'stock', 'category_id']);

        if ($products->isEmpty()) {
            return '';
        }

        $lines = $products->map(fn ($p) => '- '.$p->name.' | '.number_format($p->effectivePrice()).'đ | '
            .($p->category->name ?? 'Khác').($p->stock > 0 ? '' : ' | hết hàng'));

        return "\n\n[Danh sách sản phẩm của shop]\n".$lines->implode("\n");
    }

    public function searchProducts(string $query): array
    {
        $productKeywords = ['giá', 'price', 'bao nhiêu', 'còn hàng', 'hết hàng', 'có không', 'sản phẩm', 'áo', 'quần', 'giày', 'dép', 'váy', 'túi', 'phụ kiện', 'thời trang', 'size', 'màu'];

        $hasProductKeyword = false;
        foreach ($productKeywords as $kw) {
            if (mb_stripos($query, $kw) !== false) {
                $hasProductKeyword = true;
                break;
            }
        }

        if (! $hasProductKeyword) {
            return [];
        }

        $cleanQuery = mb_strtolower($query);
        $cleanQuery = str_replace(['giá', 'price', 'bao nhiêu', 'tiền', 'còn hàng', 'hết hàng', 'có không', 'không', 'sản phẩm', 'áo', 'quần', 'giày', 'dép', 'váy', 'túi', 'phụ kiện', 'thời trang'], '', $cleanQuery);
        $cleanQuery = preg_replace('/\s+/', ' ', trim($cleanQuery));

        if (empty($cleanQuery)) {
            return Product::where('status', 'active')
                ->with('category')
                ->limit(5)
                ->get()
                ->map(fn ($p) => [
                    'name' => $p->name,
                    'price' => number_format($p->effectivePrice()).'đ',
                    'category' => $p->category->name ?? 'N/A',
                    'in_stock' => $p->stock > 0,
                ])
                ->toArray();
        }

        return Product::where('status', 'active')
            ->where(function ($q) use ($cleanQuery) {
                $q->where('name', 'LIKE', "%{$cleanQuery}%")
                    ->orWhere('brand', 'LIKE', "%{$cleanQuery}%")
                    ->orWhere('description', 'LIKE', "%{$cleanQuery}%");
            })
            ->with('category')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'price' => number_format($p->effectivePrice()).'đ',
                'category' => $p->category->name ?? 'N/A',
                'in_stock' => $p->stock > 0,
            ])
            ->toArray();
    }

    public function searchFaqs(string $query): array
    {
        $activeFaqs = ChatbotFaq::active()->orderBy('sort_order')->get();

        $scored = [];

        foreach ($activeFaqs as $faq) {
            $queryLower = mb_strtolower($query);
            $score = 0;

            if ($faq->keywords) {
                $keywords = array_map('trim', explode(',', $faq->keywords));
                foreach ($keywords as $kw) {
                    if (mb_stripos($queryLower, mb_strtolower($kw)) !== false) {
                        $score = 10;
                        break;
                    }
                }
            }

            if ($score > 0) {
                $scored[] = ['faq' => $faq, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_values(array_slice(array_map(fn ($s) => [
            'question' => $s['faq']->question,
            'answer' => $s['faq']->answer,
        ], $scored), 0, 3));
    }

    public function getUserOrderInfo(int $userId, string $query): string
    {
        $orderKeywords = ['đơn hàng', 'đơn', 'order', 'tình trạng', 'đã giao', 'chưa giao', 'giao đến đâu', 'tracking', 'mã đơn'];
        $hasOrderKeyword = false;

        foreach ($orderKeywords as $kw) {
            if (mb_stripos($query, $kw) !== false) {
                $hasOrderKeyword = true;
                break;
            }
        }

        if (! $hasOrderKeyword) {
            return '';
        }

        $orderCode = null;
        if (preg_match('/(ORD-\d{8}-\w{3,4})/i', $query, $m)) {
            $orderCode = $m[1];
        }

        $order = Order::where('user_id', $userId)
            ->when($orderCode, fn ($q) => $q->where('order_code', $orderCode))
            ->with('items')
            ->latest()
            ->first();

        if (! $order) {
            return 'Không tìm thấy đơn hàng nào.';
        }

        $statusMap = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'shipping' => 'Đang giao hàng',
            'delivered' => 'Đã giao hàng',
            'cancelled' => 'Đã hủy',
        ];

        $items = $order->items->map(fn ($i) => "{$i->product_name} x{$i->quantity}")->implode(', ');

        return "Mã đơn: {$order->order_code} | Trạng thái: ".($statusMap[$order->status] ?? $order->status)
            .' | Tổng: '.number_format($order->total).'đ'
            ." | Sản phẩm: {$items}";
    }

    public function fallbackResponse(string $message, ?int $userId = null): string
    {
        $lower = mb_strtolower($message);

        if ($userId) {
            $orderInfo = $this->getUserOrderInfo($userId, $message);
            if (! empty($orderInfo)) {
                return $orderInfo;
            }
        }

        $products = $this->searchProducts($message);
        if (! empty($products)) {
            $lines = [];
            foreach ($products as $p) {
                $stock = $p['in_stock'] ? 'còn hàng' : 'hết hàng';
                $lines[] = "- {$p['name']}: {$p['price']} ({$stock})";
            }

            return "Các sản phẩm tìm thấy:\n".implode("\n", $lines)
                ."\nBạn có thể xem chi tiết tại trang sản phẩm.";
        }

        $faqs = $this->searchFaqs($message);
        if (! empty($faqs)) {
            return $faqs[0]['answer'];
        }

        $priceWords = ['giá', 'giá cả', 'bao nhiêu', 'price', 'cost', 'tiền'];
        foreach ($priceWords as $pw) {
            if (str_contains($lower, $pw)) {
                return 'Bạn có thể xem giá chi tiết từng sản phẩm tại trang Sản phẩm. Shop có nhiều sản phẩm thời trang từ 150.000đ trở lên!';
            }
        }

        return match (true) {
            str_contains($lower, 'xin chào') || str_contains($lower, 'hello') || str_contains($lower, 'hi') => 'Xin chào! Rất vui được hỗ trợ bạn. Bạn cần tìm sản phẩm nào?',
            str_contains($lower, 'giá') || str_contains($lower, 'price') => 'Bạn có thể xem giá sản phẩm trên trang chi tiết sản phẩm. Chúng tôi có nhiều ưu đãi hấp dẫn!',
            str_contains($lower, 'giao hàng') || str_contains($lower, 'ship') => 'Chúng tôi giao hàng toàn quốc. Miễn phí ship cho đơn từ '.$this->freeShipText().'!',
            str_contains($lower, 'thanh toán') || str_contains($lower, 'payment') => 'Chúng tôi hỗ trợ thanh toán COD và VNPay. Bạn chọn phương thức nào phù hợp nhất!',
            str_contains($lower, 'liên hệ') => 'Bạn có thể liên hệ hotline 077123456 hoặc email support@socialshop.vn.',
            default => 'Cảm ơn bạn đã nhắn tin! Hiện tại tôi là trợ lý demo. Vui lòng xem thêm thông tin trên trang web hoặc liên hệ hỗ trợ.',
        };
    }
}
