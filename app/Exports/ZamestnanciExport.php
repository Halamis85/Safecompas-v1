<?php

namespace App\Exports;

use App\Models\Zamestnanec;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export přehledu zaměstnanců.
 */
class ZamestnanciExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    public function collection()
    {
        return Zamestnanec::select('id', 'jmeno', 'prijmeni', 'stredisko')
            ->orderBy('prijmeni')
            ->orderBy('jmeno')
            ->get();
    }

    public function headings(): array
    {
        return ['Jméno', 'Příjmení', 'Středisko'];
    }

    public function map($row): array
    {
        return [
            $row->jmeno,
            $row->prijmeni,
            $row->stredisko,
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
        return 'Zaměstnanci';
    }
}
