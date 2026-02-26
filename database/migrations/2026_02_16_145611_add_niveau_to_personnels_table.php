<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->unsignedTinyInteger('niveau')
                ->default(4)
                ->comment('1=DG, 2=Directeur, 3=Manager, 4=Employe');
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
