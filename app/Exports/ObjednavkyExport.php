<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export přehledu objednávek (čekající + objednané u dodavatele).
 *
 * Záměrně nereplikuje "Akce" sloupec ani sloupec s obrázkem — ty patří jen do UI.
 * Data jsou tatáž jako vrací ObjednavkyController::getAktivni.
 */
class ObjednavkyExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    public function collection()
    {
        return DB::table('objednavky')
            ->join('zamestnanci', 'objednavky.zamestnanec_id', '=', 'zamestnanci.id')
            ->join('produkty',    'objednavky.produkt_id',     '=', 'produkty.id')
            ->select(
                'objednavky.datum_objednani',
                'zamestnanci.jmeno',
                'zamestnanci.prijmeni',
                'zamestnanci.stredisko',
                'produkty.nazev as produkt',
                'objednavky.velikost',
                'objednavky.status',
            )
            ->whereIn('objednavky.status', ['cekajici', 'Objednano'])
            ->orderBy('objednavky.datum_objednani', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return ['Datum objednání', 'Jméno', 'Příjmení', 'Středisko', 'Produkt', 'Velikost', 'Status'];
    }

    public function map($row): array
    {
        // Sjednocení statusu na český čitelný text
        $statusLabel = match (strtolower((string) $row->status)) {
            'cekajici'  => 'Čeká',
            'objednano' => 'Objednáno',
            'vydano'    => 'Vydáno',
            default     => $row->status,
        };

        return [
            $row->datum_objednani ? (new \DateTime($row->datum_objednani))->format('d.m.Y') : '',
            $row->jmeno,
            $row->prijmeni,
            $row->stredisko,
            $row->produkt,
            $row->velikost,
            $statusLabel,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Hlavička bold + světle modré pozadí
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:G1')->getFont()
            ->setBold(true)
            ->getColor()->setRGB('FFFFFF');

        // Zmrazit hlavičku
        $sheet->freezePane('A2');

        return [];
    }

    public function title(): string
    {
        return 'Objednávky';
    }
}
