<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ChatbotWidget;
use Livewire\Livewire;
use Tests\TestCase;

class ChatbotWidgetTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(ChatbotWidget::class)
            ->assertStatus(200);
    }

    public function test_can_toggle_chat_window()
    {
        Livewire::test(ChatbotWidget::class)
            ->assertSet('isOpen', false)
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->call('toggle')
            ->assertSet('isOpen', false);
    }

    public function test_does_not_send_empty_message()
    {
        Livewire::test(ChatbotWidget::class)
            ->set('message', '   ')
            ->call('sendMessage')
            ->assertSet('message', '   ')
            ->assertCount('messages', 0)
            ->assertNotDispatched('startStreaming');
    }

    public function test_can_send_message()
    {
        Livewire::test(ChatbotWidget::class)
            ->set('message', 'Hello')
            ->call('sendMessage')
            ->assertSet('message', '')
            ->assertCount('messages', 1)
            ->assertSet('messages.0.type', 'user')
            ->assertSet('messages.0.content', 'Hello')
            ->assertDispatched('startStreaming', message: 'Hello', history: [['type' => 'user', 'content' => 'Hello']])
            ->assertDispatched('scrollToBottom');
    }

    public function test_can_send_quick_message()
    {
        Livewire::test(ChatbotWidget::class)
            ->call('sendQuick', 'Quick message')
            ->assertSet('message', '')
            ->assertCount('messages', 1)
            ->assertSet('messages.0.type', 'user')
            ->assertSet('messages.0.content', 'Quick message')
            ->assertDispatched('startStreaming')
            ->assertDispatched('scrollToBottom');
    }

    public function test_can_receive_bot_response()
    {
        Livewire::test(ChatbotWidget::class)
            ->set('isLoading', true)
            ->call('receiveBotResponse', 'I am a bot')
            ->assertSet('isLoading', false)
            ->assertCount('messages', 1)
            ->assertSet('messages.0.type', 'bot')
            ->assertSet('messages.0.content', 'I am a bot')
            ->assertDispatched('scrollToBottom');
    }

    public function test_does_not_add_empty_bot_response()
    {
        Livewire::test(ChatbotWidget::class)
            ->set('isLoading', true)
            ->call('receiveBotResponse', '   ')
            ->assertSet('isLoading', false)
            ->assertCount('messages', 0)
            ->assertDispatched('scrollToBottom');
    }

    public function test_bot_message_only_renders_allowed_link_schemes()
    {
        Livewire::test(ChatbotWidget::class)
            ->call('toggle')
            ->set('messages', [
                ['type' => 'bot', 'content' => 'Xem [áo](/products/ao-polo) nhé'],
                ['type' => 'bot', 'content' => 'Bấm [tại đây](javascript:alert(1))'],
            ])
            ->assertSee('href="/products/ao-polo"', false)
            ->assertDontSee('href="javascript:', false);
    }
}
