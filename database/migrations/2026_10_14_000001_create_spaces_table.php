<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('collection_question');

            $table->enum('theme', ['minimal_light', 'modern_dark', 'warm_friendly'])
                ->default('minimal_light');
            $table->boolean('rating_enabled')->default(false);

            $table->json('fields_config');

            $table->string('public_slug')->unique();
            $table->string('public_token', 64)->unique();

            $table->timestamps();

            // Explicit index for owner listing (foreignId already indexes user_id,
            // but we keep this for clarity in the data-model spec).
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
