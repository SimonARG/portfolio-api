<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Document;
use App\Models\HeroLine;
use App\Models\Locale;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SocialLink;
use App\Models\Technology;
use App\Observers\BumpsContentVersion;
use App\Support\MarkdownRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Every model whose rows are public content.
     *
     * Writing any of them invalidates the cached representation of the site, so
     * each gets the content-version observer. `Setting` is deliberately absent:
     * it holds the version counter itself, and observing it would recurse.
     * `User` is absent because accounts are not content.
     *
     * @var array<int, class-string<Model>>
     */
    public const array CONTENT_MODELS = [
        Locale::class,
        Media::class,
        Profile::class,
        HeroLine::class,
        MenuItem::class,
        SocialLink::class,
        Document::class,
        Technology::class,
        Project::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Building the CommonMark environment and the sanitiser allowlist is
        // not free, and a seeder run renders a couple of dozen documents.
        $this->app->singleton(MarkdownRenderer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (self::CONTENT_MODELS as $model) {
            $model::observe(BumpsContentVersion::class);
        }

        // Accessing an unloaded relationship should fail in development rather
        // than silently issue a query per row. Session 3 serves list endpoints
        // that would otherwise N+1 through technologies and media without
        // anything failing to warn us.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
