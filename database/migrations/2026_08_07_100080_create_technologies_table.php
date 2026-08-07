<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The tech chips.
     *
     * Untranslated by design (REBUILD_PLAN.md §2.4): "Laravel" and "PostgreSQL"
     * are proper nouns, identical in all three locales, so a JSONB column here
     * would store the same string three times and give the admin three fields
     * to keep in step for no gain.
     */
    public function up(): void
    {
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technologies');
    }
};
