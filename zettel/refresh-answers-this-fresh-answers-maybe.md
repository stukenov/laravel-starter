# `refresh()` answers `$this`, `fresh()` answers `?static`

Both reload the row. `fresh()` builds a new model and its signature
admits the row might be gone, so `$user->fresh()->load('roles')` is a
method call on a maybe-null — an error at level 10 and, in principle, a
500 if somebody deleted the record between the update and the read.

`refresh()` reloads the same instance and returns `$this`. Same query,
same freshness, no null branch to answer. Three call sites changed —
`UserController::update`, `User::syncRolesAudited` and the generator's
controller stub — and none of them wanted a second object anyway.

The `make:feature` stub mattered most: it emits `->fresh()->toPanelItem()`
into every generated controller, so leaving it would have meant every
new entity arriving with a level 10 error and nobody knowing why.

Related: [[larastan-level-10-is-where-mixed-starts-speaking]],
[[a-fix-to-the-example-is-not-a-fix-to-the-generator]]
