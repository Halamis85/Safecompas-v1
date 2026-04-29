<?php

namespace App\Notifications;

use App\Models\LekarnickeMaterial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaterialExpired extends Notification implements ShouldQueue
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
            ->subject('UPOZORNĚNÍ: Materiál v lékárničce je expirovaný')
            ->error()
            ->greeting('Dobrý den ' . ($notifiable->firstname ?? '') . '!')
            ->line("V lékárničce se nachází **expirovaný** materiál.")
            ->line("**Lékárnička:** {$lekarnicky->nazev} ({$lekarnicky->umisteni})")
            ->line("**Materiál:** {$material->nazev_materialu}")
            ->line("**Datum expirace:** {$datum}")
            ->action('Zobrazit lékárničku', url('/lekarnicke'))
            ->line('Materiál okamžitě zlikvidujte podle platných předpisů a nahraďte novým.');
    }

    public function toArray($notifiable): array
    {
        $material   = $this->material;
        $lekarnicky = $material->lekarnicky;

        return [
            'material_id'    => $material->id,
            'lekarnicky_id'  => $lekarnicky->id ?? null,
            'message'        => 'Materiál je EXPIROVANÝ',
            'employee_name'  => $lekarnicky->nazev ?? '—',
            'product_name'   => $material->nazev_materialu,
            'size'           => 'Expiroval: ' . ($material->datum_expirace?->format('d.m.Y') ?? '—'),
            'icon'           => 'fa-solid fa-circle-exclamation',
            'color'          => ' danger',
            'kind'           => 'material_expired',
        ];
    }
}
