<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Technology;
use Database\Seeders\Support\ContentInventory;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array<string, mixed>> $overrides */
        $overrides = ContentInventory::overrides()['projects'] ?? [];

        $technologyIds = Technology::query()->pluck('id', 'slug');

        foreach (ContentInventory::section('projects') as $entry) {
            /** @var string $key */
            $key = $entry['key'];

            $override = $overrides[$key] ?? [];

            /** @var array<string, array<string, mixed>> $blocks */
            $blocks = $override['description_blocks'] ?? $entry['description_blocks'];

            /** @var array<int, string> $slugs */
            $slugs = $override['technologies'] ?? $entry['technologies'];

            $project = Project::query()->updateOrCreate(
                ['key' => $key],
                [
                    'slug' => ContentInventory::present($entry['slug']),
                    'title' => $entry['title'],
                    // The <br>-separated pseudo-list becomes a real Markdown
                    // list; the RendersMarkdown observer turns it into the
                    // sanitised HTML the client renders.
                    'description_md' => ContentInventory::markdownTranslations($blocks),
                    'repo_url' => $override['repo_url'] ?? $entry['repo_url'],
                    'live_url' => $override['live_url'] ?? $entry['live_url'],
                    'sort_order' => $entry['sort_order'],
                    'is_published' => $entry['is_published'],
                    // Null rather than a date: the legacy site recorded no
                    // publication dates, and inventing them would be fabrication.
                    // The published() scope reads null as "no embargo".
                    'published_at' => null,
                ],
            );

            $project->syncTechnologies(
                array_values(array_filter(array_map(
                    static fn (string $slug): ?int => $technologyIds[$slug] ?? null,
                    $slugs,
                ))),
            );
        }
    }
}
