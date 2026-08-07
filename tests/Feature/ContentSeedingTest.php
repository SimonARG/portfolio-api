<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\HeroLine;
use App\Models\Locale;
use App\Models\MenuItem;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Models\Technology;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Support\ContentInventory;
use Illuminate\Support\Facades\DB;

/**
 * The plan's §4.2 acceptance criterion, made executable: the database holds
 * every string from §1.3 in all three languages, and running the seeders twice
 * changes nothing.
 */

/**
 * A stable, ordered snapshot of every content row — the thing that must not
 * change between two seeder runs.
 */
function contentSnapshot(): string
{
    return json_encode([
        'locales' => Locale::query()->orderBy('code')
            ->get(['code', 'name', 'native_name', 'is_default', 'is_enabled', 'sort_order'])->toArray(),
        'profile' => Profile::query()->get()
            ->map(fn (Profile $p): array => [
                $p->email, $p->github_url, $p->linkedin_url,
                $p->getTranslations('name'), $p->getTranslations('about_md'),
                $p->getTranslations('about_html'), $p->getTranslations('meta_title'),
                $p->getTranslations('meta_description'),
            ])->toArray(),
        'hero' => HeroLine::query()->orderBy('locale_code')
            ->get(['locale_code', 'role_label', 'tech_string', 'sort_order'])->toArray(),
        'menu' => MenuItem::query()->orderBy('key')->get()
            ->map(fn (MenuItem $m): array => [
                $m->key, $m->getTranslations('label'), $m->icon,
                $m->kind, $m->target, $m->sort_order, $m->is_published,
            ])->toArray(),
        'social' => SocialLink::query()->orderBy('key')->get()
            ->map(fn (SocialLink $s): array => [
                $s->key, $s->getTranslations('label'), $s->url,
                $s->icon, $s->sort_order, $s->is_published,
            ])->toArray(),
        'documents' => Document::query()->orderBy('key')->get()
            ->map(fn (Document $d): array => [
                $d->key, $d->getTranslations('label'), $d->locale_code,
                $d->sort_order, $d->is_published,
            ])->toArray(),
        'technologies' => Technology::query()->orderBy('slug')
            ->get(['slug', 'name', 'sort_order'])->toArray(),
        'projects' => Project::query()->orderBy('key')->get()
            ->map(fn (Project $p): array => [
                $p->key, $p->getTranslations('slug'), $p->title,
                $p->getTranslations('description_md'), $p->getTranslations('description_html'),
                $p->repo_url, $p->live_url, $p->sort_order, $p->is_published, $p->published_at,
            ])->toArray(),
        'pivot' => DB::table('project_technology')
            ->join('projects', 'projects.id', '=', 'project_technology.project_id')
            ->join('technologies', 'technologies.id', '=', 'project_technology.technology_id')
            ->orderBy('projects.key')->orderBy('project_technology.sort_order')
            ->get(['projects.key', 'technologies.slug', 'project_technology.sort_order'])->toArray(),
        'content_version' => Setting::contentVersion(),
    ], JSON_UNESCAPED_UNICODE);
}

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

describe('idempotency', function (): void {
    it('changes nothing when the seeders are run a second time', function (): void {
        $before = contentSnapshot();

        $this->seed(DatabaseSeeder::class);

        expect(contentSnapshot())->toBe($before);
    });

    it('does not advance content_version on a re-seed', function (): void {
        // Stronger than the plan asks for, and it only holds because every
        // seeder keys on a natural key and the observer fires on real writes only.
        $before = Setting::contentVersion();

        $this->seed(DatabaseSeeder::class);

        expect(Setting::contentVersion())->toBe($before);
    });

    it('does not touch updated_at on a re-seed', function (): void {
        $this->seed(DatabaseSeeder::class);

        expect(Project::query()->whereColumn('updated_at', '<>', 'created_at')->count())->toBe(0)
            ->and(MenuItem::query()->whereColumn('updated_at', '<>', 'created_at')->count())->toBe(0);
    });

    it('creates no duplicate rows', function (): void {
        $this->seed(DatabaseSeeder::class);

        expect(Project::query()->count())->toBe(6)
            ->and(MenuItem::query()->count())->toBe(5)
            ->and(SocialLink::query()->count())->toBe(6)
            ->and(HeroLine::query()->count())->toBe(3)
            ->and(Document::query()->count())->toBe(2)
            ->and(Profile::query()->count())->toBe(1);
    });
});

