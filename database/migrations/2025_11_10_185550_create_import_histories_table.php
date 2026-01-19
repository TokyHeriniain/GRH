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
        Schema::create('import_histories', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->nullable();
            $table->integer('rows_total')->default(0);
            $table->integer('rows_imported')->default(0);
            $table->json('errors')->nullable();
            $table->string('type')->nullable(); // 'personnels' | 'leaves' | 'soldes'
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('import_histories');
    }

};
