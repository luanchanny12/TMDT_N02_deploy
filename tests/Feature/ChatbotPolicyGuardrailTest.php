<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotPolicyGuardrailTest extends TestCase
{
    use RefreshDatabase;

    public static function policyQuestions(): array
    {
        return [
            'đổi trả' => ['Áo mặc không vừa thì đổi trả thế nào?', '7 ngày'],
            'đổi size' => ['mình muốn đổi size quần', 'khách chịu phí gửi lại'],
            'hoàn tiền' => ['Bao lâu thì được hoàn tiền?', '3–5 ngày làm việc'],
            'bảo hành' => ['Đồng hồ có bảo hành không', 'lỗi do nhà sản xuất'],
            'khiếu nại' => ['Tôi muốn khiếu nại đơn hàng', '24 giờ làm việc'],
            'không dấu' => ['doi tra hang the nao', '7 ngày'],
        ];
    }

    /** @dataProvider policyQuestions */
    public function test_policy_questions_get_fixed_answer_with_link_and_hotline(string $question, string $expected): void
    {
        $answer = app(ChatbotService::class)->policyAnswer($question);

        $this->assertNotNull($answer);
        $this->assertStringContainsString($expected, $answer);
        $this->assertStringContainsString(route('pages.return-policy'), $answer);
        $this->assertStringContainsString('077123456', $answer);
    }

    public function test_other_questions_are_not_intercepted(): void
    {
        $this->assertNull(app(ChatbotService::class)->policyAnswer('Có áo hoodie nam không?'));
        $this->assertNull(app(ChatbotService::class)->policyAnswer('Phí ship ra Hà Nội bao nhiêu'));
    }

    public function test_logged_in_user_policy_question_never_reaches_ai(): void
    {
        Config::set('services.gemini.key', 'valid_api_key');
        Http::fake(['*' => Http::response('{"candidates":[{"content":{"parts":[{"text":"Được hoàn tiền trong 30 ngày"}]}}]}')]);

        $response = $this->actingAs(User::factory()->create())
            ->postJson('/chat/stream', ['message' => 'Cho mình hỏi chính sách hoàn tiền']);

        $content = $response->streamedContent();
        $this->assertStringContainsString('3–5 ngày làm việc', json_decode(trim(substr($content, strpos($content, '{'))), true)['text'] ?? $content);
        Http::assertNothingSent();
    }

    public function test_send_message_uses_policy_answer_without_api_call(): void
    {
        Config::set('services.gemini.key', 'valid_api_key');
        Http::fake();

        $answer = app(ChatbotService::class)->sendMessage('Hàng bị lỗi thì bảo hành sao?');

        $this->assertStringContainsString('077123456', $answer);
        Http::assertNothingSent();
    }

    public function test_system_prompt_forbids_inventing_policy(): void
    {
        $this->assertStringContainsString('không tự đặt ra điều kiện', app(ChatbotService::class)->getSystemPrompt());
    }
}
