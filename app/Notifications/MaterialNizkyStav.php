<?php

namespace App\Notifications;

use App\Models\LekarnickeMaterial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaterialNizkyStav extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LekarnickeMaterial $material) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $material   = $this->material;
        $lekarnicky = $material->lekarnicky;

        return (new MailMessage)
            ->subject('Nízký stav materiálu v lékárničce')
            ->greeting('Dobrý den ' . ($notifiable->firstname ?? '') . '!')
            ->line("V lékárničce klesl materiál pod minimální stav.")
            ->line("**Lékárnička:** {$lekarnicky->nazev} ({$lekarnicky->umisteni})")
            ->line("**Materiál:** {$material->nazev_materialu}")
            ->line("**Aktuální stav:** {$material->aktualni_pocet} {$material->jednotka}")
            ->line("**Minimální požadovaný stav:** {$material->minimalni_pocet} {$material->jednotka}")
            ->action('Zobrazit lékárničku', url('/lekarnicke'))
            ->line('Doporučujeme materiál co nejdříve doplnit.');
    }

    public function toArray($notifiable): array
    {
        $material   = $this->material;
        $lekarnicky = $material->lekarnicky;

        return [
            'material_id'    => $material->id,
            'lekarnicky_id'  => $lekarnicky->id ?? null,
            'message'        => 'Nízký stav materiálu',
            'employee_name'  => $lekarnicky->nazev ?? '—',
            'product_name'   => $material->nazev_materialu,
            'size'           => "Stav: {$material->aktualni_pocet}/{$material->minimalni_pocet} {$material->jednotka}",
            'icon'           => 'fa-solid fa-arrow-trend-down',
            'color'          => ' warning',
            'kind'           => 'material_low_stock',
        ];
    }
}
