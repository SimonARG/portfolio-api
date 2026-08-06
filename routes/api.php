<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Versioned under /api/v1, stateless, JSON only. The full surface is specified
| in REBUILD_PLAN.md §2.5 and gets built out in session 3; session 1 wires only
| the health probe so the client can prove the round trip end to end.
|
*/

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('api.v1.health');
});
