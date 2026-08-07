<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Profile;
use Database\Seeders\Support\ContentInventory;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profile = ContentInventory::profile();

        /** @var array<string, array<int, string>> $about */
        $about = $profile['about'];

        // The About copy is two paragraphs, held in the harvest as a list of
        // strings because the legacy markup separated them with </br></br>
        // (plan §1.5 defect 4). A blank line between them is the Markdown
        // equivalent, and renders as two real <p> elements.
        $aboutMarkdown = [];

        foreach ($about as $locale => $paragraphs) {
            $aboutMarkdown[$locale] = implode("\n\n", $paragraphs);
        }

        Profile::query()->updateOrCreate(
            ['email' => $profile['email']],
            [
                'name' => $profile['name'],
                'about_md' => $aboutMarkdown,
                'github_url' => $profile['github_url'],
                'linkedin_url' => $profile['linkedin_url'],
                'meta_title' => ContentInventory::present($profile['meta_title']),
                'meta_description' => ContentInventory::present($profile['meta_description']),
            ],
        );
    }
}
