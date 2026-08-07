<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Locale>
 */
class LocaleFactory extends Factory
{
    protected $model = Locale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->languageCode();

        return [
            'code' => $name,
            'name' => ucfirst($name),
            'native_name' => ucfirst($name),
            // A partial unique index allows only one default row, so the
            // default state must not claim it.
            'is_default' => false,
            'is_enabled' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => ['is_default' => true]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => ['is_enabled' => false]);
    }
}
