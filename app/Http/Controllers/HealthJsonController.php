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

        // A header, never a query parameter. `?secret=…` puts the secret
        // in the URL, and a URL is written to the access log of every
        // proxy it passes — so a monitor polling once a minute would
        // record its own credential half a million times a year, into
        // files that get rotated, shipped and backed up. The header form
        // is the only one README ever documented; the fallback was pure
        // liability.
        $given = (string) $request->header('X-Health-Secret');

        // hash_equals, so a wrong guess takes as long as a right one.
        abort_unless($secret !== '' && hash_equals($secret, $given), 404);

        $results = HealthSummary::make($store);

        // 503 on failure, because that is what a monitor watches for.
        return response()->json($results, $results['healthy'] ? 200 : 503);
    }
}
