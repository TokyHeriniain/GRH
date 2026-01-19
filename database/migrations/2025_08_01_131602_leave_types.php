<?php

// database/migrations/2025_08_01_000000_create_leave_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // ex: Mariage, Congé annuel, etc.
            $table->boolean('avec_solde')->default(true);
            $table->integer('limite_jours')->nullable(); // Si congé fixe (ex: mariage 4 jours)
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('leave_types');
    }
};
