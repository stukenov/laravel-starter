<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\Access\Ability;
use Database\Seeders\RoleSeeder;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));

        return $this->page($request, 'dash/users/index', [
            'users' => User::search($search)
                // Without this the roles column is one query per row.
                ->with('roles:id,name')
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString()
                // The model says what may leave it — AGENTS.md. Built
                // here in a closure it would be a shape only the panel
                // could rely on, and the API shares these actions.
                ->through(fn (User $user) => $user->toPanelItem()),
            'search' => $search,
            // 419 names, 8 KB of JSON, and the list changes when the
            // tzdata package does — which is to say a few times a year.
            // Rebuilding it per request is pure waste.
            'timezones' => Cache::remember(
                'timezones',
                now()->addDay(),
                fn () => DateTimeZone::listIdentifiers(),
            ),
            'roles' => Role::orderBy('name')->pluck('name')->all(),
            // Handing out a role is a different decision from editing a
            // name, so the form only offers it to somebody who may make
            // that decision.
            'canManageRoles' => $request->user()->can(Ability::MANAGE_ROLES),
        ], 'users');
    }

    public function store(StoreRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $roles = $this->rolesFrom($request, $data);

        $user = User::create($data);

        if ($roles !== null) {
            $user->syncRolesAudited($roles, $request->user());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('entities/users.created')]);

        // 201 and the record: an API caller cannot work out the id it
        // just created any other way.
        return $this->saved($request, $user->load('roles')->toPanelItem(), 201);
    }

    public function update(UpdateRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        // An empty password field means "keep the current one".
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $roles = $this->rolesFrom($request, $data);

        $user->update($data);

        if ($roles !== null) {
            $this->assign($request, $user, $roles);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('entities/users.updated')]);

        return $this->saved($request, $user->fresh()->load('roles')->toPanelItem());
    }

    /**
     * The roles from the form, or null when they must not be touched.
     *
     * An absent `roles` key is ambiguous on its own: unchecked boxes send
     * nothing, so "remove every role" and "this form has no roles field"
     * arrive identically. Editing a name would then quietly strip
     * somebody's access. Hence the hidden marker — the field says it was
     * on screen, and only then is an empty list taken to mean empty.
     *
     * Removed from the data either way: `update()` would otherwise try to
     * write a `roles` column that does not exist.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>|null
     */
    private function rolesFrom(Request $request, array &$data): ?array
    {
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        if (! $request->boolean('roles_submitted')) {
            return null;
        }

        return $request->user()->can(Ability::MANAGE_ROLES) ? $roles : null;
    }

    /**
     * RULE(access): nobody may lock themselves out — AGENTS.md.
     *
     * Taking the admin role off your own account is a one-way door: the
     * panel closes on the next request and the only way back is the
     * command line. Deleting yourself is already refused by the policy for
     * the same reason; this is the same mistake made more quietly.
     *
     * @param  array<int, string>  $roles
     */
    private function assign(Request $request, User $user, array $roles): void
    {
        $isSelf = $request->user()->id === $user->id;
        $losingAdmin = ! in_array(RoleSeeder::ADMIN, $roles, true)
            && $user->hasRole(RoleSeeder::ADMIN);

        if ($isSelf && $losingAdmin) {
            $roles[] = RoleSeeder::ADMIN;

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('entities/users.cannot_demote_self'),
            ]);
        }

        $user->syncRolesAudited($roles, $request->user());
    }

    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('entities/users.deleted')]);

        return $this->saved($request);
    }
}
