<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Small key/value store for things that are configuration rather than
     * content. `content_version` lives here: every admin write bumps it, and it
     * forms part of every Laravel cache key and every Nitro ISR key, so one
     * write produces one coherent flush across all four cache layers
     * (REBUILD_PLAN.md §2.3).
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            // jsonb, not text: values are typed (integers, flags, structures)
            // and reading one should not mean parsing a string first.
            $table->jsonb('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
