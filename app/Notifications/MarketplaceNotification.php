<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MarketplaceNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public string $dedupeKey = '',
        public bool $internal = false,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $mailer = config('mail.default');

        if (! empty($notifiable->email) && in_array($mailer, ['smtp', 'ses', 'postmark', 'resend', 'mailgun'], true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title)->line($this->body);

        if ($this->url) {
            $mail->action('View', $this->url);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'dedupe_key' => $this->dedupeKey,
            'internal' => $this->internal,
        ];
    }
}
