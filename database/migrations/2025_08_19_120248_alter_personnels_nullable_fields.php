<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('personnels', function (Blueprint $table) {
            // Rendre ces champs optionnels
            $table->date('date_naissance')->nullable()->change();
            $table->string('adresse')->nullable()->change();
            $table->string('cin')->nullable()->change();
            $table->string('diplome')->nullable()->change();
            $table->date('date_entree')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('personnels', function (Blueprint $table) {
            // Revenir aux contraintes NOT NULL si besoin
            $table->date('date_naissance')->nullable(false)->change();
            $table->string('adresse')->nullable(false)->change();
            $table->string('cin')->nullable(false)->change();
            $table->string('diplome')->nullable(false)->change();
            $table->date('date_entree')->nullable(false)->change();
        });
    }
};
