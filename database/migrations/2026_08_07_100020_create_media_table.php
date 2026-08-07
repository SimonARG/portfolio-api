<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every image, video and document, with the metadata the client needs in
     * order to reserve layout space before the asset loads.
     *
     * width/height/duration are recorded rather than derived at request time
     * because they are what keeps CLS under the §2.8 budget of 0.02: the client
     * can size an <img> or <video> box correctly on the server-rendered pass.
     *
     * The rows are written by the session-4 media seeder once the encode
     * pipeline has run; this session only creates the table and the foreign
     * keys that point at it.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            // Stable handle for seeders and fixtures to reference a row without
            // depending on an auto-increment id ("thumb_mpi", "video_blog").
            $table->string('key')->unique();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('mime');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedBigInteger('byte_size');
            // sha256 of the source file: lets the session-4 pipeline skip
            // re-encoding an asset whose bytes have not changed, and gives the
            // admin uploader a cheap duplicate check.
            $table->string('checksum', 64)->nullable();
            $table->jsonb('alt')->nullable();
            // { "avif": {...}, "webm": {...} } — the derived files and their
            // dimensions and byte sizes, written by the session-4 encoder.
            $table->jsonb('renditions')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
