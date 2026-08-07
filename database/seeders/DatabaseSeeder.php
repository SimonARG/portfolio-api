<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters: locales before anything with a locale_code foreign key,
     * and technologies before projects, which attach to them by slug.
     *
     * Note the absence of WithoutModelEvents. The seeders depend on model
     * events: RendersMarkdown is what turns each project's Markdown into the
     * sanitised HTML the client serves, and suppressing events would seed a
     * database whose description_html columns were all null.
     *
     * Every seeder is written to be re-runnable. They key on natural keys
     * through updateOrCreate, so a second run finds each row unchanged, saves
     * nothing, and — because the content-version observer only fires on real
     * writes — does not even advance content_version.
     */
    public function run(): void
    {
        $this->call([
            LocaleSeeder::class,
            SettingSeeder::class,
            ProfileSeeder::class,
            HeroLineSeeder::class,
            MenuSeeder::class,
            SocialLinkSeeder::class,
            DocumentSeeder::class,
            TechnologySeeder::class,
            ProjectSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
