<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginReset extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $newPassword;

    public function __construct(User $user, string $newPassword)
    {
        $this->user = $user;
        $this->newPassword = $newPassword;
    }

    // Kanály pro odeslání (email, database)
    public function via($notifiable): array
    {
        return ['mail', 'database']; // Email + uložení do DB
    }

    // Email notifikace
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nové přihlašovací údaje - Lékárna systém')
            ->greeting('Dobrý den ' . $this->user->firstname . '!')
            ->line('Vaše přihlašovací údaje byly resetovány.')
            ->line('**Uživatelské jméno:** ' . $this->user->username)
            ->line('**Nové heslo:** ' . $this->newPassword)
            ->line('**Poznámka:** Doporučujeme vám po přihlášení heslo změnit.')
            ->action('Přihlásit se', url('/login'))
            ->line('Pokud jste o resetování hesla nežádali, kontaktujte prosím administrátora.')
            ->salutation('S pozdravem, Tým lékárny');
    }

    // Pro uložení do databáze (in-app notifikace)
    public function toArray($notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'username' => $this->user->username,
            'firstname' => $this->user->firstname,
            'lastname' => $this->user->lastname,
            'message' => 'Přihlašovací údaje byly resetovány',
            'action' => 'password_reset',
            'icon' => 'fas fa-key',
            'color' => 'warning',
        ];
    }
}
