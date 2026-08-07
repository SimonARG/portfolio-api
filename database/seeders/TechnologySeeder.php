<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Technology;
use Database\Seeders\Support\ContentInventory;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<int, array<string, mixed>> $additional */
        $additional = ContentInventory::overrides()['additional_technologies'] ?? [];

        // The legacy nine, plus the three the portfolio rewrite needs in order
        // to describe the new stack honestly (Nuxt, TypeScript, PostgreSQL).
        $technologies = [...ContentInventory::section('technologies'), ...$additional];

        foreach ($technologies as $technology) {
            Technology::query()->updateOrCreate(
                ['slug' => $technology['slug']],
                [
                    'name' => $technology['name'],
                    'sort_order' => $technology['sort_order'],
                ],
            );
        }
    }
}
