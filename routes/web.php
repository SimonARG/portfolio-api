<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| This application is headless — nginx routes only /api/* to PHP-FPM and every
| other path to the Nuxt service (REBUILD_PLAN.md §2.3), so nothing here is
| reachable in production. The root exists so a misrouted request explains
| itself instead of returning a stack trace.
|
*/

Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'docs' => '/api/v1/health',
    'message' => 'This is the simon-dev.com API. The site itself is served by the Nuxt client.',
]));
