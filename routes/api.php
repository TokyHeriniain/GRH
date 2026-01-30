<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\{
    AuthController,
    StatsController,
    AdminController,
    LeaveController,
    PersonnelController,
    DocumentController,
    DirectionController,
    ServiceController,
    FonctionController,
    HolidayController,
    PersonnelExportController,
    ImportLegacyDataController,
    LeaveBalanceController,
    PersonnelSoldesController,
    LeaveExportController,
    ProfileController
};

use App\Http\Controllers\Api\{
    PersonnelImportController,
    PersonnelCongeController
};

use App\Http\Controllers\Rh\{
    CloturePdfController,
    RhDashboardController,
    RhAuditController,
    ReliquatController,
    AnnualLeaveClosureController
};

use App\Models\Personnel;
use App\Models\LeaveType;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RH\AnnualClosurePreviewExport;
use App\Exports\RH\AnnualClosureRhExport;

/*
|--------------------------------------------------------------------------
| AUTH PUBLIQUE
|--------------------------------------------------------------------------
*/
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| ROUTES PROTÉGÉES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PROFIL
    |--------------------------------------------------------------------------
    */
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile',                [ProfileController::class, 'show']);
    Route::put('/profile',                [ProfileController::class, 'update']);
    Route::put('/profile/password',       [ProfileController::class, 'updatePassword']);

    /*
    |--------------------------------------------------------------------------
    | STATS
    |--------------------------------------------------------------------------
    */
    Route::get('/stats', [StatsController::class, 'index'])
        ->middleware('permission:stats.view');

    /*
    |--------------------------------------------------------------------------
    | PERSONNELS
    |--------------------------------------------------------------------------
    */
    Route::get('/personnels', [PersonnelController::class, 'index'])
        ->middleware('permission:personnels.view');

    Route::get('/personnels/{id}', [PersonnelController::class, 'show'])
        ->whereNumber('id')
        ->middleware('permission:personnels.view');

    Route::post('/personnels', [PersonnelController::class, 'store'])
        ->middleware('permission:personnels.create');

    Route::put('/personnels/{id}', [PersonnelController::class, 'update'])
        ->whereNumber('id')
        ->middleware('permission:personnels.update');

    Route::delete('/personnels/{id}', [PersonnelController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('permission:personnels.delete');

    Route::post('/personnels/delete-multiple', [PersonnelController::class, 'deleteMultiple'])
        ->middleware('permission:personnels.delete');

    /*
    |--------------------------------------------------------------------------
    | SOLDES & HISTORIQUES
    |--------------------------------------------------------------------------
    */
    Route::get('/personnels/{personnel}/soldes', [PersonnelSoldesController::class, 'index'])
        ->middleware('permission:personnels.view');

    Route::get('/personnels/{personnel}/conges', [PersonnelCongeController::class, 'index'])
        ->middleware('permission:leaves.view');
    /*
    |--------------------------------------------------------------------------
    | IMPORT / EXPORT PERSONNELS
    |--------------------------------------------------------------------------
    */
    Route::post('/personnels/import', [PersonnelController::class, 'import'])
        ->middleware('permission:personnels.import');
    Route::post('/import/legacy', [ImportLegacyDataController::class, 'import'])
        ->middleware('permission:personnels.import');
    Route::get('/import/check-balances', [ImportLegacyDataController::class, 'checkBalances'])
        ->middleware('permission:personnels.import');
    Route::get('/import/histories', [ImportLegacyDataController::class, 'listImportHistories'])
        ->middleware('permission:personnels.import');    

    Route::post('/personnels/import-preview', [PersonnelController::class, 'importPreview'])
        ->middleware('permission:personnels.import');

    Route::get('/personnels/export-excel', [PersonnelExportController::class, 'exportExcel'])
        ->middleware('permission:personnels.export');

    Route::get('/personnels/export-pdf', [PersonnelExportController::class, 'exportPDF'])
        ->middleware('permission:personnels.export');


    // 📎 Gestion des documents liés à un personnel
    Route::post('/personnels/{personnel}/documents',   [DocumentController::class, 'store']);
    Route::get('/personnels/{personnel}/documents',    [DocumentController::class, 'index']);
    Route::delete('/documents/{document}',             [DocumentController::class, 'destroy']);
    Route::get('/documents/{id}/download',             [DocumentController::class, 'download']);
    Route::get('/personnels/{id}/documents',           [DocumentController::class, 'getByPersonnel']);

    /*
    |--------------------------------------------------------------------------
    | CONGÉS RH
    |--------------------------------------------------------------------------
    */
    Route::prefix('rh')->group(function () {

        Route::get('/leaves', [LeaveController::class, 'index'])
            ->middleware('permission:leaves.view');

        Route::post('/leaves', [LeaveController::class, 'store'])
            ->middleware('permission:leaves.create');

        Route::put('/leaves/{leave}', [LeaveController::class, 'update'])
            ->middleware('permission:leaves.update');

        Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy'])
            ->middleware('permission:leaves.delete');

        Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approveRH'])
            ->middleware('permission:leaves.approve');

        Route::post('/leaves/{leave}/reject', [LeaveController::class, 'rejectRH'])
            ->middleware('permission:leaves.reject');
    });

    //Employé - Gestion de ses congés
    Route::prefix('me')->group(function () {
        Route::middleware(['auth:sanctum', 'permission:employee.leaves.view'])
            ->get('/conges', [LeaveController::class, 'mesConges']);

        Route::middleware(['auth:sanctum', 'permission:employee.leaves.create'])
            ->post('/conges', [LeaveController::class, 'storeEmploye']);

        Route::middleware(['auth:sanctum', 'permission:employee.soldes.view'])
            ->get('/soldes', [LeaveController::class, 'mesSoldes']);
    });

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD & AUDIT RH
    |--------------------------------------------------------------------------
    */
    Route::prefix('rh')->middleware('permission:rh.dashboard')->group(function () {
        Route::get('/dashboard', [RhDashboardController::class, 'index']);
        Route::get('/dashboard/comparatif', [RhDashboardController::class, 'comparatif']);
    });

    Route::prefix('rh')->middleware('permission:audit.view')->group(function () {
        Route::get('/journal', [RhAuditController::class, 'index']);
        Route::get('/journal/export/excel', [RhAuditController::class, 'exportExcel']);
        Route::get('/journal/export/pdf', [RhAuditController::class, 'exportPdf']);
    });

    /*
    |--------------------------------------------------------------------------
    | CLÔTURE ANNUELLE
    |--------------------------------------------------------------------------
    */
    Route::prefix('rh/cloture')->middleware('permission:rh.closure')->group(function () {

        Route::get('/preview/{annee}', [LeaveBalanceController::class, 'previewCloture']);
        Route::post('/execute/{annee}', [LeaveBalanceController::class, 'executeCloture']);
        Route::get('/journal/{annee}', [LeaveBalanceController::class, 'journal']);
        Route::get('/status/{annee}', [AnnualLeaveClosureController::class, 'status']);
        Route::get('/closed/{annee}', [AnnualLeaveClosureController::class, 'showClosed']);
        Route::get('historique', [AnnualLeaveClosureController::class, 'historique']);
        Route::get('historique/{annee}', [AnnualLeaveClosureController::class, 'historiqueDetail']);

        Route::get('/export/excel/{annee}', function ($annee) {
            return Excel::download(
                new AnnualClosureRhExport($annee),
                "Cloture_RH_Detail_{$annee}.xlsx"
            );
        });
        Route::get('/preview/export/excel/{annee}', function ($annee) {
            return Excel::download(
                new AnnualClosurePreviewExport($annee),
                "Cloture_RH_{$annee}.xlsx"
            );
        });

        Route::get('/export/pdf/{annee}', [AnnualLeaveClosureController::class, 'exportPdf']);
    });

        /*
    |--------------------------------------------------------------------------
    | RÉFÉRENTIELS & RECHERCHES (UI)
    |--------------------------------------------------------------------------
    */
    Route::apiResource('directions', DirectionController::class)
        ->middleware('permission:personnels.view');

    Route::apiResource('services', ServiceController::class)
        ->middleware('permission:personnels.view');

    Route::apiResource('fonctions', FonctionController::class)
        ->middleware('permission:personnels.view');

    Route::get('/directions-arborescence', [DirectionController::class, 'arborescence'])
        ->middleware('permission:personnels.view');

    Route::get('/personnels-search', function (Request $request) {
        $q = $request->q;

        return Personnel::select('id','nom','prenom','matricule')
            ->when($q, fn ($query) =>
                $query->where('nom','ilike',"%$q%")
                    ->orWhere('prenom','ilike',"%$q%")
                    ->orWhere('matricule','ilike',"%$q%")
            )
            ->orderBy('nom')
            ->limit(50)
            ->get();
    })->middleware('permission:personnels.view');

    Route::get('/leave-types-search', function (Request $request) {
        $q = $request->get('q');

        return LeaveType::query()
            ->selectRaw('MIN(id) as id, nom')
            ->when($q, fn ($query) =>
                $query->where('nom', 'ILIKE', "%{$q}%")
            )
            ->groupBy('nom')
            ->orderBy('nom')
            ->get();
    })->middleware('permission:referentiels.view');


    /*
    |--------------------------------------------------------------------------
    | HISTORIQUE GLOBAL CONGÉS
    |--------------------------------------------------------------------------
    */
    Route::get('/conges/historique', [LeaveController::class, 'historique'])
        ->middleware('permission:leaves.view');

    Route::get('/conges/historique/export/excel', [LeaveController::class, 'exportHistoriqueExcel'])
        ->middleware('permission:leaves.export');

    Route::get('/conges/historique/export/pdf', [LeaveController::class, 'exportHistoriquePdf'])
        ->middleware('permission:leaves.export');

    /*
    |--------------------------------------------------------------------------
    | RELIQUATS RH
    |--------------------------------------------------------------------------
    */
    Route::prefix('rh')->middleware('permission:rh.reliquats')->group(function () {
        Route::get('/reliquats', [ReliquatController::class, 'index']);
        Route::get('/reliquats/export/excel', [ReliquatController::class, 'exportExcel']);
        Route::get('/reliquats/export/pdf', [ReliquatController::class, 'exportPdf']);
        Route::get('/reliquats/export/excel-multisheet', [ReliquatController::class, 'exportExcelMultiSheet']);
    });

    /*
    |--------------------------------------------------------------------------
    | AUTRES
    |--------------------------------------------------------------------------
    */
    Route::apiResource('holidays', HolidayController::class)
        ->middleware('permission:holidays.manage');

    Route::get('/leaves/{leave}/history', function ($leaveId) {
        return \App\Models\LeaveHistory::with('user')
            ->where('leave_id', $leaveId)
            ->latest()
            ->get();
    })->middleware('permission:leaves.view');

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRATION (UTILISATEURS / RÔLES / PERMISSIONS)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->middleware('permission:users.manage')
        ->group(function () {

            Route::get('/users',        [AdminController::class, 'listUsers']);
            Route::post('/users',       [AdminController::class, 'storeUser']);
            Route::put('/users/{user}', [AdminController::class, 'updateUser'])->whereNumber('user');
            Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->whereNumber('user');

            Route::put('/users/{user}/role', [AdminController::class, 'updateUserRole'])
                ->whereNumber('user');

            Route::get('/roles', [AdminController::class, 'listRoles']);
            Route::get('/roles/{role}/permissions', [AdminController::class, 'getRolePermissions']);
            Route::put('/roles/{role}/permissions', [AdminController::class, 'syncRolePermissions']);

            Route::post('/users/test',        [AdminController::class, 'createTestUser']);
            Route::post('/users/test-rh',     [AdminController::class, 'createTestRHUser']);
            Route::delete('/users/reset-tests',[AdminController::class, 'resetTestUsers']);
            Route::get('/personnels-disponibles',[AdminController::class, 'personnelsDisponibles']);

        });

    Route::post('/admin/users/generate-missing', [AdminController::class, 'generateMissing'])
    ->middleware(['auth:sanctum', 'permission:users.manage']);

    Route::post('/change-password', function (Request $request) {

        $request->validate([
            'password' => 'required|min:6|confirmed'
        ]);

        $user = auth()->user();
        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        return response()->json(['message' => 'Mot de passe changé']);
    })->middleware('auth:sanctum');
    
});