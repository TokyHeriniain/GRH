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
use Carbon\Carbon;
use App\Models\Holiday;
class PersonnelController extends Controller
{
    public function index(Request $request)
    {
        $query = Personnel::query()
            ->with(['direction', 'service', 'fonction']);

        // 🔎 Recherche texte (accent-insensitive)
        if ($request->filled('q')) {
            $q = trim($request->q);

            $query->where(function ($sub) use ($q) {
                $sub->whereRaw(
                        "unaccent(lower(nom)) LIKE unaccent(lower(?))",
                        ["%{$q}%"]
                    )
                    ->orWhereRaw(
                        "unaccent(lower(prenom)) LIKE unaccent(lower(?))",
                        ["%{$q}%"]
                    )
                    ->orWhereRaw(
                        "unaccent(lower(matricule)) LIKE unaccent(lower(?))",
                        ["%{$q}%"]
                    )
                    ->orWhereRaw(
                        "unaccent(lower(cin)) LIKE unaccent(lower(?))",
                        ["%{$q}%"]
                    );
            });
        }

        // 🏢 Filtres
        if ($request->filled('direction_id')) {
            $query->where('direction_id', $request->direction_id);
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // 📄 Pagination
        $perPage = (int) $request->get('per_page', 12);

        return $query
            ->orderBy('nom')
            ->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'matricule' => 'required|string|max:255|unique:personnels,matricule',

            'date_naissance' => 'nullable|date|before_or_equal:today',
            'date_entree' => 'required|date|before_or_equal:today',

            'adresse' => 'nullable|string',
            'cin' => 'nullable|string',
            'diplome' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',

            'direction_id' => 'required|exists:directions,id',
            'service_id' => 'required|exists:services,id',
            'fonction_id' => 'required|exists:fonctions,id',
        ]);

        // 🔐 Contrôle hiérarchique RH
        $service = Service::findOrFail($validated['service_id']);
        if ($service->direction_id != $validated['direction_id']) {
            abort(422, "Service incohérent avec la direction");
        }

        $fonction = Fonction::findOrFail($validated['fonction_id']);
        if ($fonction->service_id != $validated['service_id']) {
            abort(422, "Fonction incohérente avec le service");
        }

        // 👶 Âge légal RH (16 ans)
        if (!empty($validated['date_naissance'])) {
            if (Carbon::parse($validated['date_entree'])
                ->lt(Carbon::parse($validated['date_naissance'])->addYears(16))) {
                abort(422, "Entrée avant l'âge légal RH");
            }
        }

        // 📸 Photo
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $personnel = Personnel::create($validated);

        return response()->json(
            $personnel->load('direction', 'service', 'fonction'),
            201
        );
    }

    public function update(Request $request, $id)
    {
        $personnel = Personnel::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'matricule' => 'required|string|max:255|unique:personnels,matricule,' . $id,

            'date_naissance' => 'nullable|date|before_or_equal:today',
            'date_entree' => 'required|date|before_or_equal:today',

            'adresse' => 'nullable|string',
            'cin' => 'nullable|string',
            'diplome' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',

            'direction_id' => 'required|exists:directions,id',
            'service_id' => 'required|exists:services,id',
            'fonction_id' => 'required|exists:fonctions,id',
        ]);

        // 🔐 Contrôle hiérarchique RH
        $service = Service::findOrFail($validated['service_id']);
        if ($service->direction_id != $validated['direction_id']) {
            abort(422, "Service incohérent avec la direction");
        }

        $fonction = Fonction::findOrFail($validated['fonction_id']);
        if ($fonction->service_id != $validated['service_id']) {
            abort(422, "Fonction incohérente avec le service");
        }

        // 👶 Âge légal RH
        if (!empty($validated['date_naissance'])) {
            if (Carbon::parse($validated['date_entree'])
                ->lt(Carbon::parse($validated['date_naissance'])->addYears(16))) {
                abort(422, "Entrée avant l'âge légal RH");
            }
        }

        // 📸 Photo
        if ($request->hasFile('photo')) {
            if ($personnel->photo) {
                Storage::disk('public')->delete($personnel->photo);
            }
            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $personnel->update($validated);

        return response()->json([
            'message' => 'Mise à jour réussie',
            'personnel' => $personnel->load('direction', 'service', 'fonction')
        ]);
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



    public function show($id, Request $request)
    {
        $year = $request->query('year', now()->year);

        $personnel = Personnel::with(['direction', 'service', 'fonction', 'documents'])
            ->findOrFail($id);

        // ================== SOLDE GLOBAL ==================
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

        // ================== SOLDES PAR TYPE ==================
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
                'jours_pris' => round($joursPrisType, 2),
                'solde_restant' => $solde !== null ? round($solde, 2) : null,
            ];
        });

        // ================== HISTORIQUE AVEC JOURS FÉRIÉS ==================
        $historique = Leave::with(['leaveType', 'validatedBy'])
            ->where('personnel_id', $id)
            ->orderBy('date_debut', 'desc')
            ->get()
            ->map(function ($leave) {

                $impactedHolidays = [];

                if (
                    $leave->leaveType->avec_solde &&
                    !$leave->leaveType->est_exceptionnel
                ) {
                    $impactedHolidays = Holiday::whereBetween(
                        'date',
                        [$leave->date_debut, $leave->date_fin]
                    )
                    ->get()
                    ->map(fn ($h) => [
                        'title' => $h->title,
                        'date'  => Carbon::parse($h->date)->format('Y-m-d'),
                    ])
                    ->toArray();
                }

                return [
                    'id' => $leave->id,

                    // ✅ structure attendue par React
                    'leave_type' => [
                        'nom' => $leave->leaveType->nom,
                    ],

                    'date_debut' => $leave->date_debut,
                    'date_fin' => $leave->date_fin,
                    'heure_debut' => $leave->heure_debut,
                    'heure_fin' => $leave->heure_fin,

                    'jours_utilises' => round($leave->jours_utilises, 2),
                    'status' => $leave->status,

                    'created_at' => $leave->created_at,
                    'updated_at' => $leave->updated_at,

                    'validated_by' => $leave->validatedBy
                        ? ['name' => $leave->validatedBy->name]
                        : null,

                    // ✅ NOM EXACT ATTENDU PAR REACT
                    'impacted_holidays' => $impactedHolidays,
                ];
            });
        return response()->json([
            'personnel' => $personnel,
            'solde_global' => [
                'droit_total' => $droitTotal,           
                'jours_pris' => round($congesPris, 2),
                'solde_restant' => $soldeRestant,
            ],
            'solde_par_type' => $parType,
            'historique_conges' => $historique,
        ]);
    }


    public function exportPDF($id)
    {
        $personnel = Personnel::with(['direction', 'service', 'fonction', 'documents'])->findOrFail($id);

        $pdf = PDF::loadView('pdf.personnel-fiche', compact('personnel'))->setPaper('A4');

        return $pdf->download("Fiche_{$personnel->nom}_{$personnel->prenom}.pdf");
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
