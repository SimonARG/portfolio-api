<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\HeroLine;
use App\Models\Locale;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SocialLink;
use App\Models\Technology;
use Illuminate\Database\QueryException;

describe('publish scopes', function (): void {
    it('excludes unpublished projects', function (): void {
        Project::factory()->create();
        Project::factory()->unpublished()->create();

        expect(Project::query()->published()->count())->toBe(1);
    });

    it('excludes a published project whose embargo date has not arrived', function (): void {
        Project::factory()->create();
        Project::factory()->embargoed()->create();

        expect(Project::query()->published()->count())->toBe(1);
    });

    it('includes a project whose embargo date has passed', function (): void {
        Project::factory()->create(['published_at' => now()->subDay()]);

        expect(Project::query()->published()->count())->toBe(1);
    });

    it('treats a null publish date as no embargo', function (): void {
        Project::factory()->create(['published_at' => null]);

        expect(Project::query()->published()->count())->toBe(1);
    });

    it('filters menu items, social links and documents too', function (): void {
        MenuItem::factory()->create();
        MenuItem::factory()->unpublished()->create();
        SocialLink::factory()->create();
        SocialLink::factory()->unpublished()->create();
        Document::factory()->create();
        Document::factory()->unpublished()->create();

        expect(MenuItem::query()->published()->count())->toBe(1)
            ->and(SocialLink::query()->published()->count())->toBe(1)
            ->and(Document::query()->published()->count())->toBe(1);
    });
});

describe('ordering', function (): void {
    it('orders projects by sort order', function (): void {
        Project::factory()->create(['key' => 'third', 'sort_order' => 3]);
        Project::factory()->create(['key' => 'first', 'sort_order' => 1]);
        Project::factory()->create(['key' => 'second', 'sort_order' => 2]);

        expect(Project::query()->ordered()->pluck('key')->all())
            ->toBe(['first', 'second', 'third']);
    });

    it('orders technologies by their own sort order', function (): void {
        Technology::factory()->create(['slug' => 'b', 'sort_order' => 2]);
        Technology::factory()->create(['slug' => 'a', 'sort_order' => 1]);

        expect(Technology::query()->ordered()->pluck('slug')->all())->toBe(['a', 'b']);
    });

    it('orders a project\'s technologies by the pivot, not by the technology', function (): void {
        // Chip order is per-project and deliberate: MPI leads with Laravel even
        // though HTML sorts earlier globally.
        $project = Project::factory()->create();

        $first = Technology::factory()->create(['slug' => 'laravel', 'sort_order' => 90]);
        $second = Technology::factory()->create(['slug' => 'html', 'sort_order' => 1]);

        $project->syncTechnologies([$first->id, $second->id]);

        expect($project->technologies()->pluck('slug')->all())->toBe(['laravel', 'html']);
    });
});

describe('slug lookup', function (): void {
    it('finds a project by its slug in a given locale', function (): void {
        Project::factory()->create([
            'key' => 'portfolio',
            'slug' => ['es' => 'portafolios', 'en' => 'portfolio'],
        ]);

        expect(Project::query()->whereSlug('portafolios', 'es')->first()?->key)->toBe('portfolio')
            ->and(Project::query()->whereSlug('portfolio', 'en')->first()?->key)->toBe('portfolio');
    });

    it('does not match a slug from a different locale', function (): void {
        Project::factory()->create(['slug' => ['es' => 'portafolios', 'en' => 'portfolio']]);

        expect(Project::query()->whereSlug('portafolios', 'en')->exists())->toBeFalse();
    });

    it('can match across locales so a cross-locale URL can be redirected', function (): void {
        Project::factory()->create([
            'key' => 'portfolio',
            'slug' => ['es' => 'portafolios', 'en' => 'portfolio'],
        ]);

        expect(Project::query()->whereSlugInAny('portafolios', ['en', 'es'])->first()?->key)
            ->toBe('portfolio');
    });

    it('refuses two projects claiming the same slug in the same locale', function (): void {
        Project::factory()->create(['slug' => ['es' => 'duplicado']]);

        expect(fn () => Project::factory()->create(['slug' => ['es' => 'duplicado']]))
            ->toThrow(QueryException::class);
    });

    it('allows the same slug text in different locales', function (): void {
        Project::factory()->create(['key' => 'a', 'slug' => ['es' => 'mismo']]);
        Project::factory()->create(['key' => 'b', 'slug' => ['en' => 'mismo']]);

        expect(Project::query()->count())->toBe(2);
    });

    it('allows many projects to share the absence of a locale', function (): void {
        // The unique indexes are partial, so "no Japanese slug yet" is not a
        // value two projects can collide on.
        Project::factory()->create(['key' => 'a', 'slug' => ['es' => 'uno']]);
        Project::factory()->create(['key' => 'b', 'slug' => ['es' => 'dos']]);

        expect(Project::query()->count())->toBe(2);
    });
});

