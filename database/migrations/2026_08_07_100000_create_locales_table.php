<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The set of languages the site publishes in.
     *
     * Adding a language is a data change rather than a schema change — that is
     * the whole point of the JSONB translation model (REBUILD_PLAN.md §2.1) —
     * so this table is what the admin's translation editor reads to know which
     * locales a translatable field must cover.
     */
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table) {
            // The BCP 47 code is the natural key and is what every other table
            // and every URL prefix refers to, so it is the primary key.
            $table->string('code', 12)->primary();
            $table->string('name');
            $table->string('native_name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_enabled', 'sort_order']);
        });

        // Exactly one default locale, enforced by the database rather than by
        // convention. A partial unique index is the standard Postgres idiom for
        // "at most one row where this flag is true".
        DB::statement(
            'CREATE UNIQUE INDEX locales_single_default ON locales ((true)) WHERE is_default',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
