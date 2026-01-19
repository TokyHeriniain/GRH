<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_add_conge_droit_to_personnels_table.php
    public function up()
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->float('droit_conge_mensuel')->default(2.5); // par mois
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            //
        });
    }
};
