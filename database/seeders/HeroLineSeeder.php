<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HeroLine;
use Database\Seeders\Support\ContentInventory;
use Illuminate\Database\Seeder;

class HeroLineSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ContentInventory::section('hero_lines') as $line) {
            HeroLine::query()->updateOrCreate(
                ['locale_code' => $line['locale_code']],
                [
                    'role_label' => $line['role_label'],
                    'tech_string' => $line['tech_string'],
                    'sort_order' => $line['sort_order'],
                ],
            );
        }
    }
}
