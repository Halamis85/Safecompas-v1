<?php

// app/Models/Holiday.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class Holiday extends Model
{
    protected $fillable = [
        'name', 'date', 'pattern', 'country_code', 'type',
        'is_public_holiday', 'notes', 'is_dynamic'
    ];

    protected $casts = [
        'date' => 'date',
        'is_public_holiday' => 'boolean',
        'is_dynamic' => 'boolean'
    ];

    // Získání všech svátků pro daný rok
    public static function getForYear($year, $countryCode = 'CZ')
    {
        $holidays = collect();

        // Načteme všechny svátky pro danou zemi
        $allHolidays = static::where('country_code', $countryCode)->get();

        foreach ($allHolidays as $holiday) {
            if ($holiday->is_dynamic || !empty($holiday->pattern)) {
                // Pokud je dynamický nebo má pattern
                $holiday->calculated_date = static::calculateDynamicDate($holiday->pattern, $year);
            } elseif ($holiday->date) {
                // Pokud má pevné datum v DB
                $holiday->calculated_date = Carbon::create($year, $holiday->date->month, $holiday->date->day);
            } else {
                // Pokud nemá ani jedno, přeskočíme ho
                continue;
            }

            if ($holiday->calculated_date) {
                $holidays->push($holiday);
            }
        }

        return $holidays->sortBy('calculated_date');
    }

    // Výpočet dynamických svátků
    public static function calculateDynamicDate($pattern, $year)
    {
        if (str_starts_with($pattern, 'fixed:')) {
            $parts = explode(':', $pattern);
            if (isset($parts[1])) {
                $dateParts = explode('-', $parts[1]);
                if (count($dateParts) === 2) {
                    return Carbon::create($year, (int)$dateParts[0], (int)$dateParts[1]);
                }
            }
        }

        switch ($pattern) {
            case 'easter_monday':
                return static::getEasterMonday($year);
            case 'good_friday':
                return static::getGoodFriday($year);
            case 'easter_sunday':
                return static::getEasterSunday($year);
            case 'mothers_day': // 2. neděle v květnu
                return static::getNthWeekdayOfMonth($year, 5, 0, 2); // neděle (0), květen (5), 2. výskyt
            case 'fathers_day': // 3. neděle v červnu
                return static::getNthWeekdayOfMonth($year, 6, 0, 3);
            default:
                return null;
        }
    }

    // Výpočet Velikonočního pondělí
    public static function getEasterMonday($year)
    {
        $easter = static::getEasterSunday($year);
        return $easter->addDay();
    }

    // Výpočet Velkého pátku
    public static function getGoodFriday($year)
    {
        $easter = static::getEasterSunday($year);
        return $easter->subDays(2);
    }

    // Výpočet Velikonoční neděle (Gaussův algoritmus)
    public static function getEasterSunday($year)
    {
        $a = $year % 19;
        $b = intval($year / 100);
        $c = $year % 100;
        $d = intval($b / 4);
        $e = $b % 4;
        $f = intval(($b + 8) / 25);
        $g = intval(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intval($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intval(($a + 11 * $h + 22 * $l) / 451);
        $month = intval(($h + $l - 7 * $m + 114) / 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day);
    }

    // N-tý den v týdnu v měsíci
    public static function getNthWeekdayOfMonth($year, $month, $weekday, $n)
    {
        $firstDay = Carbon::create($year, $month, 1);
        $firstWeekday = $firstDay->copy()->next($weekday);

        if ($firstWeekday->month !== $month) {
            $firstWeekday = $firstWeekday->next($weekday);
        }

        return $firstWeekday->addWeeks($n - 1);
    }

    // Získání dnešního svátku
    public static function getToday($countryCode = 'CZ')
    {
        $today = now()->toDateString();
        $year = now()->year;

        return static::getForYear($year, $countryCode)
            ->first(function ($holiday) use ($today) {
                return $holiday->calculated_date->toDateString() === $today;
            });
    }
}
