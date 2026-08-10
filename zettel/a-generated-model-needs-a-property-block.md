# A generated model needs a @property block, or static analysis stops

Every hand-written model here carries one. The generator wrote none, and the
first `make:feature` on a fresh clone broke `composer types:check`:

```
Cannot call method toIso8601String() on string.
```

The call is in the generator's own `toPanelItem()` — `$this->due_at?->toIso8601String()`
— so any entity with a date failed the moment it was created. The fix had to
be typed out by hand, per entity, every time.

It is written from the same field list the migration is, so the two cannot
drift: `integer` → `int`, `boolean` → `bool`, `date`/`datetime` → `Carbon`,
`file`/`image` → `StoredFile`, `belongsTo` → a `string` uuid on the foreign
key, everything else `string`, and `|null` when the field is optional.

The imports go with it. Naming `Carbon` in a docblock without importing it
makes the analyser look for `App\Models\Carbon`, which is a different error
about the same line.

**What is not a defect, and was mistaken for one:** the factory leaves a
`belongsTo` key null. That is deliberate and the reason is in the code — a
factory that always fills the key makes "no category" untestable, and the
column is nullable by design. It only became a problem in a project that
made the relation required, which is a documented deviation, not a bug here.

See also [[the-first-field-is-not-the-searchable-one]].