describe('coverage', function (): void {
    it('seeds every row the plan §1.3 inventory lists', function (): void {
        expect(Locale::query()->count())->toBe(3)
            ->and(HeroLine::query()->count())->toBe(3)
            ->and(MenuItem::query()->count())->toBe(5)
            ->and(SocialLink::query()->count())->toBe(6)
            ->and(Document::query()->count())->toBe(2)
            ->and(Project::query()->count())->toBe(6)
            // The legacy nine plus Nuxt, TypeScript and PostgreSQL, which the
            // portfolio rewrite needs to describe the new stack.
            ->and(Technology::query()->count())->toBe(12);
    });

    it('gives every project a description in all three languages', function (): void {
        foreach (Project::all() as $project) {
            expect($project->getTranslatedLocales('description_md'))
                ->toEqualCanonicalizing(['es', 'en', 'ja'], "project {$project->key}")
                ->and($project->getTranslatedLocales('description_html'))
                ->toEqualCanonicalizing(['es', 'en', 'ja'], "project {$project->key}");
        }
    });

    it('gives every project a slug in all three languages', function (): void {
        foreach (Project::all() as $project) {
            expect($project->getTranslatedLocales('slug'))
                ->toEqualCanonicalizing(['es', 'en', 'ja'], "project {$project->key}");
        }
    });

    it('gives every menu item and social link a label in all three languages', function (): void {
        foreach (MenuItem::all() as $item) {
            expect($item->getTranslatedLocales('label'))
                ->toEqualCanonicalizing(['es', 'en', 'ja'], "menu {$item->key}");
        }

        foreach (SocialLink::all() as $link) {
            expect($link->getTranslatedLocales('label'))
                ->toEqualCanonicalizing(['es', 'en', 'ja'], "social {$link->key}");
        }
    });

    it('keeps the projects in their legacy display order', function (): void {
        expect(Project::query()->ordered()->pluck('key')->all())
            ->toBe(['mpi', 'mixtorrents', 'newtab', 'ambient', 'blog', 'portfolio']);
    });

    it('preserves each project\'s tech chips and their order', function (): void {
        expect(Project::query()->where('key', 'mpi')->first()?->technologies->pluck('slug')->all())
            ->toBe(['laravel', 'html', 'css', 'js', 'vuejs', 'tailwindcss', 'mysql']);
    });

    it('keeps the three hero lines unrelated to one another', function (): void {
        // Three role labels in three languages, each with a DIFFERENT tech list.
        $lines = HeroLine::query()->ordered()->get()
            ->mapWithKeys(fn (HeroLine $l): array => [$l->locale_code => $l->tech_string]);

        expect($lines['es'])->toBe('PHP HTML JS CSS')
            ->and($lines['en'])->toBe('LARAVEL SQL VUE')
            ->and($lines['ja'])->toBe('GIT JAVA PYTHON C++');
    });

    it('seeds the profile with both About paragraphs in each language', function (): void {
        $profile = Profile::singleton();

        foreach (['es', 'en', 'ja'] as $locale) {
            expect(substr_count((string) $profile?->getTranslation('about_html', $locale), '<p>'))
                ->toBe(2, "about {$locale}");
        }
    });
});

