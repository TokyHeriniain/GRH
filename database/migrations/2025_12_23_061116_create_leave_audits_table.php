<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_audits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('leave_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('personnel_id')->constrained();
            $table->foreignId('actor_id')->nullable()->constrained('users');

            $table->string('action'); // create, validate_rh, reject, close_year...
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_audits');
    }
};
