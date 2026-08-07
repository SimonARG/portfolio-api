<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LocaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A language the site publishes in.
 *
 * @property string $code
 * @property string $name
 * @property string $native_name
 * @property bool $is_default
 * @property bool $is_enabled
 * @property int $sort_order
 */
#[Fillable(['code', 'name', 'native_name', 'is_default', 'is_enabled', 'sort_order'])]
#[WithoutIncrementing]
#[RouteKey('code')]
class Locale extends Model
{
    /** @use HasFactory<LocaleFactory> */
    use HasFactory;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Locale>  $query
     */
    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }

    /**
     * @param  Builder<Locale>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('code');
    }

    /**
     * The codes of every enabled locale, in display order.
     *
     * This is the list a translatable field is expected to cover, so it is what
     * session 3's Form Requests validate a translation payload against and what
     * session 9's editor renders a column for.
     *
     * @return array<int, string>
     */
    public static function enabledCodes(): array
    {
        return static::query()->enabled()->ordered()->pluck('code')->all();
    }
}
