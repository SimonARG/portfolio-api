<?php

declare(strict_types=1);

use App\Models\Locale;
use App\Models\Project;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\App;

/**
 * The `requested → es` fallback chain from §2.6, and the guarantee that a
 * response never carries all three languages at once — the actual fix for
 * §1.5 defect 12, where crawlers saw triplicated content on one URL.
 */
it('returns the requested locale when it exists', function (): void {
    $project = Project::factory()->create([
        'summary' => ['es' => 'Español', 'en' => 'English', 'ja' => '日本語'],
    ]);

    expect($project->getTranslation('summary', 'ja'))->toBe('日本語')
        ->and($project->getTranslation('summary', 'en'))->toBe('English');
});

it('falls back to Spanish when the requested locale is missing', function (): void {
    $project = Project::factory()->create(['summary' => ['es' => 'Sólo español']]);

    expect($project->getTranslation('summary', 'ja'))->toBe('Sólo español')
        ->and($project->getTranslation('summary', 'en'))->toBe('Sólo español');
});

it('does not invent a value when the fallback is switched off', function (): void {
    $project = Project::factory()->create(['summary' => ['es' => 'Sólo español']]);

    // Spatie returns an empty string, not null, for an absent translation.
    // Session 3's API Resources have to treat that as "missing" rather than
    // emitting it, or the client would render a blank where the fallback should
    // have gone.
    expect($project->getTranslationWithoutFallback('summary', 'ja'))->toBe('');
});

it('reports which locales a field actually covers', function (): void {
    // This is what session 9's translation editor reads to show what is missing.
    $project = Project::factory()->create([
        'summary' => ['es' => 'Español', 'en' => 'English'],
    ]);

    expect($project->getTranslatedLocales('summary'))->toBe(['es', 'en'])
        ->and($project->hasTranslation('summary', 'ja'))->toBeFalse();
});

it('serialises to the active locale, never to the raw JSONB blob', function (): void {
    $project = Project::factory()->create([
        'summary' => ['es' => 'Español', 'en' => 'English', 'ja' => '日本語'],
    ]);

    App::setLocale('en');

    expect($project->toArray()['summary'])->toBe('English');

    App::setLocale('ja');

    expect($project->fresh()->toArray()['summary'])->toBe('日本語');
});

it('serialises through the fallback when the active locale is missing', function (): void {
    $project = Project::factory()->create(['summary' => ['es' => 'Sólo español']]);

    App::setLocale('ja');

    expect($project->toArray()['summary'])->toBe('Sólo español');
});

it('still exposes every locale to the admin path', function (): void {
    $project = Project::factory()->create([
        'summary' => ['es' => 'Español', 'en' => 'English', 'ja' => '日本語'],
    ]);

    expect($project->getTranslations('summary'))
        ->toBe(['es' => 'Español', 'en' => 'English', 'ja' => '日本語']);
});

it('lists the enabled locales in display order', function (): void {
    Locale::factory()->create(['code' => 'es', 'sort_order' => 1, 'is_default' => true]);
    Locale::factory()->create(['code' => 'ja', 'sort_order' => 3]);
    Locale::factory()->create(['code' => 'en', 'sort_order' => 2]);
    Locale::factory()->disabled()->create(['code' => 'pt', 'sort_order' => 4]);

    expect(Locale::enabledCodes())->toBe(['es', 'en', 'ja']);
});

it('allows only one default locale', function (): void {
    Locale::factory()->default()->create(['code' => 'es']);

    expect(fn () => Locale::factory()->default()->create(['code' => 'en']))
        ->toThrow(QueryException::class);
});
