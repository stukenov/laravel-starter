<?php

use App\Support\Access\Ability;
use Illuminate\Support\Facades\File;

/**
 * RULE(access): permissions are declared in code — AGENTS.md.
 *
 * The navigation names the permission each section needs, as a plain
 * string in a TypeScript file. PHP constants and TS strings cannot check
 * each other, so a typo — `users.veiw` — hides that section from
 * everybody, permanently and silently. Nothing errors: the name simply
 * never matches anything in `auth.abilities`, and the section is absent
 * for people who should see it.
 *
 * This is the seam between the two languages, so it gets a test rather
 * than a convention.
 */
function navigationAbilities(): array
{
    $sidebar = File::get(resource_path('js/components/app-sidebar.tsx'));

    preg_match_all("/ability: '([^']+)'/", $sidebar, $matches);

    return $matches[1];
}

// ── Correct data ─────────────────────────────────────────────────────

it('names only permissions that exist', function () {
    $unknown = array_values(array_diff(navigationAbilities(), Ability::all()));

    expect($unknown)->toBe([]);
});

it('finds abilities in the navigation at all', function () {
    // A regex that matches nothing passes the assertion above.
    expect(navigationAbilities())->not->toBeEmpty();
});

it('gates every section that has a policy behind one', function () {
    // The panel home needs none — everyone signed in may see it. Every
    // other section maps to a permission, or somebody sees a link that
    // answers 403.
    $sidebar = File::get(resource_path('js/components/app-sidebar.tsx'));

    preg_match_all("/title: '([^']+)'/", $sidebar, $titles);

    $sections = array_filter($titles[1], fn (string $t) => $t !== 'nav.dashboard');

    expect(count(navigationAbilities()))->toBe(count($sections));
});

// ── Wrong data ───────────────────────────────────────────────────────

it('would notice a misspelled permission', function () {
    // The check itself, checked.
    expect(in_array('users.veiw', Ability::all(), true))->toBeFalse();
});

// ── Empty data ───────────────────────────────────────────────────────

it('has permissions to compare against', function () {
    expect(Ability::all())->not->toBeEmpty();
});
