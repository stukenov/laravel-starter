# A generated model declared no casts, so every entity with a date answered 500

`make:feature` wrote `toPanelItem()` with `$this->due_on?->toIso8601String()`
and never wrote a `casts()` method. Postgres returns a `date` column as the
string `2026-07-28` and a `timestamptz` as `2026-07-27 23:22:45+00`; calling
a Carbon method on a string is a fatal error. The list page of every
generated entity with a date field answered 500.

Ten of the thirteen backend tests the generator emits failed on such an
entity — against a stated acceptance criterion that they pass with no edits.
See [[promise-and-reality-gap]].

**Why nobody noticed.** A factory holds the Carbon it was handed until the
row is read back. In memory the object looks right; only a request that
re-queries sees what the database actually returns. Every check that had
been written asked the object in memory.

The other types need nothing: pgsql hands PHP an int for `integer` and a
bool for `boolean`, so their TypeScript types were honest already. Casting
them anyway would imply the driver cannot be trusted.

The guard in `MakeFeatureTest` reads both sides out of the generated file —
what is called as a Carbon, what is declared as one — rather than listing
field names, so a seventh field type cannot leave it behind.

Related: [[route-nobody-can-reach]], [[dates-belong-to-the-reader]]
