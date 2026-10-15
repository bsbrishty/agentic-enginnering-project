<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the new columns used by the public collection form per PRD §5:
     *  - `is_active`              Toggles whether a space accepts submissions.
     *  - `star_ratings_enabled`   Display name for the existing rating toggle.
     *
     * The legacy `rating_enabled` column is kept (and mirrored) for backward
     * compatibility with existing rows / specs; new code reads the
     * `star_ratings_enabled` column and writes both.
     */
    public function up(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('rating_enabled');
            $table->boolean('star_ratings_enabled')->default(false)->after('is_active');
        });

        // Mirror any pre-existing rating_enabled values into the new column
        // so dashboards / public forms see the same on/off state.
        DB::statement('UPDATE spaces SET star_ratings_enabled = rating_enabled WHERE rating_enabled IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'star_ratings_enabled']);
        });
    }
};
