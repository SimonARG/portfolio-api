<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MenuItem;
use Database\Factories\Concerns\MakesTranslations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    use MakesTranslations;

    protected $model = MenuItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->slug(1);

        return [
            'key' => $key,
            'label' => $this->translatedText(ucfirst($key)),
            'icon' => 'tabler:link',
            'kind' => MenuItem::KIND_POPUP,
            'target' => $key,
            'sort_order' => fake()->numberBetween(1, 100),
            'is_published' => true,
        ];
    }

    public function external(string $url = 'https://example.com'): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => MenuItem::KIND_EXTERNAL,
            'target' => $url,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => ['is_published' => false]);
    }
}
