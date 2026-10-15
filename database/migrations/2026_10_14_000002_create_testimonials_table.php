<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();

            $table->string('submitter_name');
            $table->string('submitter_email');
            $table->string('submitter_company')->nullable();
            $table->json('submitter_social_links')->nullable();

            $table->text('testimonial_text');
            $table->unsignedTinyInteger('rating')->nullable();

            $table->string('profile_photo_url', 2048)->nullable();

            $table->json('field_values');

            $table->enum('consent_state', ['granted', 'declined', 'not_asked']);

            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_wall_of_love')->default(false);
            $table->boolean('is_hidden')->default(false);

            $table->timestamp('submitted_at');
            $table->timestamps();

            // Inbox: newest first per space.
            $table->index(['space_id', 'created_at']);

            // Public embed feed: covering index for the hottest read path.
            // The boolean columns are low-cardinality filters; created_at is the sort.
            $table->index(
                ['space_id', 'is_wall_of_love', 'is_hidden', 'created_at'],
                'testimonials_public_feed_idx'
            );

            // Inbox filter indexes.
            $table->index('is_favorite');
            $table->index('is_wall_of_love');

            // Search (Skipped on SQLite as fulltext is unsupported)
            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText('testimonial_text');
            }

            $table->index('submitter_name');
            $table->index('submitter_email');
        });

        // STORED generated column & unique constraint (Skipped on SQLite as generated stored columns are unsupported)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('
                ALTER TABLE testimonials
                ADD COLUMN submitter_email_normalized VARCHAR(255)
                GENERATED ALWAYS AS (LOWER(TRIM(submitter_email))) STORED
            ');

            Schema::table('testimonials', function (Blueprint $table) {
                $table->unique(
                    ['space_id', 'submitter_email_normalized'],
                    'testimonials_space_email_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};