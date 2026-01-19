<?php

// database/migrations/2025_08_01_000002_create_leave_histories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('leave_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_id')->constrained('leaves')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('set null')->nullable();
            $table->string('action'); // ex: "créé", "validé", "modifié", "refusé"
            $table->text('description')->nullable(); // détails supplémentaires
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('leave_histories');
    }
};
