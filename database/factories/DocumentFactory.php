<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\Locale;
use App\Models\Media;
use Database\Factories\Concerns\MakesTranslations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    use MakesTranslations;

    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->slug(1);

        return [
            'key' => $key,
            'label' => $this->translatedText(ucfirst($key)),
            'locale_code' => Locale::factory(),
            'media_id' => Media::factory()->document(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => ['is_published' => false]);
    }
}
