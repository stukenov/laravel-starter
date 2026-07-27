# Permissions belong in code, roles belong in the panel

The owner asked for full CRUD over users, roles and permissions. Building
all three the same way turns out to be wrong, and the reason is worth
keeping.

**A permission means something only because code checks it.** Creating
`invoices.approve` through a form produces a name that nothing enforces —
a checkbox with no effect anywhere. So permissions are declared in
`App\Support\Access\Ability` and seeded from there.

**Which permissions a role carries is a business decision**, and that does
belong in the panel: an editor gaining the right to publish is an ordinary
Tuesday, not a deployment.

So the split is: permissions read-only in the UI, roles fully editable.

`RoleSeeder` is idempotent and runs on every deploy, so adding a constant
in code is enough — nobody has to remember to create the row. Tests seed it
as well, because roles are infrastructure rather than fixtures: without
them every policy denies everything and each failure points at the wrong
place.

One rule has no exception even for an administrator: **the admin role
cannot be deleted.** Remove it and nobody can grant it back, and the panel
locks everyone out permanently. See [[a-rule-backed-by-a-test-is-a-rule]].
