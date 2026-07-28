<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TokenStoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RULE(api): a token nobody can create is an API nobody has — AGENTS.md.
 *
 * The panel and the API share this controller like every other. Listing
 * and revoking work from both; creating is deliberately browser-only,
 * because a token that can mint another token cannot be revoked in any
 * meaningful sense — you would be taking away one key from somebody
 * holding the key cutter.
 */
class TokenController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        return $this->page($request, 'settings/tokens', [
            'tokens' => $this->tokensOf($request),
            // A normal prop, not a flash event. `Inertia::flash` fires a
            // router event that a toast consumes and forgets — right for
            // "saved", wrong for a value the person has to read off the
            // screen and copy before it is gone forever.
            //
            // `hasSession()` because this action serves the API too, and
            // a token request is stateless: reaching for the session
            // there is a 500 on a list endpoint. The shared controller
            // makes that a real possibility rather than a theory, and it
            // is exactly what the API test caught.
            'created' => $request->hasSession()
                ? $request->session()->get('created_token')
                : null,
        ], 'tokens');
    }

    /**
     * The plaintext token exists for exactly one response.
     *
     * Only its hash is stored, so it cannot be shown again — and saying
     * so on screen at the moment it appears is the difference between a
     * person copying it and a person losing it.
     */
    public function store(TokenStoreRequest $request): RedirectResponse
    {
        $token = $request->user()->createToken($request->validated('name'));

        $request->session()->flash('created_token', $token->plainTextToken);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('common.tokens.created'),
        ]);

        return back();
    }

    public function destroy(Request $request, string $token): RedirectResponse|JsonResponse
    {
        // Scoped to the person's own tokens rather than found globally:
        // otherwise anybody with an id could revoke anybody's access.
        $deleted = $request->user()->tokens()->whereKey($token)->delete();

        abort_if($deleted === 0, 404);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('common.tokens.revoked'),
        ]);

        return $this->saved($request);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tokensOf(Request $request): array
    {
        return $request->user()->tokens()
            ->latest('id')
            ->get()
            ->map(fn ($token) => [
                'id' => (string) $token->id,
                'name' => $token->name,
                // RULE(time): ISO 8601 out, the client formats it.
                'created_at' => $token->created_at?->toIso8601String(),
                'last_used_at' => $token->last_used_at?->toIso8601String(),
            ])
            ->all();
    }
}
