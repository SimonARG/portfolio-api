<?php

declare(strict_types=1);

namespace Database\Factories\Concerns;

/**
 * Shared helper for building the `{"es": …, "en": …, "ja": …}` shape that every
 * translatable column expects.
 */
trait MakesTranslations
{
    /**
     * The locales a factory-built record covers by default.
     *
     * Hard-coded rather than read from the locales table: a factory must work in
     * a test that has not seeded any locales, and coupling every factory to a
     * database read would make the fixtures depend on seeding order.
     *
     * @var array<int, string>
     */
    public const array LOCALES = ['es', 'en', 'ja'];

    /**
     * Build a translation map by calling $make once per locale.
     *
     * @param  callable(string): (string|null)  $make
     * @return array<string, string|null>
     */
    protected function translated(callable $make): array
    {
        $translations = [];

        foreach (self::LOCALES as $locale) {
            $translations[$locale] = $make($locale);
        }

        return $translations;
    }

    /**
     * A translation map whose values are the same text tagged per locale —
     * enough for assertions that care about which locale came back.
     *
     * @return array<string, string>
     */
    protected function translatedText(string $text): array
    {
        /** @var array<string, string> */
        return $this->translated(fn (string $locale): string => "{$text} ({$locale})");
    }
}
