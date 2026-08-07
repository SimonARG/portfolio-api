<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\Attributes\Translatable;

/**
 * An image, video or document, with the metadata needed to reserve its layout
 * box before it loads.
 *
 * Rows are written by the session-4 media seeder once the encode pipeline has
 * produced the renditions; this session only defines the model.
 *
 * @property int $id
 * @property string $key
 * @property string $disk
 * @property string $path
 * @property string $mime
 * @property int|null $width
 * @property int|null $height
 * @property int|null $duration_ms
 * @property int $byte_size
 * @property string|null $checksum
 * @property array<string, mixed>|null $renditions
 */
#[Fillable([
    'key', 'disk', 'path', 'mime', 'width', 'height',
    'duration_ms', 'byte_size', 'checksum', 'alt', 'renditions',
])]
#[Translatable(['alt'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, HasTranslations;

    protected $table = 'media';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'duration_ms' => 'integer',
            'byte_size' => 'integer',
            'renditions' => 'array',
        ];
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime, 'video/');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }
}
