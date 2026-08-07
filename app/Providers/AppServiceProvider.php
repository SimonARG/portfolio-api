<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\MarkdownRenderer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        //
    }
}
