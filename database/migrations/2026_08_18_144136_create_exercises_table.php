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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            // Null for the shared library, set for an exercise the user added.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('level')->nullable();
            $table->string('force')->nullable();
            $table->string('mechanic')->nullable();
            $table->string('equipment')->nullable();
            $table->json('primary_muscles');
            $table->json('secondary_muscles');
            $table->json('instructions');
            // Where the illustrations live upstream, so `dofit:import-exercises`
            // can fetch them on demand. Null until the library is imported.
            $table->json('image_paths')->nullable();

            $table->index('name');
            $table->index(['user_id', 'name']);
            $table->index(['category', 'equipment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
