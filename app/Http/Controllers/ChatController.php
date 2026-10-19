<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function stream(Request $request, ChatbotService $chatbot)
    {
        $message = $request->input('message', '');

        if (! is_string($message) || trim($message) === '') {
            return response()->json(['error' => 'Message is required'], 400);
        }

        if (mb_strlen($message) > 1000) {
            return response()->json(['error' => 'Tin nhắn tối đa 1000 ký tự.'], 422);
        }

        $history = $this->sanitizeHistory($request->input('history', []));

        // Lấy thông tin user nếu đã đăng nhập; guest sẽ có $userId = null (chỉ kèm context sản phẩm, không lộ đơn hàng)
        $userId = Auth::id();
        $contextParts = $chatbot->buildContext($message, $userId);

        $apiKey = $chatbot->getApiKey();
        $serviceUrl = config('services.ai_service.url');

        if (! $serviceUrl && (empty($apiKey) || $apiKey === 'test')) {
            return $this->sendSseResponse(function () use ($message, $chatbot, $userId) {
                $text = $chatbot->sendMessage($message, [], $userId);
                $this->sendSseEvent(['text' => $text, 'done' => true]);
            });
        }

        try {
            $model = $chatbot->getModel();
            $systemPrompt = $chatbot->getSystemPrompt();

            // Ưu tiên microservice ai-service (giữ khóa Gemini); không cấu hình thì gọi Gemini trực tiếp
            if ($serviceUrl) {
                $url = rtrim($serviceUrl, '/').'/v1/chat/stream';
                $headers = ['Authorization' => 'Bearer '.config('services.ai_service.token')];
            } else {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?alt=sse";
                $headers = ['x-goog-api-key' => $apiKey];
            }

            $contents = [];
            foreach ($history as $msg) {
                $contents[] = [
                    'role' => $msg['type'] === 'user' ? 'user' : 'model',
                    'parts' => [['text' => $msg['content']]],
                ];
            }

            $payload = [
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt.$contextParts]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 256,
                    'thinkingConfig' => ['thinkingBudget' => 0],
                ],
            ];

            $client = app(Client::class);
            $response = $client->post($url, [
                'json' => $payload,
                'headers' => $headers,
                'timeout' => 20,
                'stream' => true,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Gemini API returned status: '.$response->getStatusCode());
            }

            return $this->sendSseResponse(function () use ($response) {
                $body = $response->getBody();
                $buffer = '';

                while (! $body->eof()) {
                    $chunk = $body->read(4096);
                    if ($chunk === '' || $chunk === false) {
                        break;
                    }
                    $buffer .= $chunk;

                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = substr($buffer, 0, $pos);
                        $buffer = substr($buffer, $pos + 1);

                        $line = trim($line);

                        if (empty($line)) {
                            continue;
                        }

                        if (str_starts_with($line, 'data: ')) {
                            $jsonStr = substr($line, 6);

                            if ($jsonStr === '[DONE]') {
                                $this->sendSseEvent(['done' => true]);

                                return;
                            }

                            $data = json_decode($jsonStr, true);

                            if (json_last_error() !== JSON_ERROR_NONE) {
                                continue;
                            }

                            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                            if ($text !== null) {
                                $this->sendSseEvent(['text' => $text]);
                            }
                        }
                    }
                }

                $this->sendSseEvent(['done' => true]);
                $response->getBody()->close();
            });

        } catch (\Throwable $e) {
            Log::error('Chat streaming error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendSseResponse(function () use ($message, $chatbot, $userId) {
                $text = $chatbot->sendMessage($message, [], $userId);
                $this->sendSseEvent(['text' => $text, 'done' => true]);
            });
        }
    }

    /**
     * Chỉ giữ 20 lượt gần nhất, đúng cấu trúc {type: user|bot, content: string ≤ 2000 ký tự};
     * phần tử sai định dạng bị bỏ qua thay vì làm lỗi 500.
     *
     * @return array<int, array{type: string, content: string}>
     */
    private function sanitizeHistory(mixed $history): array
    {
        if (! is_array($history)) {
            return [];
        }

        $clean = [];

        foreach ($history as $msg) {
            if (! is_array($msg)
                || ! in_array($msg['type'] ?? null, ['user', 'bot'], true)
                || ! is_string($msg['content'] ?? null)) {
                continue;
            }

            $clean[] = [
                'type' => $msg['type'],
                'content' => mb_substr($msg['content'], 0, 2000),
            ];
        }

        return array_slice($clean, -20);
    }

    private function sendSseResponse(callable $callback): StreamedResponse
    {
        return response()->stream(function () use ($callback) {
            if ($this->streamsLive()) {
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                ob_implicit_flush(true);
            }

            $callback();

            if ($this->streamsLive()) {
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Pragma' => 'no-cache',
        ]);
    }

    private function sendSseEvent(array $data): void
    {
        echo 'data: '.json_encode($data)."\n\n";
        if ($this->streamsLive()) {
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
    }

    /**
     * Đẩy từng sự kiện ra trình duyệt ngay; khi chạy test thì giữ trong buffer để kiểm tra nội dung stream.
     */
    private function streamsLive(): bool
    {
        return ! app()->runningUnitTests();
    }
}
