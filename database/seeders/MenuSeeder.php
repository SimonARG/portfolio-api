<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MenuItem;
use Database\Seeders\Support\ContentInventory;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, string> $icons */
        $icons = ContentInventory::overrides()['icons']['menu_items'] ?? [];

        foreach (ContentInventory::section('menu_items') as $item) {
            /** @var string $key */
            $key = $item['key'];

            MenuItem::query()->updateOrCreate(
                ['key' => $key],
                [
                    // `legacy_style` is never read: the Japanese inline font-size
                    // overrides are presentation, and session 5 solves CJK
                    // sizing in CSS (plan §4.2 "Watch for").
                    'label' => ContentInventory::present($item['label']),
                    'icon' => $icons[$key] ?? null,
                    'kind' => $item['kind'],
                    'target' => $item['target'],
                    'sort_order' => $item['sort_order'],
                    'is_published' => true,
                ],
            );
        }
    }
}
