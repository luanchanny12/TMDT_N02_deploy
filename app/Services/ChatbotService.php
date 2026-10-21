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
Bạn là trợ lý ảo của SocialShop kiêm Stylist thời trang thông minh — cửa hàng thời trang và phụ kiện cao cấp: quần áo nam nữ, giày dép, túi xách, đồng hồ, mỹ phẩm.
Hãy trả lời ngắn gọn, nhiệt tình, chuyên nghiệp bằng tiếng Việt.

TƯ VẤN SẢN PHẨM & LIÊN KẾT:
- Khi giới thiệu hoặc gợi ý sản phẩm, hãy chọn các sản phẩm có trong danh sách của shop (kèm giá) và tạo link xem sản phẩm dạng Markdown theo mẫu: [Tên sản phẩm](/products/slug).

TƯ VẤN KÍCH CỠ NHÂN TRẮC HỌC (SIZE ADVISOR):
- Khi khách hỏi chọn size hoặc cung cấp chiều cao, cân nặng:
  + Dưới 1m60 hoặc dưới 50kg: Size S
  + 1m60 - 1m68, 50 - 62kg: Size M
  + 1m68 - 1m75, 62 - 72kg: Size L
  + 1m75 - 1m82, 72 - 82kg: Size XL
  + Trên 1m82 hoặc trên 82kg: Size XXL
- Đưa ra lời khuyên thực tế: Khuyên chọn đúng size nếu thích mặc vừa vặn, tôn dáng; khuyên tăng 1 size nếu thích phong cách rộng rãi thoải mái (Oversize) hoặc có bờ vai/bụng lớn.
- Nếu khách chỉ đưa chiều cao hoặc chỉ đưa cân nặng, hãy lịch sự hỏi thêm số đo còn lại để tư vấn chuẩn xác nhất.

TƯ VẤN PHONG CÁCH & PHỐI ĐỒ (MIX & MATCH STYLIST):
- Khi khách hỏi trang phục theo sự kiện (đi tiệc, đám cưới, công sở/đi làm, hẹn hò, dạo phố, du lịch):
  + Chủ động gợi ý một set đồ (outfit) hoàn chỉnh gồm 2-3 món từ danh mục của shop (ví dụ: Đầm dạ hội + Túi xách; hoặc Áo polo/sơ mi + Quần + Giày sneaker).
  + Nêu rõ tên sản phẩm, giá bán và link Markdown [Tên sản phẩm](/products/slug).
  + Giải thích ngắn gọn lý do phối đồ phù hợp với ngữ cảnh đó (tôn dáng, lịch thiệp, sang trọng).

