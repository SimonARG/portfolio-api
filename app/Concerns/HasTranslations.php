<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Facades\App;
use Spatie\Translatable\HasTranslations as BaseHasTranslations;

/**
 * Spatie's trait, plus the house convention from harmless-pleasure
 * (`app/Traits/HasTranslations.php`): serialising a model resolves its
 * translatable columns to the active locale instead of emitting the raw JSONB
 * object.
 *
 * This is a safety net, not the mechanism. Session 3's API Resources resolve
 * locale explicitly and are what the public endpoints go through; this trait
 * exists so that a model which reaches a JSON response by some other path
 * — a debug dump, a queued job payload, a route returning a model directly —
 * still cannot leak all three languages at once and quietly reintroduce the
 * triplicated-content problem the rebuild is fixing (§1.5 defect 12).
 *
 * The admin side needs the opposite and must ask for it explicitly: Spatie's
 * `getTranslations()` returns every locale, and that is what session 9's
 * side-by-side translation editor reads.
 */
trait HasTranslations
{
    use BaseHasTranslations;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $attributes = $this->attributesToArray();

        foreach ($this->getTranslatableAttributes() as $key) {
            if (array_key_exists($key, $attributes)) {
                // Fallback is on, so a locale with no value resolves through
                // the `requested → es` chain rather than coming back empty.
                $attributes[$key] = $this->getTranslation($key, App::getLocale());
            }
        }

        return array_merge($attributes, $this->relationsToArray());
    }
}
