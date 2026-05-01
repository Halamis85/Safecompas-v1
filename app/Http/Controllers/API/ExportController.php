<?php

namespace App\Http\Controllers\API;

use App\Exports\KartaZamestnanceExport;
use App\Exports\ObjednavkyExport;
use App\Exports\UserActivityExport;
use App\Exports\ZamestnanciExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


/**
 * Centrální controller pro Excel exporty.
 *
 * Permission gating se řeší v routes (každá route má vlastní middleware
 * podle toho, do jakého modulu data patří). Sem se uživatel dostane,
 * jen pokud má příslušné oprávnění.
 */
class ExportController extends Controller
{
    /**
     * Přehled aktivních objednávek (čekající + objednané).
     * URL: GET /export/objednavky.xlsx
     */
    public function objednavky(): BinaryFileResponse
    {
        $filename = 'objednavky-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new ObjednavkyExport(), $filename);
    }

    /**
     * Karta zaměstnance — vydané OOPP pro daného zaměstnance.
     * URL: GET /export/karta-zamestnance/{id}.xlsx
     */
    public function kartaZamestnance(int $id): BinaryFileResponse
    {
        $filename = 'karta-zamestnance-' . $id . '-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new KartaZamestnanceExport($id), $filename);
    }

    /**
     * Log aktivit uživatelů.
     * URL: GET /export/aktivity.xlsx
     */
    public function aktivity(): BinaryFileResponse
    {
        $filename = 'aktivity-uzivatelu-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new UserActivityExport(), $filename);
    }

    /**
     * Přehled zaměstnanců.
     * URL: GET /export/zamestnanci.xlsx
     */
    public function zamestnanci(): BinaryFileResponse
    {
        $filename = 'zamestnanci-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new ZamestnanciExport(), $filename);
    }
}

