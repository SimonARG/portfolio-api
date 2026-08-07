<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The five tabs of the peek menu.
     *
     * `kind` splits the two behaviours the legacy menu conflated: three tabs open
     * a dialog, two navigate away. In the legacy markup both were <div>s wired to
     * window.open, which is why the rebuild has to know the difference — external
     * items must render as real <a> elements inside the <nav> list.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->jsonb('label');
            // Iconify name (e.g. "simple-icons:github"), resolved server-side by
            // @nuxt/icon so no CDN is touched. Font Awesome is deleted in session 5.
            $table->string('icon')->nullable();
            $table->string('kind', 16)->default('popup');
            // A dialog name for kind=popup, an absolute URL for kind=external.
            $table->string('target')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        DB::statement(
            "ALTER TABLE menu_items ADD CONSTRAINT menu_items_kind_check CHECK (kind IN ('popup', 'external'))",
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
