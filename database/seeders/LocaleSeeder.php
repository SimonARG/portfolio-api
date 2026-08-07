<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Locale;
use Database\Seeders\Support\ContentInventory;
use Illuminate\Database\Seeder;

class LocaleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ContentInventory::section('locales') as $locale) {
            Locale::query()->updateOrCreate(
                ['code' => $locale['code']],
                [
                    'name' => $locale['name'],
                    'native_name' => $locale['native_name'],
                    'is_default' => $locale['is_default'],
                    'is_enabled' => $locale['is_enabled'],
                    'sort_order' => $locale['sort_order'],
                ],
            );
        }
    }
}
