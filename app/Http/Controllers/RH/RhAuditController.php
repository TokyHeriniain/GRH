<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\LeaveAudit;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeaveAuditExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RhAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveAudit::with(['actor', 'personnel'])
            ->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return response()->json(
            $query->paginate(20)->through(function ($a) {
                return [
                    'id' => $a->id,
                    'action' => $a->action,
                    'personnel' => optional($a->personnel)->nom_complet,
                    'acteur' => optional($a->actor)->name,
                    'date' => Carbon::parse($a->created_at)
                        ->timezone(config('app.timezone'))
                        ->format('d/m/Y H:i:s'),
                    'ip' => $a->ip_address,
                ];
            })
        );
    }

    /* ================= EXPORTS ================= */

    public function exportExcel(Request $request)
    {
        return \Excel::download(
            new \App\Exports\LeaveAuditExport($request),
            'journal_rh.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $audits = LeaveAudit::orderByDesc('created_at')->get();

        $pdf = \PDF::loadView('pdf.journal_rh', compact('audits'));
        return $pdf->download('journal_rh.pdf');
    }
}
