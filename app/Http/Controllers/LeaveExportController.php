<?php

namespace App\Http\Controllers;

use App\Exports\Rh\AnnualRhExport;
use Maatwebsite\Excel\Facades\Excel;

class LeaveExportController extends Controller
{
    public function exportAnnualRh(int $annee)
    {
        return Excel::download(
            new AnnualRhExport($annee),
            "RH_Synthese_{$annee}.xlsx"
        );
    }
}
