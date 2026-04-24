<?php
namespace Database\Seeders;

use App\Models\Produkt;
use Illuminate\Database\Seeder;

class ProdukktSeeder extends Seeder
{
    public function run()
    {
        Produkt::query()->delete();
        $produkt = [
            [
            'nazev' =>'Rukavice CXS CITA II, protipořezové',
            'obrazek'=>'RukaviceCITA-II.jpg',
            'dostupne_velikosti'=>'7,8,9,10,11,12',
            'druh_id'=>'1',
            'cena'=>'45.00',
            ],
            [
                'nazev' =>'Rukavice, jednorázové, nitrilové',
                'obrazek'=>'Rukavice_jednorazove_nitrilove.jpg',
                'dostupne_velikosti'=>'M,L,XL',
                'druh_id'=>'1',
                'cena'=>'85.00',
            ],
            [
                'nazev' =>'Rukavice ALVAROS, máčené v nitrilu',
                'obrazek'=>'Rukavice_ALVAROS_máčené_nitrilu.jpg',
                'dostupne_velikosti'=>'6,7,8,9,10,11',
                'druh_id'=>'1',
                'cena'=>'25.00',
            ],
            [
                'nazev' =>'',
                'obrazek'=>'Rukavice_ALVAROS_máčené_nitrilu.jpg',
                'dostupne_velikosti'=>'6,7,8,9,10,11',
                'druh_id'=>'1',
                'cena'=>'25.00',
            ],
            [
                'nazev' =>'Rukavice ALVAROS, máčené v ',
                'obrazek'=>'Rukavice_ALVAROS_máčené_nitrilu.jpg',
                'dostupne_velikosti'=>'6,7,8,9,10,11',
                'druh_id'=>'1',
                'cena'=>'25.00',
            ],
            [
                'nazev' =>'Rukavice ALVAROS, máčené v nitrilu',
                'obrazek'=>'Rukavice_ALVAROS_máčené_nitrilu.jpg',
                'dostupne_velikosti'=>'6,7,8,9,10,11',
                'druh_id'=>'1',
                'cena'=>'25.00',
            ],
        ];
        foreach ($produkt as $produkty) {
            produkt::create($produkty);
        }
    }
}
