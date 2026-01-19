<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leaves', function (Blueprint $table) {
            // 1. Supprimer la contrainte existante si elle existe
            DB::statement('ALTER TABLE leaves DROP CONSTRAINT IF EXISTS leaves_status_check');

            // 2. Modifier le type de colonne + valeur par défaut
            $table->string('status')->default('en_attente')->change();
        });

        // 3. Recréer la contrainte proprement
        DB::statement("ALTER TABLE leaves ADD CONSTRAINT leaves_status_check 
            CHECK (status IN ('en_attente', 'approuve_manager', 'approuve_rh', 'rejete'))");
    }

    public function down()
    {
        // Rollback : supprimer nouvelle contrainte
        DB::statement('ALTER TABLE leaves DROP CONSTRAINT IF EXISTS leaves_status_check');

        Schema::table('leaves', function (Blueprint $table) {
            // remettre un status simple (par exemple 'en_attente' par défaut)
            $table->string('status')->default('en_attente')->change();
        });

        // recréer ancienne contrainte minimale (optionnel, selon ton ancien code)
        DB::statement("ALTER TABLE leaves ADD CONSTRAINT leaves_status_check 
            CHECK (status IN ('en_attente', 'approuve', 'rejete'))");
    }
};
