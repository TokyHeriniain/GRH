<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Personnel;
use App\Models\LeaveBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnnualLeaveClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_annual_leave_closure_process()
    {
        $user = User::factory()->create();

        $personnel = Personnel::factory()->create();

        LeaveBalance::create([
            'personnel_id'        => $personnel->id,
            'annee_reference'     => 2025,
            'solde_global_jours'  => 5,
            'solde_global_heures' => 40,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/leave/close-year', [
                'annee' => 2025
            ]);

        $response->assertStatus(200);

        // 1️⃣ Audit créé
        $this->assertDatabaseHas('annual_leave_closures', [
            'annee' => 2025,
            'personnel_id' => $personnel->id,
            'solde_reporte_jours' => 5
        ]);

        // 2️⃣ Nouveau solde 2026
        $this->assertDatabaseHas('leave_balance', [
            'personnel_id'    => $personnel->id,
            'annee_reference' => 2026,
        ]);
    }
}
