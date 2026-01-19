<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveBalancesTable extends Migration
{
    public function up()
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();

            // FK vers personnel
            $table->unsignedBigInteger('personnel_id');

            // Solde global (toutes les heures avec_solde)
            $table->decimal('solde_global_heures', 8, 2)->default(0);

            // Solde global en jours (calculé = heures / 8)
            $table->decimal('solde_global_jours', 8, 2)->default(0);

            // Solde du congé annuel (optionnel)
            $table->decimal('solde_annuel_heures', 8, 2)->default(0);
            $table->decimal('solde_annuel_jours', 8, 2)->default(0);

            // Solde par type de congé (pour les types exceptionnels + autres)
            $table->json('soldes_par_type')->nullable();

            $table->timestamps();

            $table->foreign('personnel_id')->references('id')->on('personnels')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_balances');
    }
}
