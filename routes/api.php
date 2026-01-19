<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    UserController,
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
    LeaveTypeController,
    ImportLegacyDataController,
    LeaveBalanceController,
    PersonnelSoldesController,
    LeaveExportController,
    ClotureCongeController
    
};
use App\Http\Controllers\Api\PersonnelImportController;
use App\Http\Controllers\Api\PersonnelCongeController;
use Illuminate\Http\Request;
use App\Models\Personnel;
use App\Models\LeaveType;
use App\Exports\RH\AnnualClosurePreviewExport;
use App\Exports\RH\AnnualClosureRhExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Rh\{
    CloturePdfController,
    RhDashboardController,
    RhAuditController,
    ReliquatController,
    AnnualLeaveClosureController
};


// 📦 Authentification publique
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// 🔐 Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // 👤 Profil utilisateur connecté
    Route::get('/me',        [AuthController::class, 'me']);
    Route::post('/logout',   [AuthController::class, 'logout']);
    Route::put('/profile',   [UserController::class, 'updateProfile']);
    Route::get('/stats',     [StatsController::class, 'index']);

    // 📁 Gestion des personnels (RH/Admin)
    Route::get('/personnels',                     [PersonnelController::class, 'index']);
    Route::get('/personnels/{id}',                [PersonnelController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/personnels',                    [PersonnelController::class, 'store']);
    Route::put('/personnels/{id}',                [PersonnelController::class, 'update']);
    Route::delete('/personnels/{id}',             [PersonnelController::class, 'destroy']);
    Route::get('/personnels/{id}/leaves-summary', [PersonnelController::class, 'leaveSummary']);
    Route::post('/personnels/delete-multiple',    [PersonnelController::class, 'deleteMultiple']); 
    
    //Reliquat
    Route::prefix('rh')->group(function () {
        Route::get('/reliquats', [ReliquatController::class, 'index']);
        Route::get('/reliquats/export/excel', [ReliquatController::class, 'exportExcel']);
        Route::get('/reliquats/export/pdf', [ReliquatController::class, 'exportPdf']);
        Route::get('/reliquats/export/excel-multisheet',[ReliquatController::class, 'exportExcelMultiSheet']);
    });
    
    // soldes
    Route::get('/personnels/{personnel}/soldes', [PersonnelSoldesController::class, 'index']);
    // 📥 Import / Export
    Route::post('/personnels/import',           [PersonnelController::class, 'import']);
    Route::post('/personnels/import-preview',   [PersonnelController::class, 'importPreview']);
    Route::get('/personnels/export-excel',      [PersonnelController::class, 'exportExcel']);
    Route::get('/personnels/export-csv',        [PersonnelController::class, 'exportCSV']);
    Route::get('/personnels/export-pdf',        [PersonnelController::class, 'exportPDF']);
    Route::get('/personnels/{id}/export-pdf',   [PersonnelController::class, 'exportPDF']);
    Route::post('/personnels/import/preview',   [PersonnelImportController::class, 'preview']);
    Route::post('/personnels/import',           [PersonnelImportController::class, 'import']);

    Route::get('/personnels/export-excel', [PersonnelExportController::class, 'exportExcel']);
    Route::get('/personnels/export-pdf', [PersonnelExportController::class, 'exportPDF']);
    Route::get('/personnels/{id}/export-pdf', [PersonnelExportController::class, 'exportSinglePDF']);
    Route::get('/personnels/export-fiches-zip', [PersonnelController::class, 'exportFichesZip']);
    

    Route::post('/import/legacy', [ImportLegacyDataController::class, 'import']);
    Route::get('/import/check-balances', [ImportLegacyDataController::class, 'checkBalances']);
    Route::get('/import/histories', [ImportLegacyDataController::class, 'listImportHistories']);

    Route::get('/personnels/{personnel}/conges',[PersonnelCongeController::class, 'index']);


    // 📎 Gestion des documents liés à un personnel
    Route::post('/personnels/{personnel}/documents',   [DocumentController::class, 'store']);
    Route::get('/personnels/{personnel}/documents',    [DocumentController::class, 'index']);
    Route::delete('/documents/{document}',             [DocumentController::class, 'destroy']);
    Route::get('/documents/{id}/download',             [DocumentController::class, 'download']);
    Route::get('/personnels/{id}/documents',           [DocumentController::class, 'getByPersonnel']);

    Route::prefix('rh')->group(function () {
        Route::get('/reliquats/{annee}', [RHRelicatController::class, 'index']);
        Route::get('/reliquats/export/excel/{annee}', [RHRelicatController::class, 'exportExcel']);
        Route::get('/reliquats/export/pdf/{annee}', [RHRelicatController::class, 'exportPdf']);
    });
    Route::prefix('cloture/reliquats')->group(function () {
        Route::get('/avant', [ClotureCongeController::class, 'reliquatsAvantCloture']);
        Route::get('/apres', [ClotureCongeController::class, 'reliquatsApresCloture']);

        Route::get('/apres/export/pdf', [ClotureCongeController::class, 'exportPdf']);
        Route::get('/apres/export/excel', [ClotureCongeController::class, 'exportExcel']);
    });


     // 📅 Gestion des congés

    Route::prefix('rh')->group(function () {
        Route::get('/leaves', [LeaveController::class, 'index']);
        Route::post('/leaves', [LeaveController::class, 'store']);
        Route::put('/leaves/{leave}', [LeaveController::class, 'update']);
        Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy']);

        Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approveRH']);
        Route::post('/leaves/{leave}/reject', [LeaveController::class, 'rejectRH']);

        Route::get('/leaves/export/excel', [LeaveController::class, 'exportExcel']);
        Route::get('/leaves/export/pdf', [LeaveController::class, 'exportPDF']);
    });


    Route::get('/rh/exports/annual/{annee}',[LeaveExportController::class, 'exportAnnualRh']);


    Route::middleware(['auth:sanctum'])->group(function () {

        Route::get(
            '/rh/cloture/export/pdf/{annee}',
            [CloturePdfController::class, 'export']
        );

    });


    // SOLDES VIA LE SERVICE DEDIE
    Route::get('/personnels/{id}/soldes', [LeaveBalanceController::class, 'show']);
    Route::get('/conges/historique', [LeaveController::class, 'historique']);
    /* Route::get('/conges/export-excel', [LeaveController::class, 'exportExcel']);
    Route::get('/conges/export-pdf', [LeaveController::class, 'exportPdf']); */

    //Report solde et cloture
    Route::prefix('rh/cloture')->group(function () {
        Route::get('/preview/{annee}', [LeaveBalanceController::class, 'previewCloture']);
        Route::post('/execute/{annee}', [LeaveBalanceController::class, 'executeCloture']);
        Route::get('/journal/{annee}', [LeaveBalanceController::class, 'journal']);
        Route::get('/status/{annee}', [AnnualLeaveClosureController::class, 'status']);
        Route::get('/closed/{annee}', [AnnualLeaveClosureController::class, 'showClosed']);
        Route::get('historique', [AnnualLeaveClosureController::class, 'historique']);
        Route::get('historique/{annee}', [AnnualLeaveClosureController::class, 'historiqueDetail']);

    });    

    Route::get('/rh/cloture/preview/export/excel/{annee}', function ($annee) {
        return Excel::download(
            new AnnualClosurePreviewExport($annee),
            "Cloture_RH_{$annee}.xlsx"
        );
    });
    

    Route::prefix('rh')->group(function () {
        Route::get('/dashboard', [RhDashboardController::class, 'index']);
        Route::get('/journal', [RhAuditController::class, 'index']);
        Route::get('/journal/export/excel', [RhAuditController::class, 'exportExcel']);
        Route::get('/journal/export/pdf', [RhAuditController::class, 'exportPdf']);
    });

    Route::get('/rh/cloture/export/excel/{annee}', function (int $annee) {
        return Excel::download(
            new AnnualClosureRhExport($annee),
            "cloture_rh_{$annee}.xlsx"
        );
    });

    Route::get(
        '/rh/cloture/export/pdf/{annee}',
        [AnnualLeaveClosureController::class, 'exportPdf']
    );    
    // routes/api.php
    Route::get('/leaves/{leave}/history', function ($leaveId) {
        return \App\Models\LeaveHistory::with('user')
            ->where('leave_id', $leaveId)
            ->latest()
            ->orderBy('created_at', 'desc')
            ->get();
    });

    Route::get('/personnels-search', function (Request $request) {
        $q = $request->q;

        return Personnel::select('id','nom','prenom','matricule')
            ->when($q, fn($query) => $query
                ->where('nom','ilike',"%$q%")
                ->orWhere('prenom','ilike',"%$q%")
                ->orWhere('matricule','ilike',"%$q%")
            )
            ->orderBy('nom')
            ->limit(50)
            ->get();
    });   

    Route::get('/leave-types-search', function (Request $request) {
        $q = $request->get('q');

        return \App\Models\LeaveType::query()
            ->selectRaw('MIN(id) as id, nom')
            ->when($q, fn ($query) =>
                $query->where('nom', 'ILIKE', "%{$q}%")
            )
            ->groupBy('nom')
            ->orderBy('nom')
            ->get();
    });

    // 👑 Administration
    Route::middleware('check.role:Admin')->prefix('admin')->group(function () {
        Route::get('/users',                   [AdminController::class, 'listUsers']);
        Route::put('/users/{user}/role',       [AdminController::class, 'updateUserRole']);
        Route::delete('/users/{user}',         [AdminController::class, 'deleteUser']);
        Route::post('/users/test',             [AdminController::class, 'createTestUser']);
        Route::delete('/users/reset-tests',    [AdminController::class, 'resetTestUsers']);
        Route::post('/users/test-rh',          [AdminController::class, 'createTestRHUser']);
    });

        // Routes CRUD Directions
    Route::apiResource('directions', DirectionController::class);

    // Routes CRUD Services
    Route::apiResource('services', ServiceController::class);

    // Routes CRUD Fonctions
    Route::apiResource('fonctions', FonctionController::class);

    Route::get('/directions-arborescence', [DirectionController::class, 'arborescence']);

    Route::apiResource('personnels', \App\Http\Controllers\PersonnelController::class);

});
