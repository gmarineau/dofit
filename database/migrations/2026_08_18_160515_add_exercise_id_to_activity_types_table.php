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
        Schema::table('activity_types', function (Blueprint $table) {
            // Null when the user typed a name of their own rather than picking
            // one from the library.
            $table->foreignId('exercise_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exercise_id');
        });
    }
};
