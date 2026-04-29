<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Naplánované úkoly (Laravel scheduler)
|--------------------------------------------------------------------------
| Spouští se voláním `php artisan schedule:run` každou minutu z cronu.
| Cron záznam (přidat na produkční server jednou):
|
|   * * * * * cd /cesta/k/projektu && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Denně v 7:00 ráno - kontrola lékárniček a odeslání notifikací
Schedule::command('lekarnicky:send-notifications')
    ->dailyAt('07:00')
    ->timezone('Europe/Prague')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/lekarnicky-notifications.log'));

