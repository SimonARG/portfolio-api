<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Setting;
use App\Models\Technology;
use App\Providers\AppServiceProvider;

/**
 * `content_version` is the single lever that invalidates all four cache layers
 * (§2.3). Bumping it too rarely serves stale content; bumping it on every no-op
 * throws away the cache for nothing. Both directions matter.
 */
it('starts from zero when nothing has been written', function (): void {
    expect(Setting::contentVersion())->toBe(0);
});

it('advances when content is created', function (): void {
    Project::factory()->create();

    expect(Setting::contentVersion())->toBe(1);
});

it('advances when content is updated', function (): void {
    $project = Project::factory()->create();
    $before = Setting::contentVersion();

    $project->update(['title' => 'CHANGED']);

    expect(Setting::contentVersion())->toBeGreaterThan($before);
});

it('advances when content is deleted', function (): void {
    $project = Project::factory()->create();
    $before = Setting::contentVersion();

    $project->delete();

    expect(Setting::contentVersion())->toBeGreaterThan($before);
});

it('does not advance on a save that changes nothing', function (): void {
    // Eloquent fires `saved` even when save() short-circuits on a clean model,
    // and its change set is stale at that point. Observing `updated` is what
    // makes this hold.
    $project = Project::factory()->create();
    $before = Setting::contentVersion();

    $project->save();

    expect(Setting::contentVersion())->toBe($before);
});

it('does not advance when a field is written its existing value', function (): void {
    $project = Project::factory()->create(['title' => 'SAME']);
    $before = Setting::contentVersion();

    $project->update(['title' => 'SAME']);

    expect(Setting::contentVersion())->toBe($before);
});

it('advances when a project\'s technologies change', function (): void {
    // Pivot writes fire no model event, so this only holds because
    // syncTechnologies bumps explicitly.
    $project = Project::factory()->create();
    $technologies = Technology::factory()->count(2)->create()->pluck('id')->all();
    $before = Setting::contentVersion();

    $project->syncTechnologies($technologies);

    expect(Setting::contentVersion())->toBeGreaterThan($before);
});

it('does not advance when the same technologies are synced again', function (): void {
    // sync() reports every touched row as updated, and Postgres counts a row as
    // affected even when written back its own values, so its return value
    // cannot be trusted for this.
    $project = Project::factory()->create();
    $technologies = Technology::factory()->count(2)->create()->pluck('id')->all();
    $project->syncTechnologies($technologies);

    $before = Setting::contentVersion();
    $project->syncTechnologies($technologies);

    expect(Setting::contentVersion())->toBe($before);
});

it('advances when technologies are merely reordered', function (): void {
    $project = Project::factory()->create();
    $technologies = Technology::factory()->count(3)->create()->pluck('id')->all();
    $project->syncTechnologies($technologies);

    $before = Setting::contentVersion();
    $project->syncTechnologies(array_reverse($technologies));

    expect(Setting::contentVersion())->toBeGreaterThan($before);
});

it('increments atomically rather than read-modify-write', function (): void {
    Setting::set(Setting::CONTENT_VERSION, 7);

    expect(Setting::bumpContentVersion())->toBe(8)
        ->and(Setting::bumpContentVersion())->toBe(9)
        ->and(Setting::contentVersion())->toBe(9);
});

it('creates the key on first bump if it is absent', function (): void {
    expect(Setting::bumpContentVersion())->toBe(1);
});

it('does not observe itself', function (): void {
    // Setting is deliberately excluded from CONTENT_MODELS; observing it would
    // recurse on every bump.
    expect(AppServiceProvider::CONTENT_MODELS)
        ->not->toContain(Setting::class);
});
