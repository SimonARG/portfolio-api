<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Profile;
use App\Models\Project;
use App\Support\MarkdownRenderer;

/**
 * Keeps every `*_md` column's rendered `*_html` twin in step, per locale,
 * whenever a model is saved.
 *
 * Rendering on save rather than on read is what lets the client ship no
 * Markdown parser (§2.4) and keeps the render cost off the request path
 * entirely — a project is written a handful of times and read on every cache
 * miss for the rest of its life.
 *
 * Doing it in an observer rather than in the admin controller means the
 * invariant holds no matter how the row was written: through the session-3 API,
 * through a seeder, or from tinker.
 */
class RendersMarkdown
{
    public function __construct(private readonly MarkdownRenderer $renderer) {}

    /**
     * The parameter is the concrete union rather than Model because the models
     * carrying Markdown are a closed, named set — adding a third means adding it
     * here, which is the point: the observer should not silently do nothing for
     * a model that forgot to declare MARKDOWN_COLUMNS.
     */
    public function saving(Profile|Project $model): void
    {
        foreach ($model::MARKDOWN_COLUMNS as $source => $target) {
            // Re-render only when the source actually changed. Without this,
            // every save of an unrelated field — a sort_order drag in the admin
            // — would rewrite the HTML and, through the content-version
            // observer, needlessly invalidate every cache layer.
            if (! $model->isDirty($source)) {
                continue;
            }

            /** @var array<string, string|null> $translations */
            $translations = $model->getTranslations($source);

            $rendered = [];

            foreach ($translations as $locale => $markdown) {
                $html = $this->renderer->toHtml($markdown);

                if ($html !== null) {
                    $rendered[$locale] = $html;
                }
            }

            if ($rendered === []) {
                // NOT setAttribute($target, null): the target is a translatable
                // column, so Spatie would route a scalar through setTranslation()
                // and store {"es": null} rather than clearing the column.
                $model->forgetTranslations($target, asNull: true);

                continue;
            }

            // Assigned as a whole map rather than one setTranslation() per
            // locale, so a locale dropped from the source is dropped from the
            // rendered output too instead of lingering as a stale translation.
            $model->setAttribute($target, $rendered);
        }
    }
}
