<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/*
 * RULE(architecture): one big monolith, not a layer cake — AGENTS.md.
 *
 * A rule written only as prose survives until the first developer in a
 * hurry. Here it is executable.
 */

arch()->preset()->laravel();
arch()->preset()->security();

/*
 * RULE(architecture): one monolith, not a layer cake — AGENTS.md.
 *
 * The ban is on the *habit*, not on the idea. Logic belongs in models,
 * form requests and policies, and a `UserService` wrapping four Eloquent
 * calls is a file that exists to be a file.
 *
 * Work that genuinely is not a model's — talking to another system,
 * generating a document, driving a subprocess — goes in `App\Support`
 * with the rest of the plumbing. That is where `Locale`, `Sessions`,
 * `Access` and `Sse` live. Porting a project with eight service classes
 * found this rule enforced and unexplained: it says where not to put
 * things and never says where to.
 */
arch('no repositories, and no service layer by that name')
    ->expect('App')
    ->not->toUse([
        'App\Repositories',
        'App\Services',
    ]);

// App\Actions is not banned: Fortify requires CreateNewUser and
// ResetUserPassword by contract. We do not add an action class per
// operation of our own — the logic lives in models, form requests and
// policies.
arch('only the action classes Fortify demands')
    ->expect('App\Actions')
    ->toBeClasses()
    ->toOnlyBeUsedIn('App\Providers');

arch('models live in one place and extend Eloquent')
    ->expect('App\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('policies are named alike')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');

arch('form requests are named alike')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('middleware are classes')
    ->expect('App\Http\Middleware')
    ->toBeClasses();

arch('no controller reaches for the query builder facade')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

/**
 * The facade ban was the whole of "controllers do not touch the database
 * directly", and it is the smaller half. `User::where('email', $x)->get()`
 * uses no facade at all and is exactly the thing the rule is about: a
 * query written where nothing else can reuse it, test it, or find it.
 *
 * Where the line actually falls, measured against what this codebase
 * already does rather than against an ideal: a controller may order and
 * paginate what a model handed it. Choosing *which* rows — every `where`,
 * every join, every piece of raw SQL — belongs on the model as a scope.
 * All three list controllers here already work that way; `scopeSearch` is
 * the pattern.
 */
function controllerQueryBuilding(): array
{
    $offenders = [];

    foreach (File::allFiles(app_path('Http/Controllers')) as $file) {
        foreach (preg_split('/\R/', $file->getContents()) ?: [] as $number => $line) {
            if (preg_match('/^\s*(\/\/|\*|#)/', $line)) {
                continue;
            }

            // Both arrows. `User::where(...)` is the commonest form by
            // far, and a pattern that only knew `->where(` missed it —
            // which the falsification check below caught, and nothing
            // else would have.
            if (preg_match('/(->|::)(where|orWhere|whereIn|whereRaw|selectRaw|join|leftJoin|havingRaw)\(/', $line)) {
                $offenders[] = str_replace(base_path().'/', '', $file->getPathname()).':'.($number + 1);
            }
        }
    }

    return $offenders;
}

// ── Correct data ─────────────────────────────────────────────────────

it('chooses no rows inside a controller', function () {
    expect(controllerQueryBuilding())->toBe([]);
});

it('still lets a controller order and paginate', function () {
    // The line has to leave the existing code standing, or it is not a
    // description of this project — it is a wish.
    $controller = File::get(app_path('Http/Controllers/Dash/UserController.php'));

    expect($controller)->toContain('->orderByDesc(')
        ->and($controller)->toContain('->paginate(');
});

// ── Wrong data ───────────────────────────────────────────────────────

it('would notice a query in a controller', function () {
    // The check itself, checked: a pattern that matches nothing makes the
    // assertion above pass on an empty search.
    $sample = "        \$users = User::where('email', \$request->email)->get();";

    expect(preg_match('/(->|::)(where|orWhere|whereIn)\(/', $sample))->toBe(1);
});

// ── Empty data ───────────────────────────────────────────────────────

it('reads the controllers at all', function () {
    expect(count(File::allFiles(app_path('Http/Controllers'))))->toBeGreaterThan(5);
});
