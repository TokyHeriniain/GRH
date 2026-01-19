<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeavesTable extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();

            // Clés étrangères
            $table->foreignId('personnel_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained()->onDelete('restrict');

            // Période
            $table->date('date_debut');
            $table->date('date_fin');
            $table->time('heure_debut');
            $table->time('heure_fin');

            // Détails
            $table->text('raison')->nullable();
            $table->enum('status', ['en_attente', 'approuve', 'rejete'])->default('en_attente');

            // Calculs
            $table->decimal('jours_utilises', 5, 2)->default(0);      // Ex: 1.5 jours
            $table->decimal('droit_total', 5, 2)->nullable();         // Total cumulé disponible
            $table->decimal('solde_restant', 5, 2)->nullable();       // Après cette demande

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
}
