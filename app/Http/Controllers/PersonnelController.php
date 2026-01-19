<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Imports\PersonnelsImport;
use Illuminate\Support\Facades\Validator;
use App\Models\Direction;
use App\Models\Service;
use App\Models\Fonction;
use App\Models\Leave;
use App\Models\LeaveType;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade as PDF;
use ZipArchive;
use Carbon\Carbon;

class PersonnelController extends Controller
{
    public function index(Request $request)
    {
        $query = Personnel::with(['direction', 'service', 'fonction']);

        if ($request->filled('nom')) {
            $query->where('nom', 'ILIKE', '%' . $request->nom . '%');
        }
        if ($request->filled('matricule')) {
            $query->where('matricule', 'ILIKE', '%' . $request->matricule . '%');
        }
        if ($request->filled('direction_id')) {
            $query->where('direction_id', $request->direction_id);
        }

        $perPage = 10; // éléments par page
        $personnels = $query->paginate($perPage);

        return response()->json($personnels);
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'matricule' => 'required|string|max:255|unique:personnels,matricule',
            'date_naissance' => 'nullable|date',
            'adresse' => 'nullable|string',
            'cin' => 'nullable|string',
            'diplome' => 'nullable|string',
            'date_entree' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
            'direction_id' => 'required|exists:directions,id',
            'service_id' => 'required|exists:services,id',
            'fonction_id' => 'required|exists:fonctions,id',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $personnel = Personnel::create($validated);

        return response()->json($personnel->load('direction', 'service', 'fonction'), 201);
    }


    public function update(Request $request, $id)
    {
        $personnel = Personnel::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'matricule' => 'required|string|max:255|unique:personnels,matricule,' . $id,
            'date_naissance' => 'nullable|date',
            'adresse' => 'nullable|string',
            'cin' => 'nullable|string',
            'diplome' => 'nullable|string',
            'date_entree' => 'nullable|date',
            'direction_id' => 'nullable|exists:directions,id',
            'service_id' => 'nullable|exists:services,id',
            'fonction_id' => 'nullable|exists:fonctions,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        // Gestion du fichier photo uniquement si une nouvelle est uploadée
        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($personnel->photo) {
                Storage::disk('public')->delete($personnel->photo);
            }

            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $personnel->update($validated);

        return response()->json(['message' => 'Mise à jour réussie', 'personnel' => $personnel->load('direction', 'service', 'fonction')]);
    }


    public function destroy($id)
    {
        $personnel = Personnel::findOrFail($id);

        // Supprimer la photo associée si elle existe
        if ($personnel->photo && Storage::disk('public')->exists($personnel->photo)) {
            Storage::disk('public')->delete($personnel->photo);
        }

        $personnel->delete();

        return response()->json(['message' => 'Personnel supprimé avec succès']);
    }

