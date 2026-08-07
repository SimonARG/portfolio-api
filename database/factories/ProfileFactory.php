<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Profile;
use Database\Factories\Concerns\MakesTranslations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 *
 * A unique index on ((true)) allows exactly one profile row, so this factory
 * builds the singleton — creating two in one test will fail, by design.
 */
class ProfileFactory extends Factory
{
    use MakesTranslations;

    protected $model = Profile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->safeEmail(),
            'name' => $this->translatedText('Simon Chasnovsky'),
            'about_md' => $this->translatedText('About paragraph'),
            'github_url' => 'https://github.com/SimonARG',
            'linkedin_url' => 'https://www.linkedin.com/in/simonpaul99/',
            'meta_title' => $this->translatedText('Simon Chasnovsky'),
            'meta_description' => $this->translatedText('Portfolio'),
        ];
    }
}