CHÍNH SÁCH BÁN HÀNG & PHÁP LÝ:
- Giao hàng toàn quốc, miễn phí ship cho đơn từ :free_ship.
- Thanh toán hỗ trợ COD và VNPay.
- Chính sách đổi trả trong vòng 7 ngày kể từ khi nhận hàng (sản phẩm nguyên tem mác).
- Với câu hỏi về đổi trả, hoàn tiền, bảo hành hoặc khiếu nại: không tự đặt ra điều kiện ngoài chính sách; dẫn link trang Chính sách đổi trả và hotline 077123456.
- Nếu khách hỏi mặt hàng shop không kinh doanh (ví dụ điện thoại, laptop), hãy nói rõ shop chưa bán và gợi ý sản phẩm thời trang phù hợp.
- Không trả lời các câu hỏi ngoài phạm vi cửa hàng.
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

    /**
     * Guardrail: câu hỏi về đổi trả / hoàn tiền / bảo hành / khiếu nại được trả lời bằng nội dung chính sách cố định,
     * không qua AI, để trợ lý không "hứa" sai điều kiện so với chính sách đã công bố.
     */
    public function policyAnswer(string $message): ?string
    {
        $lower = mb_strtolower($message);
        $has = fn (array $words) => collect($words)->contains(fn ($w) => str_contains($lower, $w));

        $topic = match (true) {
            $has(['khiếu nại', 'khieu nai', 'phàn nàn', 'tố cáo']) => 'complaint',
            $has(['hoàn tiền', 'hoàn lại tiền', 'hoan tien', 'refund', 'trả tiền lại']) => 'refund',
            $has(['bảo hành', 'bao hanh', 'warranty']) => 'warranty',
            $has(['đổi trả', 'doi tra', 'trả hàng', 'tra hang', 'trả lại', 'đổi hàng', 'doi hang', 'đổi size', 'đổi mẫu', 'đổi màu']) => 'return',
            default => null,
        };

        if ($topic === null) {
            return null;
        }

        $link = route('pages.return-policy');
        $contact = 'Hotline 077123456 hoặc email support@socialshop.vn (kèm mã đơn hàng và hình ảnh sản phẩm).';

        $body = match ($topic) {
            'return' => 'Chính sách đổi trả của SocialShop:
'
                .'- Đổi/trả trong vòng 7 ngày kể từ ngày nhận hàng.
'
                .'- Hàng lỗi nhà sản xuất, giao sai size/màu, hư hỏng khi vận chuyển hoặc không đúng mô tả: miễn phí vận chuyển đổi trả.
'
                .'- Đổi size, đổi mẫu hoặc trả do đổi ý: sản phẩm còn nguyên tem, nhãn, chưa sử dụng; khách chịu phí gửi lại.',
            'refund' => 'Hoàn tiền tại SocialShop:
'
                .'- Sau khi shop nhận và kiểm tra hàng trả, tiền được hoàn trong 3–5 ngày làm việc.
'
                .'- Đơn VNPay hoàn về tài khoản ngân hàng; đơn COD shop chuyển khoản tới tài khoản bạn cung cấp.',
            'warranty' => 'Về bảo hành:
'
                .'- Sản phẩm lỗi do nhà sản xuất được đổi/trả miễn phí trong 7 ngày kể từ ngày nhận hàng theo chính sách đổi trả.
'
                .'- Ngoài thời hạn này, vui lòng liên hệ CSKH để được hướng dẫn cụ thể cho từng sản phẩm.',
            'complaint' => 'Rất tiếc vì trải nghiệm chưa tốt của bạn. Để gửi khiếu nại:
'
                .'- CSKH phản hồi trong vòng 24 giờ làm việc và hướng dẫn các bước tiếp theo.',
        };

        return "{$body}
Xem đầy đủ: {$link}
Liên hệ: {$contact}";
    }

    public function sendMessage(string $userMessage, array $history = [], ?int $userId = null): string
    {
        if ($policy = $this->policyAnswer($userMessage)) {
            return $policy;
        }

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
            ->get(['id', 'name', 'slug', 'price', 'sale_price', 'sale_ends_at', 'stock', 'category_id']);

        if ($products->isEmpty()) {
            return '';
        }

        $lines = $products->map(fn ($p) => '- '.$p->name.' | '.number_format($p->effectivePrice()).'đ | '
            .($p->category->name ?? 'Khác').($p->stock > 0 ? '' : ' | hết hàng')
            .' | link: /products/'.$p->slug);

        return "\n\n[Danh sách sản phẩm của shop]\n".$lines->implode("\n");
    }

    public function searchProducts(string $query): array
    {
        $productKeywords = [
            'giá', 'price', 'bao nhiêu', 'còn hàng', 'hết hàng', 'có không', 'sản phẩm',
            'áo', 'quần', 'giày', 'dép', 'váy', 'túi', 'phụ kiện', 'thời trang', 'size', 'màu',
            'tiệc', 'cưới', 'dự tiệc', 'dạ hội', 'công sở', 'đi làm', 'hẹn hò', 'dạo phố',
            'du lịch', 'phối đồ', 'mix', 'match', 'outfit', 'set đồ', 'thanh lịch', 'sang trọng',
            'năng động', 'quyến rũ', 'thể thao', 'polo', 'sơ mi', 'tote', 'sneaker',
        ];

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
                    'slug' => $p->slug,
                    'price' => number_format($p->effectivePrice()).'đ',
                    'category' => $p->category->name ?? 'N/A',
                    'in_stock' => $p->stock > 0,
                ])
                ->toArray();
        }

        return Product::where('status', 'active')
            ->where(function ($q) use ($cleanQuery) {
                $q->whereLike('name', "%{$cleanQuery}%")
                    ->orWhereLike('brand', "%{$cleanQuery}%")
                    ->orWhereLike('description', "%{$cleanQuery}%");
            })
            ->with('category')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'slug' => $p->slug,
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

    public function recommendSizeText(?int $height, ?int $weight): string
    {
        $h = $height ?? 168;
        $w = $weight ?? 60;

        $size = 'M';
        if ($h < 160 || $w < 50) {
            $size = 'S';
        } elseif ($h <= 168 && $w <= 62) {
            $size = 'M';
        } elseif ($h <= 175 && $w <= 72) {
            $size = 'L';
        } elseif ($h <= 182 && $w <= 82) {
            $size = 'XL';
        } else {
            $size = 'XXL';
        }

        $hText = $height ? "cao {$height}cm" : '';
        $wText = $weight ? "nặng {$weight}kg" : '';
        $profile = trim("{$hText} {$wText}");
        $profileText = $profile ? " với số đo {$profile}" : '';

        return "Dựa trên số đo nhân trắc học của bạn{$profileText}, SocialShop tư vấn kích cỡ chuẩn nhất như sau:\n\n"
            ."👉 **Size đề xuất: Size {$size}** (Vừa vặn, tôn dáng nhất)\n"
            ."- **Thích mặc ôm (Slim-fit):** Có thể chọn size nhỏ hơn 1 nấc nếu nằm ở ngưỡng đầu size.\n"
            ."- **Thích mặc rộng rãi (Oversize / Thoải mái) hoặc có vai/bụng lớn:** Khuyên bạn nên tăng thêm 1 size.\n\n"
            ."💡 *Mẹo:* Bạn cũng có thể mở bất kỳ sản phẩm nào và bấm vào nút **'Gợi ý size thông minh'** trên trang sản phẩm để kéo thước đo tự động nhé!";
    }

    public function fallbackResponse(string $message, ?int $userId = null): string
    {
        if ($policy = $this->policyAnswer($message)) {
            return $policy;
        }

        $lower = mb_strtolower($message);

        if ($userId) {
            $orderInfo = $this->getUserOrderInfo($userId, $message);
            if (! empty($orderInfo)) {
                return $orderInfo;
            }
        }

        // 1. Nhận diện câu hỏi tư vấn kích cỡ (chiều cao, cân nặng, size)
        if (preg_match('/(\d{1,2}(?:[m\.,]\d{1,2})?|\d{3})\s*(?:cm|m)?.*?(\d{2,3})\s*(?:kg)?/iu', $message)
            || str_contains($lower, 'size') || str_contains($lower, 'kích cỡ') || str_contains($lower, 'chọn size') || str_contains($lower, 'mặc vừa')) {
            $height = null;
            $weight = null;

            if (preg_match('/(\d{1})[m\.,](\d{1,2})/i', $message, $hm)) {
                $height = (int) $hm[1] * 100 + (int) str_pad($hm[2], 2, '0', STR_PAD_RIGHT);
            } elseif (preg_match('/(\d{3})\s*cm\b/i', $message, $hm)) {
                $height = (int) $hm[1];
            } elseif (preg_match('/(?:cao|dài|height)[^0-9]{0,12}(\d{3})\b/i', $message, $hm)) {
                $height = (int) $hm[1];
            }

            if (preg_match('/(\d{2,3})\s*(?:kg|kí|cân|kilo)/i', $message, $wm)) {
                $weight = (int) $wm[1];
            }

            if ($height || $weight) {
                return $this->recommendSizeText($height, $weight);
            }
        }

        // 2. Nhận diện câu hỏi phối đồ / đi tiệc / sự kiện
        if (str_contains($lower, 'tiệc') || str_contains($lower, 'cưới') || str_contains($lower, 'dạ hội')) {
            $partyProduct = Product::where('status', 'active')
                ->where(function ($q) {
                    $q->whereLike('name', '%dạ hội%')
                        ->orWhereLike('name', '%đầm%')
                        ->orWhereLike('name', '%váy%');
                })->first();

            $bagProduct = Product::where('status', 'active')
                ->where(function ($q) {
                    $q->whereLike('name', '%túi%')
                        ->orWhereLike('name', '%coach%')
                        ->orWhereLike('name', '%tote%');
                })->first();

            $resp = "Để đi dự tiệc cưới/dạ hội thanh lịch và sang trọng, SocialShop gợi ý bạn set đồ cực kỳ tôn dáng này nhé:\n\n";
            if ($partyProduct) {
                $resp .= "1. [{$partyProduct->name}](/products/{$partyProduct->slug}) - ".number_format($partyProduct->effectivePrice())."đ (Thiết kế thanh lịch, chất vải cao cấp)\n";
            }
            if ($bagProduct) {
                $resp .= "2. [{$bagProduct->name}](/products/{$bagProduct->slug}) - ".number_format($bagProduct->effectivePrice())."đ (Phụ kiện hoàn hảo tôn nét quý phái)\n";
            }
            $resp .= "\n💡 **Gợi ý phối đồ (Stylist Tip):** Kết hợp cùng giày cao gót màu nude hoặc ánh kim và trang sức tối giản để tạo vẻ đẹp thanh lịch, nổi bật.\n"
                .'Bạn bấm vào link sản phẩm ở trên để xem chi tiết và đặt hàng nhé!';

            return $resp;
        }

        if (str_contains($lower, 'công sở') || str_contains($lower, 'đi làm') || str_contains($lower, 'lịch lãm') || str_contains($lower, 'phối đồ')) {
            $workProduct = Product::where('status', 'active')
                ->where(function ($q) {
                    $q->whereLike('name', '%sơ mi%')
                        ->orWhereLike('name', '%polo%');
                })->first();

            $resp = "Đối với trang phục công sở / đi làm lịch sự, SocialShop gợi ý set đồ chuẩn phong cách Smart Casual:\n\n";
            if ($workProduct) {
                $resp .= "- [{$workProduct->name}](/products/{$workProduct->slug}) - ".number_format($workProduct->effectivePrice())."đ\n";
            }
            $resp .= "\n💡 **Gợi ý phối đồ:** Kết hợp cùng quần âu hoặc quần kaki màu be/xám và giày da hoặc sneaker trắng thanh lịch.\n"
                .'Mời bạn bấm vào tên sản phẩm để xem chi tiết!';

            return $resp;
        }

        $products = $this->searchProducts($message);
        if (! empty($products)) {
            $lines = [];
            foreach ($products as $p) {
                $stock = $p['in_stock'] ? 'còn hàng' : 'hết hàng';
                $slug = $p['slug'] ?? '';
                $link = $slug ? " [Xem ngay](/products/{$slug})" : '';
                $lines[] = "- {$p['name']}: {$p['price']} ({$stock}){$link}";
            }

            return "Các sản phẩm tìm thấy phù hợp với yêu cầu của bạn:\n".implode("\n", $lines)
                ."\n\nBạn có thể bấm vào link sản phẩm để xem ảnh chi tiết và đặt hàng nhé!";
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
            str_contains($lower, 'xin chào') || str_contains($lower, 'hello') || str_contains($lower, 'hi') => 'Xin chào! Rất vui được hỗ trợ bạn. Bạn cần tư vấn chọn size, gợi ý phối đồ hay tìm sản phẩm nào?',
            str_contains($lower, 'giá') || str_contains($lower, 'price') => 'Bạn có thể xem giá sản phẩm trên trang chi tiết sản phẩm. Chúng tôi có nhiều ưu đãi hấp dẫn!',
            str_contains($lower, 'giao hàng') || str_contains($lower, 'ship') => 'Chúng tôi giao hàng toàn quốc. Miễn phí ship cho đơn từ '.$this->freeShipText().'!',
            str_contains($lower, 'thanh toán') || str_contains($lower, 'payment') => 'Chúng tôi hỗ trợ thanh toán COD và VNPay. Bạn chọn phương thức nào phù hợp nhất!',
            str_contains($lower, 'liên hệ') => 'Bạn có thể liên hệ hotline 077123456 hoặc email support@socialshop.vn.',
            default => 'Cảm ơn bạn đã nhắn tin! Tôi có thể giúp bạn tư vấn chọn size theo chiều cao/cân nặng, gợi ý phối đồ đi tiệc, đi làm, hoặc kiểm tra tình trạng đơn hàng. Bạn cần hỗ trợ gì ạ?',
        };
    }
}
