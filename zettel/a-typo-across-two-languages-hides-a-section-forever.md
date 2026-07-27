# A permission name is a string in TypeScript and a constant in PHP, and they cannot check each other

The navigation names the permission each section needs as a plain string:
`ability: 'users.view'`. The permissions themselves are PHP constants in
`App\Support\Access\Ability`.

Write `users.veiw` and nothing errors. The name matches nothing in
`auth.abilities`, so `useCan()` answers false, so the section is absent —
for the people who should see it, permanently, with nothing anywhere
saying why. The most likely diagnosis is "the permission is not granted",
which sends somebody into the roles page for an afternoon.

This is a seam between two languages, and a seam gets a test rather than a
convention: `tests/Feature/NavigationAbilitiesTest.php` compares the names
in the sidebar against `Ability::all()`.

The general form: wherever a value crosses from one language to another as
a bare string, neither compiler is watching, and only a test spans both.

Related: [[permissions-are-code-roles-are-data]],
[[route-nobody-can-reach]], [[a-rule-backed-by-a-test-is-a-rule]]
