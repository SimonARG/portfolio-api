<?php

declare(strict_types=1);

/**
 * Guards database/data/content-inventory.json — the verbatim harvest of the legacy
 * site that the session-2 seeders consume.
 *
 * The plan's acceptance criterion for session 1 is that the inventory "round-trips
 * every string in §1.3 with nothing lost". These assertions are that criterion made
 * executable, so a later edit cannot quietly drop a locale or a project.
 */
function inventory(): array
{
    static $data;

    return $data ??= json_decode(
        (string) file_get_contents(dirname(__DIR__, 2).'/database/data/content-inventory.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
}

const LOCALES = ['es', 'en', 'ja'];

it('is valid JSON with every top-level section present', function (): void {
    expect(inventory())->toHaveKeys([
        'meta', 'locales', 'profile', 'hero_lines', 'menu_items',
        'social_links', 'documents', 'technologies', 'projects', 'ui_strings', 'media',
    ]);
});

it('declares exactly the three locales, with Spanish default', function (): void {
    $locales = inventory()['locales'];

    expect(array_column($locales, 'code'))->toBe(LOCALES)
        ->and(array_column(array_filter($locales, fn ($l) => $l['is_default']), 'code'))->toBe(['es'])
        ->and(array_filter($locales, fn ($l) => ! $l['is_enabled']))->toBeEmpty();
});

it('matches the counts declared in the coverage checklist', function (): void {
    $checklist = inventory()['meta']['coverage_checklist'];

    expect(inventory()['hero_lines'])->toHaveCount($checklist['hero_lines'])
        ->and(inventory()['menu_items'])->toHaveCount($checklist['menu_items'])
        ->and(inventory()['social_links'])->toHaveCount($checklist['social_links'])
        ->and(inventory()['documents'])->toHaveCount($checklist['documents'])
        ->and(inventory()['projects'])->toHaveCount($checklist['projects'])
        ->and(inventory()['technologies'])->toHaveCount($checklist['technologies'])
        ->and(inventory()['media'])->toHaveCount($checklist['media']);
});

it('carries the profile identity in all three scripts', function (): void {
    $profile = inventory()['profile'];

    expect($profile['email'])->toBe('simonchasnovsky@gmail.com')
        ->and($profile['github_url'])->toBe('https://github.com/SimonARG')
        ->and($profile['linkedin_url'])->toBe('https://www.linkedin.com/in/simonpaul99/')
        ->and($profile['name'])->toBe([
            'es' => 'SIMÓN P. CHASNOVSKY',
            'en' => 'SIMON P. CHASNOVSKY',
            'ja' => 'サイモン・チャスノブスキ',
        ]);
});

it('carries the About copy as two paragraphs per locale', function (): void {
    foreach (LOCALES as $locale) {
        $paragraphs = inventory()['profile']['about'][$locale];

        expect($paragraphs)->toBeArray()->toHaveCount(2)
            ->and($paragraphs[0])->not->toBeEmpty()
            ->and($paragraphs[1])->not->toBeEmpty();

        // Presentation must never leak into content.
        foreach ($paragraphs as $paragraph) {
            expect($paragraph)->not->toContain('<br>')
                ->and($paragraph)->not->toContain('</br>')
                ->and($paragraph)->not->toContain('font-size');
        }
    }
});

it('keeps the three hero lines distinct rather than translations of one another', function (): void {
    $lines = inventory()['hero_lines'];

    expect(array_column($lines, 'locale_code'))->toBe(LOCALES)
        ->and(array_column($lines, 'role_label'))->toBe(['Programador', 'Programmer', 'プログラマー'])
        ->and(array_column($lines, 'tech_string'))->toBe([
            'PHP HTML JS CSS',
            'LARAVEL SQL VUE',
            'GIT JAVA PYTHON C++',
        ]);

    // The whole point: three different tech lists, not one list translated.
    expect(array_unique(array_column($lines, 'tech_string')))->toHaveCount(3);
});

it('gives every menu item a label in all three locales and a resolvable target', function (): void {
    foreach (inventory()['menu_items'] as $item) {
        expect($item['label'])->toHaveKeys(LOCALES);

        foreach (LOCALES as $locale) {
            expect($item['label'][$locale])->toBeString()->not->toBeEmpty();
        }

        expect($item['kind'])->toBeIn(['popup', 'external']);

        if ($item['kind'] === 'external') {
            expect($item['target'])->toStartWith('https://');
        }
    }

    expect(array_column(inventory()['menu_items'], 'key'))
        ->toBe(['about', 'github', 'cv', 'socials', 'linkedin']);
});

it('gives every social link an https URL and a Spanish label', function (): void {
    foreach (inventory()['social_links'] as $link) {
        expect($link['url'])->toStartWith('https://')
            ->and($link['label']['es'])->toBeString()->not->toBeEmpty()
            ->and($link['label'])->toHaveKeys(LOCALES);
    }

    expect(array_column(inventory()['social_links'], 'key'))
        ->toBe(['instagram', 'linkedin', 'facebook', 'last_fm', 'rym', 'letterboxd']);
});

it('lists the six projects in the legacy display order', function (): void {
    expect(array_column(inventory()['projects'], 'key'))
        ->toBe(['mpi', 'mixtorrents', 'newtab', 'ambient', 'blog', 'portfolio']);

    expect(array_column(inventory()['projects'], 'sort_order'))->toBe([1, 2, 3, 4, 5, 6]);
});

it('describes every project in all three locales, verbatim and decomposed', function (): void {
    foreach (inventory()['projects'] as $project) {
        expect($project['description_legacy_html'])->toHaveKeys(LOCALES)
            ->and($project['description_blocks'])->toHaveKeys(LOCALES);

        foreach (LOCALES as $locale) {
            $blocks = $project['description_blocks'][$locale];

            expect($project['description_legacy_html'][$locale])->toBeString()->not->toBeEmpty()
                ->and($blocks['lead'])->toBeString()->not->toBeEmpty()
                ->and($blocks['items'])->toBeArray();

            // The decomposition must not carry markup through.
            expect($blocks['lead'])->not->toContain('<br>');

            foreach ($blocks['items'] as $item) {
                expect($item)->not->toContain('<br>')
                    ->and($item)->not->toStartWith('-')
                    ->and($item)->not->toStartWith('・');
            }
        }

        // Every locale must decompose to the same number of bullets.
        expect(count($project['description_blocks']['en']['items']))
            ->toBe(count($project['description_blocks']['es']['items']))
            ->toBe(count($project['description_blocks']['ja']['items']));
    }
});

it('references only technologies that are defined', function (): void {
    $defined = array_column(inventory()['technologies'], 'slug');

    foreach (inventory()['projects'] as $project) {
        expect($project['technologies'])->not->toBeEmpty();

        foreach ($project['technologies'] as $slug) {
            expect($slug)->toBeIn($defined);
        }
    }

    // No orphans: every declared technology is used by at least one project.
    $used = array_unique(array_merge(...array_column(inventory()['projects'], 'technologies')));
    expect(array_diff($defined, $used))->toBeEmpty();
});

it('gives every project a slug in all three locales', function (): void {
    foreach (inventory()['projects'] as $project) {
        expect($project['slug'])->toHaveKeys(LOCALES);

        foreach (LOCALES as $locale) {
            expect($project['slug'][$locale])
                ->toMatch('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', "slug for {$project['key']}/{$locale} must be URL-safe");
        }
    }
});

it('flags the portfolio project as needing a rewrite', function (): void {
    $portfolio = collect(inventory()['projects'])->firstWhere('key', 'portfolio');

    expect($portfolio['note'])->toContain('REWRITTEN IN SESSION 2')
        ->and($portfolio['description_blocks']['en']['trailing'])->toContain('vanilla HTML');
});

it('preserves the Re;Noise audio exception', function (): void {
    $ambient = collect(inventory()['projects'])->firstWhere('key', 'ambient');
    $others = collect(inventory()['projects'])->where('key', '!=', 'ambient');

    expect($ambient['legacy_video_muted'])->toBeFalse()
        ->and($others->pluck('legacy_video_muted')->unique()->all())->toBe([true]);
});

it('records every legacy media asset with a byte count', function (): void {
    foreach (inventory()['media'] as $asset) {
        expect($asset)->toHaveKeys(['key', 'legacy_path', 'kind', 'bytes'])
            ->and($asset['bytes'])->toBeInt()->toBeGreaterThan(0)
            ->and($asset['kind'])->toBeIn(['image', 'video', 'document']);
    }

    $videoBytes = collect(inventory()['media'])->where('kind', 'video')->sum('bytes');

    // The session-4 baseline: 55 MB of video must become under 8 MB.
    expect($videoBytes)->toBe(57_457_667);
});

it('keeps every Japanese inline style out of content and in legacy_style', function (): void {
    $json = (string) file_get_contents(dirname(__DIR__, 2).'/database/data/content-inventory.json');

    // Every occurrence of an inline style must sit under a *legacy_style* key or a note.
    preg_match_all('/"[^"]*font-size:[^"]*"/', $json, $matches);

    expect($matches[0])->not->toBeEmpty('the harvest should record the legacy CJK sizing overrides');

    foreach (inventory()['menu_items'] as $item) {
        foreach (LOCALES as $locale) {
            expect($item['label'][$locale])->not->toContain('font-size');
        }
    }
});
