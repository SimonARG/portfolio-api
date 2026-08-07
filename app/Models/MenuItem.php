<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\Attributes\Translatable;

/**
 * A tab in the peek menu.
 *
 * @property int $id
 * @property string $key
 * @property string|null $icon
 * @property string $kind
 * @property string|null $target
 * @property int $sort_order
 * @property bool $is_published
 */
#[Fillable(['key', 'label', 'icon', 'kind', 'target', 'sort_order', 'is_published'])]
#[Translatable(['label'])]
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory, HasTranslations;

    /** Opens a dialog on the current page. */
    public const string KIND_POPUP = 'popup';

    /** Navigates away — must render as a real <a>, not a div with a click handler. */
    public const string KIND_EXTERNAL = 'external';

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

    public function isExternal(): bool
    {
        return $this->kind === self::KIND_EXTERNAL;
    }

    /**
     * @param  Builder<MenuItem>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @param  Builder<MenuItem>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
