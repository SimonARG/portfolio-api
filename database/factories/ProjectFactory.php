<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\Technology;
use Database\Factories\Concerns\MakesTranslations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    use MakesTranslations;

    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            // Slugs are unique per locale in the database, so each locale gets a
            // distinct value rather than the same string three times.
            'slug' => $this->translated(fn (string $locale): string => "{$key}-{$locale}"),
            'title' => strtoupper(fake()->words(2, true)),
            'summary' => $this->translatedText('Summary'),
            'description_md' => $this->translated(
                fn (string $locale): string => "Lead line ({$locale}):\n\n- first\n- second",
            ),
            'repo_url' => 'https://github.com/SimonARG/'.$key,
            'live_url' => null,
            'sort_order' => fake()->numberBetween(1, 100),
            'is_published' => true,
            'published_at' => null,
            'meta_title' => $this->translatedText('Meta title'),
            'meta_description' => $this->translatedText('Meta description'),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => ['is_published' => false]);
    }

    /**
     * Published, but embargoed until a future date — the case the `published`
     * scope has to exclude even though `is_published` is true.
     */
    public function embargoed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => true,
            'published_at' => now()->addWeek(),
        ]);
    }

    /**
     * Attach technologies in the given order, mirroring how a real project
     * carries a deliberate chip order on the pivot.
     */
    public function withTechnologies(int $count = 3): static
    {
        return $this->afterCreating(function (Project $project) use ($count): void {
            Technology::factory()
                ->count($count)
                ->create()
                ->each(function (Technology $technology, int $index) use ($project): void {
                    $project->technologies()->attach($technology, ['sort_order' => $index + 1]);
                });
        });
    }
}
