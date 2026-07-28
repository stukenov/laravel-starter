<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doc\StoreRequest;
use App\Http\Requests\Doc\UpdateRequest;
use App\Models\Doc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $this->authorize('viewAny', Doc::class);

        $search = trim((string) $request->query('search', ''));

        // RULE(api): one action, both surfaces — AGENTS.md. The
        // envelope lives on the base controller so the panel and
        // /api/v1 cannot drift apart.
        return $this->page($request, 'dash/docs/index', [
            'records' => Doc::search($search)
                ->orderByDesc('created_at')

                ->paginate(20)
                ->withQueryString()
                // Not the model itself: a page handed the whole record
                // receives every column added to it later, meant for it
                // or not.
                ->through(fn (Doc $record) => $record->toPanelItem()),
            'search' => $search,
        ], 'records');
    }

    public function store(StoreRequest $request): RedirectResponse|JsonResponse
    {
        $record = Doc::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('entities/docs.created')]);

        // 201 with the record: the id is the one thing an API caller
        // cannot work out for itself.
        return $this->saved($request, $record->toPanelItem(), 201);
    }

    public function update(UpdateRequest $request, Doc $doc): RedirectResponse|JsonResponse
    {
        $doc->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('entities/docs.updated')]);

        return $this->saved($request, $doc->fresh()->toPanelItem());
    }

    public function destroy(Request $request, Doc $doc): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $doc);

        $doc->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('entities/docs.deleted')]);

        return $this->saved($request);
    }
}
