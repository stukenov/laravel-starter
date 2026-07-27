<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Activity::class);

        $log = trim((string) $request->query('log', ''));
        $event = trim((string) $request->query('event', ''));

        return Inertia::render('dash/activity/index', [
            'entries' => Activity::query()
                ->with('causer:id,name,email')
                ->filter($log, $event)
                ->orderByDesc('created_at')
                ->paginate(50)
                ->withQueryString()
                ->through(fn (Activity $entry) => $entry->toFeedItem()),
            'filters' => ['log' => $log, 'event' => $event],
            'logs' => Activity::distinctLogNames(),
            'events' => Activity::distinctEvents(),
        ]);
    }
}