    public function deleteMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['message' => 'Aucun personnel sélectionné'], 400);
        }

        Personnel::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Personnels supprimés avec succès']);
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        $import = new PersonnelsImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'message' => 'Import terminé',
            'imported' => $import->importedCount,
            'errors' => $import->errors,
        ]);
    }

    public function leaveSummary($id)
    {
        $personnel = Personnel::with(['leaves' => function ($query) {
            $query->where('statut', 'approved')
                ->select('id', 'personnel_id', 'type', 'date_debut', 'date_fin', 'raison');
        }])->findOrFail($id);

        // Calcul du droit total, nombre de jours pris et restant
        $totalRight = 30; // par exemple, configurable ou basé sur l'ancienneté
        $totalTaken = $personnel->leaves->sum(function ($leave) {
            return \Carbon\Carbon::parse($leave->date_debut)
                ->diffInDays(\Carbon\Carbon::parse($leave->date_fin)) + 1;
        });

        return response()->json([
            'personnel' => $personnel->nom . ' ' . $personnel->prenom,
            'matricule' => $personnel->matricule,
            'fonction' => $personnel->fonction?->nom ?? '',
            'totalRight' => $totalRight,
            'totalTaken' => $totalTaken,
            'remaining' => max($totalRight - $totalTaken, 0),
            'leaves' => $personnel->leaves,
        ]);
    }


   public function show($id, Request $request)
    {
        $year = $request->query('year', now()->year);

        $personnel = Personnel::with(['direction', 'service', 'fonction', 'documents'])
            ->findOrFail($id);

        // --- Solde global ---
        $entree = Carbon::parse($personnel->date_entree);
        $moisTravailles = $entree->diffInMonths(now());
        $droitTotal = round($moisTravailles * 2.5, 2);

        $congesPris = Leave::where('personnel_id', $id)
            ->where('status', 'approuve')
            ->whereHas('leaveType', function ($q) {
                $q->where('avec_solde', true)
                ->where('est_exceptionnel', false)
                ->whereNull('limite_jours');
            })
            ->sum('jours_utilises');

        $soldeRestant = round($droitTotal - $congesPris, 2);

        // --- Soldes par type ---
        $leaveTypes = LeaveType::all();
        $parType = $leaveTypes->map(function ($type) use ($id, $year) {
            $joursPrisType = Leave::where('personnel_id', $id)
                ->where('leave_type_id', $type->id)
                ->whereYear('date_debut', $year)
                ->where('status', 'approuve')
                ->sum('jours_utilises');

            $droit = $type->est_exceptionnel ? $type->limite_jours : null;
            $solde = $droit !== null ? max(0, $droit - $joursPrisType) : null;

            return [
                'type' => $type->nom,
                'droit_total' => $droit,
                'jours_pris' => $joursPrisType,
                'solde_restant' => $solde,
            ];
        });

        // --- Historique des congés ---
        $historique = Leave::with('leaveType')
            ->where('personnel_id', $id)
            ->orderBy('date_debut', 'desc')
            ->get();

        return response()->json([
            'personnel' => $personnel,
            'conges' => [
                'global' => [
                    'droit_total' => $droitTotal,
                    'jours_pris' => $congesPris,
                    'solde_restant' => $soldeRestant,
                ],
                'par_type' => $parType,
                'historique' => $historique,
            ],
        ]);
    }




    public function exportPDF($id)
    {
        $personnel = Personnel::with(['direction', 'service', 'fonction', 'documents'])->findOrFail($id);

        $pdf = PDF::loadView('pdf.personnel-fiche', compact('personnel'))->setPaper('A4');

        return $pdf->download("Fiche_{$personnel->nom}_{$personnel->prenom}.pdf");
    }

    public function exportFichesZip()
    {
        $personnels = Personnel::with(['direction', 'service', 'fonction', 'documents'])->get();
        $zipFileName = 'fiches_personnels.zip';
        $zipPath = storage_path("app/public/{$zipFileName}");

        // Créer un dossier temporaire
        $tempDir = storage_path('app/public/tmp_fiches');
        if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

        foreach ($personnels as $personnel) {
            $pdf = PDF::loadView('pdf.personnel-fiche', ['personnel' => $personnel]);
            $fileName = 'fiche_' . $personnel->matricule . '.pdf';
            $pdf->save("{$tempDir}/{$fileName}");
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach (glob("{$tempDir}/*.pdf") as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        }

        // Supprimer les fichiers temporaires après zip
        foreach (glob("{$tempDir}/*.pdf") as $file) {
            unlink($file);
        }
        rmdir($tempDir);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function search(Request $request)
    {
        $q = $request->query('q', '');

        $personnels = Personnel::when($q, function ($query, $q) {
            $query->where('nom', 'ilike', "%{$q}%")
                ->orWhere('prenom', 'ilike', "%{$q}%")
                ->orWhere('matricule', 'ilike', "%{$q}%");
        })->orderBy('nom')->get();

        return response()->json($personnels);
    }
}
