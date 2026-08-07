<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SocialLink;
use Database\Seeders\Support\ContentInventory;
use Illuminate\Database\Seeder;

class SocialLinkSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, string> $icons */
        $icons = ContentInventory::overrides()['icons']['social_links'] ?? [];

        /** @var array<string, array{label: array<string, string>}> $overrides */
        $overrides = ContentInventory::overrides()['social_links'] ?? [];

        foreach (ContentInventory::section('social_links') as $link) {
            /** @var string $key */
            $key = $link['key'];

            // RYM had a single untranslated <span> in the legacy markup; the
            // override fills all three locales. See content-overrides.json.
            $label = $overrides[$key]['label'] ?? $link['label'];

            SocialLink::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => ContentInventory::present($label),
                    'url' => $link['url'],
                    'icon' => $icons[$key] ?? null,
                    'sort_order' => $link['sort_order'],
                    'is_published' => $link['is_published'],
                ],
            );
        }
    }
}
