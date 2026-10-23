<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Notifications\ApplicationNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class NotificationService
{
    public function alert(mixed $recipients, string $title, string $message, array $options = []): void
    {
        $this->send($recipients, NotificationCategory::Alert, $title, $message, $options);
    }

    public function event(mixed $recipients, string $title, string $message, array $options = []): void
    {
        $this->send($recipients, NotificationCategory::Event, $title, $message, $options);
    }

    public function activity(mixed $recipients, string $title, string $message, array $options = []): void
    {
        $this->send($recipients, NotificationCategory::Activity, $title, $message, $options);
    }

    /** Alias untuk pemanggilan lama/lebih singkat; disimpan sebagai kategori activity. */
    public function log(mixed $recipients, string $title, string $message, array $options = []): void
    {
        $this->activity($recipients, $title, $message, $options);
    }

    public function send(
        mixed $recipients,
        NotificationCategory $category,
        string $title,
        string $message,
        array $options = [],
    ): void {
        if (trim($title) === '' || trim($message) === '') {
            throw new InvalidArgumentException('Judul dan pesan notifikasi wajib diisi.');
        }

        $recipients = $this->normalizeRecipients($recipients);

        if ($recipients->isEmpty()) {
            return;
        }

        $defaults = $this->defaultsFor($category);
        $payload = [
            'category' => $category->value,
            'severity' => $options['severity'] ?? $defaults['severity'],
            'title' => trim($title),
            'message' => trim($message),
            'url' => $options['url'] ?? null,
            'icon' => $options['icon'] ?? $defaults['icon'],
            'actor_id' => $options['actor_id'] ?? null,
            'meta' => $options['meta'] ?? [],
        ];

        Notification::send($recipients, new ApplicationNotification($payload));
    }

    private function normalizeRecipients(mixed $recipients): Collection
    {
        if ($recipients instanceof Authenticatable || (is_object($recipients) && in_array(RoutesNotifications::class, class_uses_recursive($recipients), true))) {
            return collect([$recipients]);
        }

        if ($recipients instanceof Collection || $recipients instanceof EloquentCollection) {
            return $recipients;
        }

        if (is_array($recipients) || $recipients instanceof \Traversable) {
            return collect($recipients);
        }

        throw new InvalidArgumentException('Penerima notifikasi harus model Notifiable atau kumpulan model Notifiable.');
    }

    private function defaultsFor(NotificationCategory $category): array
    {
        return match ($category) {
            NotificationCategory::Alert => ['severity' => 'warning', 'icon' => 'fa-solid fa-triangle-exclamation'],
            NotificationCategory::Event => ['severity' => 'info', 'icon' => 'fa-regular fa-calendar'],
            NotificationCategory::Activity => ['severity' => 'primary', 'icon' => 'fa-solid fa-clock-rotate-left'],
        };
    }
}
