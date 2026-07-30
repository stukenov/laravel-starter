<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        $log = trim((string) $request->query('log', ''));
        $event = trim((string) $request->query('event', ''));

        return $this->page($request, 'dash/activity/index', [
            'entries' => Activity::query()
                // Named columns, not `select *`. `properties` is a jsonb
                // blob the feed never shows, and fifty rows of it travel
                // from the database for nothing on every page.
                ->select([
                    'id', 'log_name', 'description', 'event',
                    'subject_type', 'subject_id', 'causer_type', 'causer_id',
                    'attribute_changes', 'created_at', 'seq',
                ])
                ->with('causer:id,name,email')
                ->filter($log, $event)
                ->orderByDesc('created_at')
                ->paginate(50)
                ->withQueryString()
                ->through(fn (Activity $entry) => $entry->toFeedItem()),
            'filters' => ['log' => $log, 'event' => $event],
            'logs' => Activity::distinctLogNames(),
            'events' => Activity::distinctEvents(),
        ], 'entries');
    }
}
