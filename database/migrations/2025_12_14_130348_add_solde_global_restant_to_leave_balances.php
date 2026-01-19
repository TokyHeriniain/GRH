<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->decimal('solde_global_restant', 8, 2)
                  ->default(0)
                  ->after('solde_global_jours');
        });
    }

    public function down()
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropColumn('solde_global_restant');
        });
    }
};
