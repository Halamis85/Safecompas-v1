<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetLink extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token, public string $email) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = url('/password/reset/' . $this->token . '?email=' . urlencode($this->email));

        return (new MailMessage)
            ->subject('Obnovení hesla - Safecompas')
            ->greeting('Dobrý den!')
            ->line('Obdrželi jsme žádost o obnovení vašeho hesla.')
            ->action('Obnovit heslo', $url)
            ->line('Tento odkaz vyprší za 60 minut.')
            ->line('Pokud jste nežádali o reset hesla, ignorujte tento e-mail.');
    }
}
