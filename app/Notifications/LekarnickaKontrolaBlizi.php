<?php

namespace App\Notifications;

use App\Models\Lekarnicky;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LekarnickaKontrolaBlizi extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lekarnicky $lekarnicky) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $lekarnicky = $this->lekarnicky;
        $datum = $lekarnicky->dalsi_kontrola?->format('d.m.Y') ?? '—';

        return (new MailMessage)
            ->subject('Blíží se termín kontroly lékárničky')
            ->greeting('Dobrý den ' . ($notifiable->firstname ?? '') . '!')
            ->line("Blíží se termín pravidelné kontroly lékárničky.")
            ->line("**Lékárnička:** {$lekarnicky->nazev}")
            ->line("**Umístění:** {$lekarnicky->umisteni}")
            ->line("**Zodpovědná osoba:** {$lekarnicky->zodpovedna_osoba}")
            ->line("**Termín kontroly:** {$datum}")
            ->action('Zobrazit lékárničku', url('/lekarnicke'))
            ->line('Prosím, naplánujte kontrolu lékárničky včas.');
    }

    public function toArray($notifiable): array
    {
        $lekarnicky = $this->lekarnicky;

        return [
            'lekarnicky_id'  => $lekarnicky->id,
            'message'        => 'Blíží se kontrola lékárničky',
            'employee_name'  => $lekarnicky->nazev,
            'product_name'   => $lekarnicky->umisteni,
            'size'           => 'Kontrola: ' . ($lekarnicky->dalsi_kontrola?->format('d.m.Y') ?? '—'),
            'icon'           => 'fa-solid fa-clipboard-check',
            'color'          => ' primary',
            'kind'           => 'lekarnicka_inspection',
        ];
    }
}
