<?php

namespace App\Notifications;

use App\Models\LekarnickeMaterial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaterialExpiringSoon extends Notification implements ShouldQueue
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
        $datum      = $material->datum_expirace?->format('d.m.Y') ?? '—';

        return (new MailMessage)
            ->subject('Materiál v lékárničce brzy expiruje')
            ->greeting('Dobrý den ' . ($notifiable->firstname ?? '') . '!')
            ->line("V lékárničce brzy expiruje materiál.")
            ->line("**Lékárnička:** {$lekarnicky->nazev} ({$lekarnicky->umisteni})")
            ->line("**Materiál:** {$material->nazev_materialu}")
            ->line("**Aktuální stav:** {$material->aktualni_pocet} {$material->jednotka}")
            ->line("**Datum expirace:** {$datum}")
            ->action('Zobrazit lékárničku', url('/lekarnicke'))
            ->line('Doporučujeme materiál včas doplnit nebo zlikvidovat.');
    }

    public function toArray($notifiable): array
    {
        $material   = $this->material;
        $lekarnicky = $material->lekarnicky;

        return [
            'material_id'    => $material->id,
            'lekarnicky_id'  => $lekarnicky->id ?? null,
            'message'        => 'Materiál brzy expiruje',
            'employee_name'  => $lekarnicky->nazev ?? '—',
            'product_name'   => $material->nazev_materialu,
            'size'           => 'Expiruje: ' . ($material->datum_expirace?->format('d.m.Y') ?? '—'),
            'icon'           => 'fa-solid fa-triangle-exclamation',
            'color'          => ' warning',
            'kind'           => 'material_expiring',
        ];
    }
}
