<?php

namespace App\Modules\Auth\Notifications;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordLink extends Notification
{
    public function __construct(public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(CanResetPassword $notifiable): MailMessage
    {
        $minutes = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Atur ulang password GovFac Report')
            ->greeting("Halo {$notifiable->name},")
            ->line('Kami menerima permintaan untuk mengatur ulang password akun GovFac Report Anda.')
            ->action('Atur ulang password', $this->url($notifiable))
            ->line("Tautan ini berlaku {$minutes} menit dan hanya bisa dipakai sekali.")
            ->line('Jika Anda tidak merasa meminta ini, abaikan email ini. Password Anda tidak akan berubah.')
            ->salutation('Tim GovFac Report');
    }

    public function url(CanResetPassword $notifiable): string
    {
        // config/app.php is Dev A's file. Until it defines frontend_url we fall
        // back to the same default as FRONTEND_URL in .env.example.
        $frontend = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');

        return $frontend.'/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
