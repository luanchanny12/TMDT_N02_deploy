<?php

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private array $sent = [];

    private function fakeUpstream(Response|\Throwable ...$responses): void
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->sent));
        $this->app->instance(Client::class, new Client(['handler' => $stack]));
    }

    private function sse(string ...$texts): Response
    {
        $body = collect($texts)
            ->map(fn ($t) => 'data: '.json_encode(['candidates' => [['content' => ['parts' => [['text' => $t]]]]]])."\n\n")
            ->implode('');

        return new Response(200, ['Content-Type' => 'text/event-stream'], $body);
    }

    private function ask(string $message = 'gợi ý áo đi làm'): string
    {
        $response = $this->actingAs(User::factory()->create())->post('/chat/stream', [
            'message' => $message,
            'history' => [['type' => 'user', 'content' => $message]],
        ]);
        $response->assertOk();

        return $response->streamedContent();
    }

    public function test_chat_goes_through_ai_service_with_token_and_without_gemini_key(): void
    {
        config([
            'services.ai_service.url' => 'http://ai-service:3000',
            'services.ai_service.token' => 'svc-token',
            'services.gemini.key' => 'real-gemini-key',
        ]);
        $this->fakeUpstream($this->sse('Áo sơ mi ', 'rất hợp!'));

        $content = $this->ask();

        $this->assertStringContainsString(json_encode(['text' => 'Áo sơ mi ']), $content);
        $this->assertStringContainsString(json_encode(['text' => 'rất hợp!']), $content);
        $this->assertStringContainsString('"done":true', $content);

        $request = $this->sent[0]['request'];
        $this->assertSame('http://ai-service:3000/v1/chat/stream', (string) $request->getUri());
        $this->assertSame('Bearer svc-token', $request->getHeaderLine('Authorization'));
        $this->assertFalse($request->hasHeader('x-goog-api-key'), 'Laravel không được gửi khóa Gemini sang microservice');

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame('user', $payload['contents'][0]['role']);
        $this->assertStringContainsString('trợ lý ảo của SocialShop', $payload['systemInstruction']['parts'][0]['text']);
    }

    public function test_falls_back_when_ai_service_is_down(): void
    {
        config([
            'services.ai_service.url' => 'http://ai-service:3000',
            'services.ai_service.token' => 'svc-token',
            'services.gemini.key' => '', // không có khóa → trả lời bằng FAQ/fallback
        ]);
        $this->fakeUpstream(new ConnectException('down', new Request('POST', 'http://ai-service:3000')));

        $content = $this->ask('phí ship bao nhiêu');

        $this->assertStringContainsString('"done":true', $content);
        $this->assertStringContainsString('"text":', $content);
    }

    public function test_direct_gemini_is_used_when_service_not_configured(): void
    {
        config(['services.ai_service.url' => null, 'services.gemini.key' => 'real-gemini-key']);
        $this->fakeUpstream($this->sse('Xin chào'));

        $this->ask();

        $request = $this->sent[0]['request'];
        $this->assertStringContainsString('generativelanguage.googleapis.com', (string) $request->getUri());
        $this->assertSame('real-gemini-key', $request->getHeaderLine('x-goog-api-key'));
    }
}
