<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SocialLink;
use Database\Factories\Concerns\MakesTranslations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialLink>
 */
class SocialLinkFactory extends Factory
{
    use MakesTranslations;

    protected $model = SocialLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->slug(1);

        return [
            'key' => $key,
            'label' => $this->translatedText(ucfirst($key)),
            'url' => fake()->url(),
            'icon' => 'simple-icons:github',
            'sort_order' => fake()->numberBetween(1, 100),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => ['is_published' => false]);
    }
}
