<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Support\Health\HealthSummary;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Health\ResultStores\ResultStore;

/**
 * RULE(external): no third-party domain at render time — AGENTS.md.
 *
 * Spatie ships a dashboard of its own, and it opens with a stylesheet from
 * rsms.me. The browser blocks it here, so the page would arrive unstyled
 * with the reason visible only in a console. Reading the same stored
 * results into a page of our own costs a controller and keeps one design
 * language across the panel.
 */
class HealthController extends Controller
{
    public function index(ResultStore $store): Response
    {
        // Gated by `can:health.view` on the route. There is no model here,
        // so nothing for a policy to hang off — and spatie registers every
        // permission as a gate of the same name.
        return Inertia::render('dash/health/index', HealthSummary::make($store));
    }
}
