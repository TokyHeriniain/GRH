<?php
namespace App\Http\Controllers;

use App\Models\Personnel;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PersonnelsExport;
use Barryvdh\DomPDF\Facade\Pdf;

class PersonnelExportController extends Controller
{
    public function exportExcel()
    {
        return Excel::download(new PersonnelsExport, 'personnels.xlsx');
    }

    public function exportPDF()
    {
        $personnels = Personnel::with(['direction', 'service', 'fonction'])->get();

        $pdf = Pdf::loadView('exports.personnels-pdf', ['personnels' => $personnels]);
        return $pdf->download('personnels.pdf');
    }

    public function exportSinglePDF($id)
    {
        $personnel = \App\Models\Personnel::with(['direction', 'service', 'fonction', 'documents'])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.personnel-fiche', [
            'personnel' => $personnel
        ]);

        return $pdf->download("fiche-{$personnel->nom}-{$personnel->prenom}.pdf");
    }

}
