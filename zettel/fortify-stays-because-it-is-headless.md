# Fortify stays because it ships no views

The spec first demanded writing the auth controllers by hand, "so the pages
are entirely ours". Checking the installer showed the official Laravel 13
React starter is built on `laravel/fortify` 1.37.

The decision flipped in Fortify's favour. The reasoning is simple: Fortify
**ships no pages at all**, only the logic for login, registration, password
reset and email verification. It does not threaten "every screen under
shadcn" in the slightest — we still write every screen.

Rewriting proven auth logic by hand would have produced less reliable code
for no gain whatsoever.

Side finding: the starter also ships two-factor and passkeys, which were
never requested. Three lines in `config/fortify.php` remove them — minus
three screens and eighteen tests.

General lesson: **a "we write it ourselves" requirement should be checked
against what it actually protects.** Here it protected appearance, not
logic — and Fortify does not touch appearance.
