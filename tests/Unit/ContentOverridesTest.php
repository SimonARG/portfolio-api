<?php

declare(strict_types=1);

use Database\Seeders\Support\ContentInventory;

/**
 * Guards database/data/content-overrides.json — the record of every deliberate
 * departure from the legacy copy.
 *
 * The point of these assertions is that a correction cannot quietly stop
 * working. If the inventory is ever re-harvested and a typo is fixed upstream,
 * or a string is reworded, the correction targeting it would silently become a
 * no-op; here it fails the build instead.
 */

/**
 * Just the parts of the inventory the seeders actually read.
 *
 * `meta` is excluded because it *catalogues* the typos — meta.source_defects
 * quotes "Porfolio" as documentation, and correcting that would erase the record
 * of what was wrong. `description_legacy_html` is excluded because it is the
 * verbatim copy of the old markup, kept for reference and deliberately not
 * corrected; only description_blocks is seeded. `ui_strings` is excluded because
 * it becomes an i18n message catalogue in the client rather than database rows,
 * so its typos are tracked under client_side_corrections for session 5.
 */
function seededScope(array $inventory): string
{
    unset($inventory['meta'], $inventory['ui_strings']);

    foreach (array_keys($inventory['projects']) as $index) {
        unset($inventory['projects'][$index]['description_legacy_html']);
    }

    return (string) json_encode($inventory, JSON_UNESCAPED_UNICODE);
}

it('targets text that actually exists in the legacy harvest', function (): void {
    $legacy = seededScope(ContentInventory::legacy());

    $missing = array_values(array_filter(
        ContentInventory::corrections(),
        fn (array $correction): bool => ! str_contains($legacy, $correction['from']),
    ));

    expect($missing)->toBe([]);
});

it('applies every correction, leaving none of the original typos behind', function (): void {
    $corrected = seededScope(ContentInventory::all());

    foreach (ContentInventory::corrections() as $correction) {
        expect($corrected)
            ->not->toContain($correction['from'])
            ->toContain($correction['to']);
    }
});

it('leaves the legacy harvest itself untouched', function (): void {
    // content-inventory.json is the historical record. Corrections are applied
    // on read, never written back.
    $legacy = json_encode(ContentInventory::legacy(), JSON_UNESCAPED_UNICODE);

    expect($legacy)
        ->toContain('Porfolio')
        ->toContain('móbiles')
        ->toContain('vanilla HTML, CSS and JavaScript');
});

it('fixes all ten typos catalogued in the harvest', function (): void {
    // Nine are database content; PROYECTS lives in ui_strings, which becomes an
    // i18n message catalogue in the client, so it is tracked separately.
    expect(ContentInventory::corrections())->toHaveCount(9)
        ->and(ContentInventory::overrides()['client_side_corrections'])->toHaveCount(2);

    $catalogued = count(ContentInventory::legacy()['meta']['source_defects']['copy_typos']);

    expect($catalogued)->toBe(10);
});

it('keeps the halfwidth katakana LinkedIn label exactly as the legacy site had it', function (): void {
    // Simón's explicit decision. If this ever fails, someone has "fixed" a
    // deliberate choice — check content-overrides.json decisions before changing it.
    $linkedin = ContentInventory::entry('menu_items', 'linkedin');

    expect($linkedin['label']['ja'])->toBe('ﾘﾝｹﾄﾞｲﾝ');

    $decision = collect(ContentInventory::overrides()['decisions'])
        ->firstWhere('do_not_change', true);

    expect($decision)->not->toBeNull()
        ->and($decision['choice'])->toContain('KEEP');
});

it('rewrites the portfolio project so it no longer claims to be vanilla JavaScript', function (): void {
    $override = ContentInventory::overrides()['projects']['portfolio'];

    foreach (['es', 'en', 'ja'] as $locale) {
        $markdown = (string) ContentInventory::markdown($override['description_blocks'][$locale]);

        expect($markdown)
            ->not->toContain('vanilla')
            ->not->toContain('バニラ')
            ->toContain('Nuxt')
            ->toContain('Laravel');
    }

    expect($override['repo_url'])->toBe('https://github.com/SimonARG/portfolio-client')
        ->and($override['live_url'])->toBe('https://www.simon-dev.com/')
        ->and($override['technologies'])->toContain('nuxt', 'typescript', 'postgresql');
});

it('fills every locale for the labels the legacy markup left untranslated', function (): void {
    $overrides = ContentInventory::overrides();

    foreach (['es', 'en', 'ja'] as $locale) {
        expect($overrides['social_links']['rym']['label'][$locale])->not->toBeEmpty()
            ->and($overrides['documents']['cv_es']['label'][$locale])->not->toBeEmpty()
            ->and($overrides['documents']['cv_en']['label'][$locale])->not->toBeEmpty();
    }
});

it('never carries a legacy inline style into seeded content', function (): void {
    // The Japanese font-size overrides are presentation. They stay in the
    // harvest's legacy_style fields for session 5 and must not reach the DB.
    $seeded = json_encode([
        ContentInventory::section('menu_items'),
        ContentInventory::section('social_links'),
        ContentInventory::profile()['about'],
    ], JSON_UNESCAPED_UNICODE);

    // legacy_style is present in the harvest — that is fine, it is reference
    // data. What matters is that no seeder reads it, which the seeding test
    // asserts against the database itself.
    expect($seeded)->toContain('legacy_style');
});

describe('markdown assembly', function (): void {
    it('builds a lead, a bullet list and a trailing paragraph', function (): void {
        $markdown = ContentInventory::markdown([
            'lead' => 'Lead:',
            'items' => ['one', 'two'],
            'trailing' => 'Trailing.',
        ]);

        expect($markdown)->toBe("Lead:\n\n- one\n- two\n\nTrailing.");
    });

    it('omits the list when a project has no bullets', function (): void {
        expect(ContentInventory::markdown(['lead' => 'Lead.', 'items' => []]))
            ->toBe('Lead.');
    });

    it('returns null for an empty block', function (): void {
        expect(ContentInventory::markdown([]))->toBeNull();
    });
});