describe('the deliberate departures', function (): void {
    it('applies every copy correction', function (): void {
        $profile = Profile::singleton();

        expect($profile?->getTranslation('about_md', 'es'))
            ->toContain('móviles')->not->toContain('móbiles')
            ->and($profile?->getTranslation('about_md', 'en'))
            ->toContain('acquire')->not->toContain('adquire')
            ->toContain('Creative Suite (')
            ->toContain('the desktop suites by Google')
            ->toContain('maintaining')->not->toContain('maitaining')
            ->and($profile?->getTranslation('meta_description', 'es'))
            ->toBe('Simon Chasnovsky - Portfolio');

        expect(Project::query()->where('key', 'ambient')->first()?->getTranslation('description_md', 'es'))
            ->toContain('la configuración')->not->toContain('laconfiguración');

        expect(Project::query()->where('key', 'newtab')->first()?->getTranslation('description_md', 'en'))
            ->toContain('Adding')->not->toContain('Addding')
            ->toContain('shortcuts')->not->toContain('shorcuts');
    });

    it('rewrites the portfolio project for the new stack', function (): void {
        $portfolio = Project::query()->where('key', 'portfolio')->first();

        foreach (['es', 'en', 'ja'] as $locale) {
            expect($portfolio?->getTranslation('description_md', $locale))
                ->not->toContain('vanilla')
                ->not->toContain('バニラ');
        }

        expect($portfolio?->repo_url)->toBe('https://github.com/SimonARG/portfolio-client')
            ->and($portfolio?->live_url)->toBe('https://www.simon-dev.com/')
            ->and($portfolio?->technologies->pluck('slug')->all())
            ->toBe(['nuxt', 'vuejs', 'typescript', 'tailwindcss', 'laravel', 'php', 'postgresql']);
    });

    it('keeps the halfwidth katakana LinkedIn menu label', function (): void {
        // Deliberate: see content-overrides.json decisions.
        expect(MenuItem::query()->where('key', 'linkedin')->first()?->getTranslation('label', 'ja'))
            ->toBe('ﾘﾝｹﾄﾞｲﾝ')
            ->and(SocialLink::query()->where('key', 'linkedin')->first()?->getTranslation('label', 'ja'))
            ->toBe('リンクトイン');
    });

    it('fills the labels the legacy markup left untranslated', function (): void {
        $rym = SocialLink::query()->where('key', 'rym')->first();

        expect($rym?->getTranslation('label', 'ja'))->toBe('レートユアミュージック')
            ->and($rym?->getTranslation('label', 'en'))->toBe('Rate Your Music');

        expect(Document::query()->where('key', 'cv_es')->first()?->getTranslation('label', 'ja'))
            ->toBe('スペイン語')
            ->and(Document::query()->where('key', 'cv_en')->first()?->getTranslation('label', 'ja'))
            ->toBe('英語');
    });

    it('never seeds a legacy inline font-size as content', function (): void {
        // Presentation, not content — session 5 handles CJK sizing in CSS.
        $everything = json_encode([
            Profile::query()->get()->toArray(),
            MenuItem::query()->get()->map(fn (MenuItem $m): array => $m->getTranslations('label'))->toArray(),
            SocialLink::query()->get()->map(fn (SocialLink $s): array => $s->getTranslations('label'))->toArray(),
            Project::query()->get()->map(fn (Project $p): array => $p->getTranslations('description_html'))->toArray(),
        ], JSON_UNESCAPED_UNICODE);

        expect($everything)
            ->not->toContain('font-size')
            ->not->toContain('font-weight')
            ->not->toContain('legacy_style');
    });
});

describe('rendered output', function (): void {
    it('turns the legacy br-lists into real list markup', function (): void {
        $mpi = Project::query()->where('key', 'mpi')->first();
        $html = (string) $mpi?->getTranslation('description_html', 'es');

        expect($html)
            ->toContain('<ul>')
            ->toContain('<li>Iniciar sesión con una cuenta de la plataforma</li>')
            ->not->toContain('<br>')
            ->and(substr_count($html, '<li>'))->toBe(5);
    });

    it('renders the Japanese middle-dot bullets as list items too', function (): void {
        $mpi = Project::query()->where('key', 'mpi')->first();
        $html = (string) $mpi?->getTranslation('description_html', 'ja');

        expect(substr_count($html, '<li>'))->toBe(5)
            // The legacy markup used ・ as a bullet glyph; the harvest stripped
            // it, so it must not survive into the rendered list item.
            ->and($html)->not->toContain('<li>・');
    });

    it('produces no unsanitised markup anywhere in the seeded content', function (): void {
        foreach (Project::all() as $project) {
            foreach ($project->getTranslations('description_html') as $locale => $html) {
                expect($html)
                    ->not->toContain('<script')
                    ->not->toContain('javascript:')
                    ->not->toContain('<h1')
                    ->not->toContain('onerror', "project {$project->key} {$locale}");
            }
        }
    });
});

it('records the seeded content_version', function (): void {
    expect(Setting::contentVersion())->toBeGreaterThan(0);
});

it('reads the same inventory the tests assert against', function (): void {
    // Guards against the seeders and the assertions drifting onto different
    // copies of the data.
    expect(ContentInventory::section('projects'))->toHaveCount(6)
        ->and(ContentInventory::section('locales'))->toHaveCount(3);
});
