<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Notification\Services\DeviceLabel;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Notification;

class LoginDetected extends Notification
{
    public function __construct(
        public readonly ?string $ip,
        public readonly ?string $userAgent,
        public readonly CarbonImmutable $loggedInAt,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Stored in the `type` column instead of the class name, so the API never
    // exposes our internal namespaces.
    public function databaseType(object $notifiable): string
    {
        return 'login';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $device = DeviceLabel::fromUserAgent($this->userAgent);
        $ip = $this->ip ?? 'tidak diketahui';

        return [
            'title' => 'Login berhasil',
            'message' => "Akun Anda baru saja masuk dari {$device} (IP {$ip}). Jika ini bukan Anda, segera ganti password.",
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
            'logged_in_at' => $this->loggedInAt->toIso8601String(),
        ];
    }
}
