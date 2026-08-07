<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simón himself — a singleton row (REBUILD_PLAN.md §2.4).
     *
     * `about_md` is the authored source and `about_html` the rendered, sanitised
     * output; both are translatable, and the renderer keeps them in step through
     * a model observer rather than through anything the admin has to remember.
     */
    public function up(): void
    {
        Schema::create('profile', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->jsonb('name');
            $table->jsonb('about_md')->nullable();
            $table->jsonb('about_html')->nullable();
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->jsonb('meta_title')->nullable();
            $table->jsonb('meta_description')->nullable();
            $table->timestamps();
        });

        // "Singleton" enforced rather than assumed: a second row would make
        // /api/v1/bootstrap non-deterministic about which profile it serves.
        DB::statement(
            'CREATE UNIQUE INDEX profile_singleton ON profile ((true))',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('profile');
    }
};
