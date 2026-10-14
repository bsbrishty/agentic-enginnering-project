<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embed_configs', function (Blueprint $table) {
            $table->id();

            // UUID v4 generated in the Eloquent model `creating` hook.
            // This is the only public-facing identifier; the auto-increment `id`
            // is never exposed in the embed snippet URL.
            $table->uuid('public_id')->unique();

            $table->foreignId('space_id')->constrained()->cascadeOnDelete();

            $table->enum('layout', ['masonry', 'carousel']);
            $table->enum('theme_mode', ['light', 'dark']);
            $table->string('background_color', 7); // #RRGGBB
            $table->boolean('animation_enabled')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embed_configs');
    }
};
