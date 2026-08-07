<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Observers\RendersMarkdown;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\Attributes\Translatable;

/**
 * Simón — the singleton behind /api/v1/bootstrap.
 *
 * @property int $id
 * @property string $email
 * @property string|null $github_url
 * @property string|null $linkedin_url
 */
#[Fillable([
    'email', 'name', 'about_md', 'about_html',
    'github_url', 'linkedin_url', 'meta_title', 'meta_description',
])]
#[Translatable(['name', 'about_md', 'about_html', 'meta_title', 'meta_description'])]
#[ObservedBy(RendersMarkdown::class)]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory, HasTranslations;

    protected $table = 'profile';

    /**
     * Markdown source columns, and the rendered column each one feeds.
     *
     * @var array<string, string>
     */
    public const array MARKDOWN_COLUMNS = ['about_md' => 'about_html'];

    /**
     * The one and only row.
     *
     * A unique index on `((true))` guarantees there is at most one, so this
     * cannot silently pick between candidates.
     */
    public static function singleton(): ?self
    {
        return static::query()->first();
    }
}
