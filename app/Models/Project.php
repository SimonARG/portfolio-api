<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Observers\RendersMarkdown;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\Attributes\Translatable;

/**
 * A project.
 *
 * @property int $id
 * @property string $key
 * @property string $title
 * @property string|null $repo_url
 * @property string|null $live_url
 * @property int|null $thumbnail_media_id
 * @property int|null $video_media_id
 * @property int|null $poster_media_id
 * @property int $sort_order
 * @property bool $is_published
 * @property Carbon|null $published_at
 */
#[Fillable([
    'key', 'slug', 'title', 'summary', 'description_md', 'description_html',
    'repo_url', 'live_url', 'thumbnail_media_id', 'video_media_id',
    'poster_media_id', 'sort_order', 'is_published', 'published_at',
    'meta_title', 'meta_description',
])]
#[Translatable(['slug', 'summary', 'description_md', 'description_html', 'meta_title', 'meta_description'])]
#[ObservedBy(RendersMarkdown::class)]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasTranslations;

    /**
     * Markdown source columns, and the rendered column each one feeds.
     *
     * @var array<string, string>
     */
    public const array MARKDOWN_COLUMNS = ['description_md' => 'description_html'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Technology, $this>
     */
    public function technologies(): BelongsToMany
    {
        // Chip order is per-project and deliberate (MPI leads with Laravel),
        // so it comes off the pivot, not off technologies.sort_order.
        return $this->belongsToMany(Technology::class, 'project_technology')
            ->withPivot('sort_order')
            ->orderBy('project_technology.sort_order');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'thumbnail_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'video_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'poster_media_id');
    }

    /**
     * Visible to the public right now.
     *
     * `is_published` is the gate and `published_at` is an optional embargo, so a
     * row can be marked published ahead of time and stay hidden until its date
     * arrives. A null `published_at` means "no embargo" — which is what the
     * migrated content carries, since the legacy site recorded no dates and
     * inventing them would be fabrication.
     *
     * @param  Builder<Project>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @param  Builder<Project>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Match a slug in one specific locale.
     *
     * Compiles to `slug->>'es' = ?`, which the partial unique B-tree index on
     * that expression serves directly.
     *
     * @param  Builder<Project>  $query
     */
    #[Scope]
    protected function whereSlug(Builder $query, string $slug, string $locale): void
    {
        $query->where("slug->{$locale}", $slug);
    }

    /**
     * Match a slug in any enabled locale, preferring the requested one.
     *
     * Session 3 decides the policy this enables: a visitor arriving at the
     * English URL for a Spanish slug can be resolved and 301'd to the canonical
     * per-locale URL rather than 404'd, which matters because the legacy site
     * had one URL per page and inbound links will not know the new shape.
     *
     * @param  Builder<Project>  $query
     * @param  array<int, string>  $locales
     */
    #[Scope]
    protected function whereSlugInAny(Builder $query, string $slug, array $locales): void
    {
        $query->where(function (Builder $query) use ($slug, $locales): void {
            foreach ($locales as $locale) {
                $query->orWhere("slug->{$locale}", $slug);
            }
        });
    }

    public function hasLinks(): bool
    {
        return $this->repo_url !== null || $this->live_url !== null;
    }

    /**
     * Replace this project's technologies, preserving the given order.
     *
     * Exists because pivot writes fire no model event on Project, so the
     * content-version observer never sees them. Changing a project's chips is a
     * content change like any other and has to invalidate the caches; going
     * through this method rather than through technologies()->sync() directly
     * is what guarantees it does.
     *
     * @param  array<int, int>  $technologyIds  in display order
     */
    public function syncTechnologies(array $technologyIds): void
    {
        $wanted = array_map('intval', array_values($technologyIds));

        // Compared before the write, and by ordered list rather than by set,
        // because reordering the chips is a content change too.
        //
        // sync()'s own return value cannot answer this: it reports a row as
        // `updated` whenever the UPDATE touched it, and Postgres counts a row as
        // affected even when every column is written back its existing value.
        // Re-syncing an unchanged list would therefore invalidate every cache
        // layer on every admin save.
        $current = array_map('intval', $this->technologies()->pluck('technologies.id')->all());

        $payload = [];

        foreach ($wanted as $index => $id) {
            $payload[$id] = ['sort_order' => $index + 1];
        }

        $this->technologies()->sync($payload);

        if ($current !== $wanted) {
            Setting::bumpContentVersion();
        }
    }
}
