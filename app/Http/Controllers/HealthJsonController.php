<?php

namespace App\Http\Controllers;

use App\Support\Health\HealthSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Health\ResultStores\ResultStore;

/**
 * The health results for a monitor that has no session to log into.
 *
 * Guarded by a secret rather than by a session, and the route is not
 * registered at all without one: an open endpoint hands a stranger the
 * list of which parts of the system are currently failing.
 */
class HealthJsonController extends Controller
{
    public function __invoke(Request $request, ResultStore $store): JsonResponse
    {
        $secret = (string) config('health.json_secret');
        $given = (string) ($request->header('X-Health-Secret') ?? $request->query('secret', ''));

        // hash_equals, so a wrong guess takes as long as a right one.
        abort_unless($secret !== '' && hash_equals($secret, $given), 404);

        $results = HealthSummary::make($store);

        // 503 on failure, because that is what a monitor watches for.
        return response()->json($results, $results['healthy'] ? 200 : 503);
    }
}
