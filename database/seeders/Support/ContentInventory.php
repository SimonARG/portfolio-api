<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use JsonException;
use RuntimeException;

/**
 * Reads `content-inventory.json` — the verbatim harvest of the legacy site —
 * and applies `content-overrides.json` on top of it.
 *
 * The two files are kept separate on purpose. The inventory is the historical
 * record of what the old site actually said, and stays untouched so it can
 * still be diffed against; the overrides file is the complete, reviewable list
 * of what the rebuild deliberately says instead. Everything the seeders write
 * comes from this class, so there is exactly one place where "legacy copy" turns
 * into "shipped copy".
 */
final class ContentInventory
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /** @var array<string, mixed>|null */
    private static ?array $overrides = null;

    /**
     * The corrected inventory: legacy content with the agreed fixes applied.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $inventory = self::read('content-inventory.json');

        foreach (self::corrections() as $correction) {
            $inventory = self::applyCorrection($inventory, $correction);
        }

        return self::$cache = $inventory;
    }

    /**
     * The raw, uncorrected harvest — what the legacy site said, verbatim.
     *
     * Used by the tests that assert a correction actually changed something.
     *
     * @return array<string, mixed>
     */
    public static function legacy(): array
    {
        return self::read('content-inventory.json');
    }

    /**
     * @return array<string, mixed>
     */
    public static function overrides(): array
    {
        return self::$overrides ??= self::read('content-overrides.json');
    }

    /**
     * @return array<int, array{where: string, from: string, to: string}>
     */
    public static function corrections(): array
    {
        /** @var array<int, array{where: string, from: string, to: string}> */
        return self::overrides()['corrections'] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function section(string $key): array
    {
        /** @var array<int, array<string, mixed>> */
        return self::all()[$key] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function profile(): array
    {
        /** @var array<string, mixed> */
        return self::all()['profile'] ?? [];
    }

    /**
     * One entry of a keyed section, by its `key` field.
     *
     * @return array<string, mixed>|null
     */
    public static function entry(string $section, string $key): ?array
    {
        foreach (self::section($section) as $entry) {
            if (($entry['key'] ?? null) === $key) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Render a {lead, items, trailing} block as Markdown.
     *
     * The legacy descriptions were <br>-separated pseudo-lists: a lead sentence,
     * then a run of lines each prefixed with a hyphen (or a katakana middle dot
     * in Japanese) standing in for a bullet. Session 1's harvest already split
     * them into their parts, so this is a mechanical assembly rather than a
     * parse — the bullets become a real Markdown list, which is what makes them
     * a real <ul> in the rendered HTML.
     *
     * @param  array<string, mixed>  $block
     */
    public static function markdown(array $block): ?string
    {
        $parts = [];

        $lead = trim((string) ($block['lead'] ?? ''));

        if ($lead !== '') {
            $parts[] = $lead;
        }

        /** @var array<int, string> $items */
        $items = $block['items'] ?? [];

        if ($items !== []) {
            $parts[] = implode("\n", array_map(
                static fn (string $item): string => '- '.trim($item),
                $items,
            ));
        }

        $trailing = trim((string) ($block['trailing'] ?? ''));

        if ($trailing !== '') {
            $parts[] = $trailing;
        }

        return $parts === [] ? null : implode("\n\n", $parts);
    }

    /**
     * Build a translation map from a per-locale set of description blocks.
     *
     * @param  array<string, array<string, mixed>>  $blocks
     * @return array<string, string>
     */
    public static function markdownTranslations(array $blocks): array
    {
        $translations = [];

        foreach ($blocks as $locale => $block) {
            $markdown = self::markdown($block);

            if ($markdown !== null) {
                $translations[$locale] = $markdown;
            }
        }

        return $translations;
    }

    /**
     * Drop locales whose value is null.
     *
     * The harvest records a missing legacy variant as null. Storing that null
     * would make Spatie treat the locale as present-but-empty, which defeats the
     * `requested → es` fallback; omitting the key is what lets it fall through.
     *
     * @param  array<string, string|null>  $translations
     * @return array<string, string>
     */
    public static function present(array $translations): array
    {
        return array_filter(
            $translations,
            static fn (?string $value): bool => $value !== null && $value !== '',
        );
    }

    /**
     * Reset the memoised copies. Tests that mutate the files use this.
     */
    public static function flush(): void
    {
        self::$cache = null;
        self::$overrides = null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function read(string $file): array
    {
        $path = database_path('data/'.$file);

        if (! is_file($path)) {
            throw new RuntimeException("Content data file is missing: {$path}");
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode(
                (string) file_get_contents($path),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            throw new RuntimeException("Content data file is not valid JSON: {$path}", previous: $e);
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $inventory
     * @param  array{where: string, from: string, to: string}  $correction
     * @return array<string, mixed>
     */
    private static function applyCorrection(array $inventory, array $correction): array
    {
        $segments = explode('.', $correction['where']);

        /** @var array<string, mixed> */
        return self::replaceAt($inventory, $segments, $correction);
    }

    /**
     * Walk `$segments` into `$node`, then replace `from` with `to` in every
     * string at or below the destination.
     *
     * A list of objects is indexed by its `key` field, so "projects.ambient"
     * selects the entry whose key is 'ambient' rather than offset 'ambient'.
     *
     * @param  array<int|string, mixed>  $node
     * @param  array<int, string>  $segments
     * @param  array{where: string, from: string, to: string}  $correction
     * @return array<int|string, mixed>
     */
    private static function replaceAt(array $node, array $segments, array $correction): array
    {
        if ($segments === []) {
            return self::replaceDeep($node, $correction['from'], $correction['to']);
        }

        $segment = array_shift($segments);
        $index = self::locate($node, $segment);

        if ($index === null) {
            throw new RuntimeException(
                "Correction target '{$correction['where']}' does not exist in the content inventory.",
            );
        }

        $child = $node[$index];

        if (is_array($child)) {
            $node[$index] = self::replaceAt($child, $segments, $correction);
        } elseif (is_string($child) && $segments === []) {
            $node[$index] = str_replace($correction['from'], $correction['to'], $child);
        }

        return $node;
    }

    /**
     * @param  array<int|string, mixed>  $node
     */
    private static function locate(array $node, string $segment): int|string|null
    {
        if (array_key_exists($segment, $node)) {
            return $segment;
        }

        foreach ($node as $index => $child) {
            if (is_array($child) && ($child['key'] ?? null) === $segment) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int|string, mixed>  $node
     * @return array<int|string, mixed>
     */
    private static function replaceDeep(array $node, string $from, string $to): array
    {
        foreach ($node as $index => $value) {
            if (is_string($value)) {
                $node[$index] = str_replace($from, $to, $value);
            } elseif (is_array($value)) {
                $node[$index] = self::replaceDeep($value, $from, $to);
            }
        }

        return $node;
    }
}
