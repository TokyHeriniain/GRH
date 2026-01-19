<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('annual_leave_closures', function (Blueprint $table) {
            $table->id();

            $table->integer('annee')->index();

            $table->unsignedBigInteger('personnel_id')->index();

            // 🔹 Données figées AVANT clôture
            $table->decimal('solde_avant', 8, 2);
            $table->decimal('report', 8, 2);
            $table->decimal('perte', 8, 2);

            // 🔹 Données APRÈS report
            $table->decimal('solde_n_plus_1', 8, 2);

            // 🔹 Signature RH
            $table->unsignedBigInteger('validated_by');
            $table->timestamp('validated_at');

            $table->timestamps();

            $table->unique(['annee', 'personnel_id']); // 🔒 1 clôture / an / personnel
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_leave_closures');
    }
};
