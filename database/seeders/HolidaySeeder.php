<?php

// database/seeders/HolidaySeeder.php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run()
    {
        // Smaž staré záznamy
        Holiday::truncate();

        $holidays = [
            // Statické svátky (stejné datum každý rok)
            [
                'name' => 'Karina / Vasil',
                'date' => '2000-01-02', // Rok se ignoruje, použije se jen měsíc a den
                'country_code' => 'CZ',
                'type' => 'public',
                'is_public_holiday' => false,
                'is_dynamic' => false,
                'notes' => 'Jmeniny '
            ],
            [
                'name' => 'Radmila / Radomil',
                'date' => '2000-01-03', // Rok se ignoruje, použije se jen měsíc a den
                'country_code' => 'CZ',
                'type' => 'public',
                'is_public_holiday' => false,
                'is_dynamic' => false,
                'notes' => 'Jmeniny '
            ],
            [
                'name' => 'Diana',
                'date' => '2000-01-04', // Rok se ignoruje, použije se jen měsíc a den
                'country_code' => 'CZ',
                'type' => 'public',
                'is_public_holiday' => false,
                'is_dynamic' => false,
                'notes' => 'Jmeniny '
            ],
            [
                'name' => 'Dalimil',
                'date' => '2000-01-05', // Rok se ignoruje, použije se jen měsíc a den
                'country_code' => 'CZ',
                'type' => 'public',
                'is_public_holiday' => false,
                'is_dynamic' => false,
                'notes' => 'Jmeniny '
            ],
            [
                'name' => 'Lada',
                'date' => '2000-08-07', // Rok se ignoruje, použije se jen měsíc a den
                'country_code' => 'CZ',
                'type' => 'public',
                'is_public_holiday' => false,
                'is_dynamic' => false,
                'notes' => 'Jmeniny '
            ],
            [
                'name' => 'Nový rok',
                'date' => '2000-01-01', // Rok se ignoruje, použije se jen měsíc a den
                'country_code' => 'CZ',
                'type' => 'public',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],
            [
                'name' => 'Svátek práce',
                'date' => '2000-05-01',
                'country_code' => 'CZ',
                'type' => 'public',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],
            [
                'name' => 'Den vítězství',
                'date' => '2000-05-08',
                'country_code' => 'CZ',
                'type' => 'national',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],
            [
                'name' => 'Den slovanských věrozvěstů Cyrila a Metoděje',
                'date' => '2000-07-05',
                'country_code' => 'CZ',
                'type' => 'religious',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],
            [
                'name' => 'Den upálení mistra Jana Husa',
                'date' => '2000-07-06',
                'country_code' => 'CZ',
                'type' => 'national',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],
            [
                'name' => 'Den české státnosti',
                'date' => '2000-09-28',
                'country_code' => 'CZ',
                'type' => 'national',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],
            [
                'name' => 'Den vzniku samostatného československého státu',
                'date' => '2000-10-28',
                'country_code' => 'CZ',
                'type' => 'national',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],
            [
                'name' => 'Den boje za svobodu a demokracii',
                'date' => '2000-11-17',
                'country_code' => 'CZ',
                'type' => 'national',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],
            [
                'name' => 'Štědrý den',
                'date' => '2000-12-24',
                'country_code' => 'CZ',
                'type' => 'religious',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],
            [
                'name' => '1. svátek vánoční',
                'date' => '2000-12-25',
                'country_code' => 'CZ',
                'type' => 'religious',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],
            [
                'name' => '2. svátek vánoční',
                'date' => '2000-12-26',
                'country_code' => 'CZ',
                'type' => 'religious',
                'is_public_holiday' => true,
                'is_dynamic' => false,
                'notes' => 'Státní svátek'
            ],

            // Dynamické svátky (počítají se podle patterns)
            [
                'name' => 'Velikonoční pondělí',
                'pattern' => 'easter_monday',
                'country_code' => 'CZ',
                'type' => 'religious',
                'is_public_holiday' => true,
                'is_dynamic' => true,
                'notes' => 'Pohyblivý svátek - pondělí po Velikonoční neděli'
            ],
            [
                'name' => 'Velikonoční neděle',
                'pattern' => 'easter_sunday',
                'country_code' => 'CZ',
                'type' => 'religious',
                'is_public_holiday' => false,
                'is_dynamic' => true,
                'notes' => 'Pohyblivý svátek'
            ],
            [
                'name' => 'Velký pátek',
                'pattern' => 'good_friday',
                'country_code' => 'CZ',
                'type' => 'religious',
                'is_public_holiday' => false,
                'is_dynamic' => true,
                'notes' => 'Pohyblivý svátek - pátek před Velikonoční nedělí'
            ],
            [
                'name' => 'Den matek',
                'pattern' => 'mothers_day',
                'country_code' => 'CZ',
                'type' => 'social',
                'is_public_holiday' => false,
                'is_dynamic' => true,
                'notes' => '2. neděle v květnu'
            ],
            [
                'name' => 'Den otců',
                'pattern' => 'fathers_day',
                'country_code' => 'CZ',
                'type' => 'social',
                'is_public_holiday' => false,
                'is_dynamic' => true,
                'notes' => '3. neděle v červnu'
            ]
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }
    }
}
