<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the key exists even on a database seeded with model events
        // suppressed, so nothing downstream has to cope with a missing version.
        // A plain existence check rather than a write, because writing it would
        // make the seeder non-idempotent for the one value that must not drift.
        if (Setting::query()->whereKey(Setting::CONTENT_VERSION)->doesntExist()) {
            Setting::set(Setting::CONTENT_VERSION, 1);
        }
    }
}
