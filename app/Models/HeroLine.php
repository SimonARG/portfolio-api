<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\HeroLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One of the three role lines on the landing page.
 *
 * Not translatable, deliberately. All three rows render simultaneously, each in
 * its own language and each with a DIFFERENT tech list — "Programador /
 * PHP HTML JS CSS" beside "Programmer / LARAVEL SQL VUE" beside
 * "プログラマー / GIT JAVA PYTHON C++". They are three pieces of content, not
 * three translations of one, and modelling them as translations would collapse
 * the hero into a single line.
 *
 * @property int $id
 * @property string $locale_code
 * @property string $role_label
 * @property string $tech_string
 * @property int $sort_order
 */
#[Fillable(['locale_code', 'role_label', 'tech_string', 'sort_order'])]
class HeroLine extends Model
{
    /** @use HasFactory<HeroLineFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
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
     * @param  Builder<HeroLine>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
