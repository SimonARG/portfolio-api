<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HeroLine;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeroLine>
 */
class HeroLineFactory extends Factory
{
    protected $model = HeroLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'locale_code' => Locale::factory(),
            'role_label' => fake()->jobTitle(),
            // Deliberately unrelated to the label: the three hero lines pair a
            // role with a DIFFERENT tech list, they are not translations.
            'tech_string' => strtoupper(implode(' ', fake()->words(3))),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function forLocale(string $code): static
    {
        return $this->state(fn (array $attributes): array => ['locale_code' => $code]);
    }
}
