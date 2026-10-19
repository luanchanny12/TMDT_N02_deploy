<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $subject = $this->order->payment_status === 'paid'
            ? "[SocialShop] Hóa đơn điện tử & Xác nhận thanh toán #{$this->order->order_code}"
            : "[SocialShop] Hóa đơn & Xác nhận đơn hàng #{$this->order->order_code}";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
