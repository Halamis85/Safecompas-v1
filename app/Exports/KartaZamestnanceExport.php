<?php

namespace App\Exports;

use App\Models\Zamestnanec;
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
 * Export karty zaměstnance — všechny vydané OOPP pro daného zaměstnance.
 *
 * Stejná data jako ZamestnanciController::getObjednavkyVydane,
 * jen ve formátu, který se otevře v Excelu.
 */
class KartaZamestnanceExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    public function __construct(private int $zamestnanecId)
    {
    }

    public function collection()
    {
        return DB::table('objednavky')
            ->join('produkty', 'objednavky.produkt_id', '=', 'produkty.id')
            ->select(
                'produkty.nazev as produkt',
                'objednavky.velikost',
                'objednavky.datum_vydani',
            )
            ->where('objednavky.zamestnanec_id', $this->zamestnanecId)
            ->where('objednavky.status', 'vydano')
            ->orderBy('objednavky.datum_vydani', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return ['Produkt', 'Velikost', 'Datum vydání'];
    }

    public function map($row): array
    {
        return [
            $row->produkt,
            $row->velikost,
            $row->datum_vydani ? (new \DateTime($row->datum_vydani))->format('d.m.Y H:i') : '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:C1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:C1')->getFont()
            ->setBold(true)
            ->getColor()->setRGB('FFFFFF');
        $sheet->freezePane('A2');

        return [];
    }

    public function title(): string
    {
        $z = Zamestnanec::find($this->zamestnanecId);
        if (!$z) {
            return 'Karta zaměstnance';
        }
        // List sheet má max 31 znaků
        $title = "{$z->jmeno} {$z->prijmeni}";
        return mb_substr($title, 0, 31);
    }
}
