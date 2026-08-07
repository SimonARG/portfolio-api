<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\Attributes\Translatable;

/**
 * A downloadable document — today, the two CVs.
 *
 * `locale_code` is the language the document itself is written in, which is not
 * the same axis as the UI language: there is no Japanese CV, so all three
 * locales offer the same two files.
 *
 * @property int $id
 * @property string $key
 * @property string $locale_code
 * @property int|null $media_id
 * @property int $sort_order
 * @property bool $is_published
 */
#[Fillable(['key', 'label', 'locale_code', 'media_id', 'sort_order', 'is_published'])]
#[Translatable(['label'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasTranslations;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Locale, $this>
     */
    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale_code', 'code');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @param  Builder<Document>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @param  Builder<Document>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