describe('markdown rendering on save', function (): void {
    it('renders each locale of a project description', function (): void {
        $project = Project::factory()->create([
            'description_md' => [
                'es' => "Permite:\n\n- uno\n- dos",
                'en' => "It allows:\n\n- one\n- two",
            ],
        ]);

        expect($project->getTranslation('description_html', 'es'))
            ->toContain('<li>uno</li>')
            ->and($project->getTranslation('description_html', 'en'))
            ->toContain('<li>one</li>')
            ->and($project->getTranslatedLocales('description_html'))->toBe(['es', 'en']);
    });

    it('renders the profile about copy as separate paragraphs', function (): void {
        $profile = Profile::factory()->create([
            'about_md' => ['es' => "Primero.\n\nSegundo."],
        ]);

        expect(substr_count((string) $profile->getTranslation('about_html', 'es'), '<p>'))->toBe(2);
    });

    it('sanitises rendered output', function (): void {
        $project = Project::factory()->create([
            'description_md' => ['es' => "Hola\n\n<script>alert(1)</script>"],
        ]);

        expect($project->getTranslation('description_html', 'es'))
            ->not->toContain('<script')
            ->toContain('<p>Hola</p>');
    });

    it('re-renders when the markdown changes', function (): void {
        $project = Project::factory()->create(['description_md' => ['es' => 'Antes.']]);

        $project->update(['description_md' => ['es' => 'Después.']]);

        expect($project->fresh()?->getTranslation('description_html', 'es'))
            ->toContain('Después.');
    });

    it('merges rather than replaces when a translation map is assigned', function (): void {
        // Worth pinning down, because it is the opposite of what assigning an
        // array usually means: Spatie applies the map locale by locale, so a
        // locale absent from the new value survives from the old one.
        $project = Project::factory()->create([
            'description_md' => ['es' => 'Español.', 'en' => 'English.'],
        ]);

        $project->update(['description_md' => ['es' => 'Cambiado.']]);

        expect($project->fresh()?->getTranslatedLocales('description_md'))
            ->toEqualCanonicalizing(['es', 'en']);
    });

    it('drops a rendered locale when its source locale is genuinely removed', function (): void {
        $project = Project::factory()->create([
            'description_md' => ['es' => 'Español.', 'en' => 'English.'],
        ]);

        // replaceTranslations is the API that actually removes a locale, and is
        // what session 9's editor must use when a translation is deleted.
        $project->replaceTranslations('description_md', ['es' => 'Español.']);
        $project->save();

        expect($project->fresh()?->getTranslatedLocales('description_html'))->toBe(['es']);
    });

    it('nulls the html column when all markdown is cleared', function (): void {
        $project = Project::factory()->create(['description_md' => ['es' => 'Algo.']]);

        $project->update(['description_md' => []]);

        // Not {"es": null} — the column itself must be null, or the API would
        // serve an empty translation instead of falling back.
        expect($project->fresh()?->getRawOriginal('description_html'))->toBeNull();
    });
});

describe('relationships', function (): void {
    it('links hero lines and documents to their locale', function (): void {
        Locale::factory()->create(['code' => 'es', 'is_default' => true]);

        $document = Document::factory()->create(['locale_code' => 'es']);

        expect($document->locale->code)->toBe('es');
    });

    it('refuses to delete a locale that a hero line still depends on', function (): void {
        $locale = Locale::factory()->create(['code' => 'es']);
        HeroLine::factory()->forLocale('es')->create();

        expect(fn () => $locale->delete())->toThrow(QueryException::class);
    });

    it('keeps a project when its media is deleted', function (): void {
        $media = Media::factory()->create();
        $project = Project::factory()->create(['thumbnail_media_id' => $media->id]);

        $media->delete();

        expect($project->fresh())->not->toBeNull()
            ->and($project->fresh()?->thumbnail_media_id)->toBeNull();
    });

    it('removes pivot rows when a project is deleted', function (): void {
        $project = Project::factory()->withTechnologies(2)->create();

        $project->delete();

        expect(DB::table('project_technology')->count())->toBe(0);
    });
});

it('allows only one profile row', function (): void {
    Profile::factory()->create();

    expect(fn () => Profile::factory()->create())->toThrow(QueryException::class);
});

it('rejects a menu item with an unknown kind', function (): void {
    expect(fn () => MenuItem::factory()->create(['kind' => 'bogus']))
        ->toThrow(QueryException::class);
});
