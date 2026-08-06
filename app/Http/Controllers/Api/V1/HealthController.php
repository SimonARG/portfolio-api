<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Liveness and dependency probe.
 *
 * Kept dependency-light on purpose: it is the endpoint the Nuxt client hits to
 * prove the two applications can talk to each other, and the one a deploy
 * script polls before flipping a slot.
 */
final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->probe(static fn () => DB::connection()->getPdo()),
            'cache' => $this->probe(static function (): void {
                Cache::put('health:probe', 1, 5);
                Cache::get('health:probe');
            }),
        ];

        $ok = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'version' => config('app.version'),
            'environment' => App::environment(),
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }

    /**
     * @param  callable(): mixed  $check
     */
    private function probe(callable $check): bool
    {
        try {
            $check();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
