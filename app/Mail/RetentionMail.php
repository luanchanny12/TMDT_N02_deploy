<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class RetentionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const SUBJECTS = [
        'welcome' => 'Chào mừng bạn đến với SocialShop',
        'cart_reminder' => 'Giỏ hàng của bạn vẫn đang chờ',
        'offer' => 'Ưu đãi riêng cho bạn — chỉ trong 48 giờ',
    ];

    public string $unsubscribeUrl;

    public function __construct(
        public User $user,
        public string $type,
        public array $data = [],
    ) {
        $this->unsubscribeUrl = URL::signedRoute('marketing.unsubscribe', ['user' => $user->id]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::SUBJECTS[$this->type]);
    }

    public function headers(): Headers
    {
        // Nút "Hủy đăng ký" có sẵn của Gmail / Outlook
        return new Headers(text: [
            'List-Unsubscribe' => "<{$this->unsubscribeUrl}>",
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(view: "emails.retention.{$this->type}", with: $this->data);
    }
}
