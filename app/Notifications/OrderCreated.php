<?php


namespace App\Notifications;

use App\Models\Objednavka;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $objednavka;

    public function __construct(Objednavka $objednavka)
    {
        $this->objednavka = $objednavka;
    }

    // Kanály pro odeslání (email, database, slack...)
    public function via($notifiable): array
    {
        return ['mail', 'database']; // Email + uložení do DB
    }

    // Email notifikace
    public function toMail($notifiable): MailMessage
    {
        $objednavka = $this->objednavka;

        return (new MailMessage)
            ->subject('Nová objednávka OOPP')
            ->greeting('Dobrý den!')
            ->line("Byla vytvořena nová objednávka OOPP.")
            ->line("**Zaměstnanec:** {$objednavka->zamestnanec->jmeno} {$objednavka->zamestnanec->prijmeni}")
            ->line("**Produkt:** {$objednavka->produkt->nazev}")
            ->line("**Velikost:** {$objednavka->velikost}")
            ->line("**Datum:** " . $objednavka->datum_objednani->format('d.m.Y'))
            ->action('Zobrazit objednávku', url("/prehled"))
            ->line('Děkujeme!');
    }

    // Pro uložení do databáze (in-app notifikace)
    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->objednavka->id,
            'employee_name' => $this->objednavka->zamestnanec->jmeno . ' ' . $this->objednavka->zamestnanec->prijmeni,
            'employee_stredisko' => $this->objednavka->zamestnanec->stredisko,
            'product_name' => $this->objednavka->produkt->nazev,
            'size' => $this->objednavka->velikost,
            'date' => $this->objednavka->datum_objednani->format('d.m.Y'),
            'img' => $this->objednavka->produkt->obrazek,
            'message' => 'Nová objednávka byla vytvořena',
            'icon' => 'fas fa-shopping-cart',
            'color' => 'primary',
        ];
    }
}
