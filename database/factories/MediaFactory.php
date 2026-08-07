<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use Database\Factories\Concerns\MakesTranslations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    use MakesTranslations;

    protected $model = Media::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            'disk' => 'public',
            'path' => "media/{$key}.avif",
            'mime' => 'image/avif',
            'width' => 1920,
            'height' => 974,
            'duration_ms' => null,
            'byte_size' => fake()->numberBetween(5_000, 200_000),
            'checksum' => hash('sha256', $key),
            'alt' => $this->translatedText('Screenshot'),
            'renditions' => null,
        ];
    }

    public function video(): static
    {
        return $this->state(function (array $attributes): array {
            $key = $attributes['key'] ?? fake()->unique()->slug(2);

            return [
                'path' => "media/{$key}.mp4",
                'mime' => 'video/mp4',
                'duration_ms' => fake()->numberBetween(20_000, 60_000),
                'byte_size' => fake()->numberBetween(1_000_000, 9_000_000),
            ];
        });
    }

    public function document(): static
    {
        return $this->state(function (array $attributes): array {
            $key = $attributes['key'] ?? fake()->unique()->slug(2);

            return [
                'path' => "media/{$key}.pdf",
                'mime' => 'application/pdf',
                'width' => null,
                'height' => null,
            ];
        });
    }
}
