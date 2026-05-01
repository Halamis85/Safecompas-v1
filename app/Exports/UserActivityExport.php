<?php

namespace App\Exports;

use App\Models\UserActivity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export logu aktivit uživatelů (poslední 500 záznamů).
 *
 * Stejné limity jako UserController::getUserActivity — kdo má přístup
 * k záznamům přes UI, má i k jejich exportu (route je gated stejnou permission).
 */
class UserActivityExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    public function collection()
    {
        return UserActivity::with('user:id,firstname,lastname')
            ->select('id', 'created_at', 'action', 'table_name', 'old_values', 'new_values', 'user_id')
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();
    }

    public function headings(): array
    {
        return ['Datum a čas', 'Uživatel', 'Typ aktivity', 'Detaily'];
    }

    public function map($activity): array
    {
        $name = trim(($activity->user->firstname ?? '') . ' ' . ($activity->user->lastname ?? ''));

        // Sestavení sloupce "Detaily" stejně jako v UserController
        $details = $activity->table_name ? "Tabulka: {$activity->table_name}" : '';
        if ($activity->new_values) {
            $keys = array_keys((array) $activity->new_values);
            $details .= ($details !== '' ? ' | ' : '') . 'Pole: ' . implode(', ', $keys);
        }

        return [
            $activity->created_at ? $activity->created_at->format('d.m.Y H:i:s') : '',
            $name !== '' ? $name : '—',
            $activity->action,
            $details !== '' ? $details : '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:D1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:D1')->getFont()
            ->setBold(true)
            ->getColor()->setRGB('FFFFFF');
        $sheet->freezePane('A2');

        return [];
    }

    public function title(): string
    {
        return 'Aktivity uživatelů';
    }
}
