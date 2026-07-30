<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRequest;
use App\Http\Requests\Role\UpdateRequest;
use App\Models\Role;
use App\Support\Access\Ability;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return $this->page($request, 'dash/roles/index', [
            // Paginated like every other list. Roles are few in most
            // projects and unbounded in principle: a list with no ceiling
            // is a page that works until the day it does not.
            'roles' => Role::withCount(['permissions', 'users'])
                ->orderBy('name')
                ->paginate(30)
                ->through(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions_count' => $role->permissions_count,
                    'users_count' => $role->users_count,
                    // The admin role is protected from deletion; the UI
                    // needs to know so it does not offer the button.
                    'is_protected' => $role->name === RoleSeeder::ADMIN,
                ]),
        ], 'roles');
    }

    /**
     * The permission matrix is a large form, so it gets its own page
     * rather than a dialog. See the list-versus-form rule in AGENTS.md.
     */
    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        return Inertia::render('dash/roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_protected' => $role->name === RoleSeeder::ADMIN,
            ],
            'abilities' => Ability::all(),
            'granted' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->permissionsFrom($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('entities/roles.created')]);

        return back();
    }

    public function update(UpdateRequest $request, Role $role): RedirectResponse
    {
        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($this->permissionsFrom($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('entities/roles.updated')]);

        return back();
    }

    /**
     * The permission names the form submitted.
     *
     * `validated()` answers `mixed`. The rules guarantee an array of
     * names from a closed list, but a rule is not a type — so the shape
     * is established once here rather than at both writes.
     *
     * @return array<int, string>
     */
    private function permissionsFrom(StoreRequest|UpdateRequest $request): array
    {
        $permissions = $request->validated('permissions', []);

        return array_values(array_filter(
            is_array($permissions) ? $permissions : [],
            is_string(...),
        ));
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('entities/roles.deleted')]);

        return to_route('dash.roles.index');
    }
}
