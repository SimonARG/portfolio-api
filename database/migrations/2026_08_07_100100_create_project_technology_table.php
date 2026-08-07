<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which technologies each project lists, and in what order.
     *
     * The order is per-project and deliberate — the legacy chips run
     * "Laravel, HTML, CSS, JS, VueJS, TailwindCSS, MySQL" for MPI, leading with
     * the framework — so `sort_order` lives on the pivot rather than being
     * inherited from technologies.sort_order.
     */
    public function up(): void
    {
        Schema::create('project_technology', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique(['project_id', 'technology_id']);
            $table->index(['project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_technology');
    }
};
