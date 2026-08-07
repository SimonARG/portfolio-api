<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Downloadable documents — today the two CVs.
     *
     * `locale_code` is the language the DOCUMENT is written in, not the UI
     * language it appears under: there is no Japanese CV, so the dialog offers
     * the same two entries in all three locales. `label` is separately
     * translatable so a Japanese visitor sees スペイン語 / 英語 naming those two
     * files, rather than the Spanish endonyms.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->jsonb('label');
            $table->string('locale_code', 12);
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->foreign('locale_code')
                ->references('code')
                ->on('locales')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
