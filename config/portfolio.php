<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Admin account
    |--------------------------------------------------------------------------
    |
    | Credentials for the account AdminUserSeeder creates. They come from the
    | environment and never from a literal in a migration or seeder, so the
    | production password is not sitting in the repository's history
    | (REBUILD_PLAN.md §4.10 step 8).
    |
    | With ADMIN_EMAIL unset the seeder skips the account entirely, which is the
    | right behaviour for CI.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Simón Chasnovsky'),
        'email' => env('ADMIN_EMAIL', ''),
        'password' => env('ADMIN_PASSWORD', ''),
    ],

];
