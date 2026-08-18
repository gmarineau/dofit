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

            $table->index('name');
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
