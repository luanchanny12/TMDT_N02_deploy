<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Thông báo trong trang quản trị (lưu bảng notifications, hiển thị ở chuông trên thanh tiêu đề).
 */
class AdminAlert extends Notification
{
    public const LEVELS = ['info', 'success', 'warning', 'danger'];

    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public ?string $url = null,
        public string $level = 'info',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'level' => in_array($this->level, self::LEVELS, true) ? $this->level : 'info',
        ];
    }
}
