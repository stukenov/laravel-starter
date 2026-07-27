# The spatie permission tables need rewriting for uuid keys

The published migration uses `$table->id()` for roles and permissions and
`unsignedBigInteger` for the morph key. That morph key points at `users.id`,
which is a uuid here, so the pivot cannot bind at all.

Rewritten rather than patched: mixing key types across one schema is the
kind of thing that only surfaces when a relation is finally used, usually
far from the migration.

Three separate traps followed, all the same shape — **something did not
know about the uuid**:

1. Spatie's own `Role` and `Permission` models assume an auto-increment
   key. Without `HasUuids` the model never learns the uuid the database
   issued and hands a `lastInsertId` artefact to the pivot query, which
   fails with "no operator matches uuid and integer". Subclassed in
   `app/Models` and pointed at through `config/permission.php`.

2. `config/permission.php` refers to the models by short name, so the
   substitution has to happen in its `use` statements. Replacing the
   fully-qualified strings does nothing.

3. The seeder imported spatie's models directly, bypassing the config
   entirely — so it kept using the non-uuid classes no matter what the
   config said. That one cost the most time, because the schema and the
   config were both already correct.

See [[uuid-is-generated-in-two-places-on-purpose]].
