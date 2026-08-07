<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The six projects — the site's real content.
     *
     * `title` is a plain column: the titles are proper nouns or stylised names
     * ("MIXTORRENTS", "RE;NOISE", "PDA MPI") that do not translate. `slug` IS
     * translatable so URLs can localise — /proyectos/portafolios against
     * /en/projects/portfolio — which is what makes the per-locale routes in
     * §2.1 addressable rather than cosmetic.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->jsonb('slug');
            $table->string('title');
            $table->jsonb('summary')->nullable();
            $table->jsonb('description_md')->nullable();
            $table->jsonb('description_html')->nullable();
            $table->string('repo_url')->nullable();
            $table->string('live_url')->nullable();

            // Media is seeded in session 4, so every project starts with null
            // references and the FKs null out rather than cascade — losing a
            // thumbnail must not delete the project it belonged to.
            $table->foreignId('thumbnail_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('video_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('poster_media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->jsonb('meta_title')->nullable();
            $table->jsonb('meta_description')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        /*
         * Slug lookup — the hot path behind GET /api/v1/projects/{slug}.
         *
         * A GIN index would NOT serve this query, which is worth stating
         * explicitly because §4.2 asks for GIN indexes and the obvious reading
         * is wrong here. Spatie's whereJsonContainsLocale scope compiles to
         *
         *     where "slug"->>'es' = ?
         *
         * — a text extraction, not a containment operator. Postgres can only
         * use GIN for @> / ? / @@ style operators, so the planner would ignore a
         * GIN index on this column and sequential-scan instead.
         *
         * What actually serves it is a B-tree index on the extracted expression,
         * one per locale. Making each of them UNIQUE is a free bonus: it stops
         * two projects claiming the same URL in the same language, which is a
         * constraint the API layer would otherwise have to enforce by hand.
         *
         * WHERE ... IS NOT NULL keeps the index partial, so a project with no
         * Japanese slug yet does not collide with every other project that also
         * has none.
         */
        foreach (['es', 'en', 'ja'] as $locale) {
            DB::statement(
                "CREATE UNIQUE INDEX projects_slug_{$locale}_unique
                 ON projects ((slug->>'{$locale}'))
                 WHERE slug->>'{$locale}' IS NOT NULL",
            );
        }

        /*
         * GIN, where the query shape genuinely uses it.
         *
         * The session-9 translation editor has to answer "which fields are
         * missing a locale" across the content set. That is a key-existence
         * test — `description_md ? 'ja'` — which is exactly what GIN's default
         * jsonb_ops accelerates, unlike the slug lookup above.
         */
        DB::statement('CREATE INDEX projects_description_md_gin ON projects USING gin (description_md)');
        DB::statement('CREATE INDEX projects_summary_gin ON projects USING gin (summary)');
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
