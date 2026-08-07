<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The three role lines on the landing page.
     *
     * These are emphatically NOT translations of one another, which is why
     * `role_label` and `tech_string` are plain columns and not JSONB: all three
     * rows are visible simultaneously, each in its own language, and each is
     * paired with a DIFFERENT tech list ("PHP HTML JS CSS" / "LARAVEL SQL VUE" /
     * "GIT JAVA PYTHON C++"). Modelling them as one translatable row would
     * destroy exactly the thing that makes the hero work.
     *
     * `locale_code` therefore says which language this line is written in, and
     * which language its pill switches the site to — it is not a translation key.
     */
    public function up(): void
    {
        Schema::create('hero_lines', function (Blueprint $table) {
            $table->id();
            $table->string('locale_code', 12);
            $table->string('role_label');
            $table->string('tech_string');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('locale_code')
                ->references('code')
                ->on('locales')
                ->cascadeOnUpdate()
                // Deleting a locale that still has a hero line should fail
                // loudly: it would silently remove a third of the landing page.
                ->restrictOnDelete();

            $table->unique('locale_code');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_lines');
    }
};
