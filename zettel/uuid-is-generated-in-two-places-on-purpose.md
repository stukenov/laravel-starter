# UUIDs are generated in two places, and that is deliberate

I first did it "cleanly": a `uuidv7()` default on the database and model-side
generation switched off, so exactly one thing issues the key.

That was worse. Eloquent inserts a row without an identifier, the database
supplies one, and **the model never finds out**: it does not re-read the row
and returns junk for the key. In tests two different users came back with the
same empty identifier, and a key lookup compared a uuid against the number 19.

Both mechanisms are needed:

- **`HasUuids` on the model** — in Laravel 13 it returns `Str::uuid7()`, so
  the model sets the key itself and knows it immediately;
- **the database default** — insurance for rows inserted around Eloquent: by
  a migration, a script, by hand in psql.

No conflict: when the model supplies a key, the default is not used.

The lesson is broader than this case: **"there must be one source of truth"
is not always right.** Here two generators solve two different problems, and
removing either breaks its own scenario. See
[[postgres-18-ships-uuidv7-built-in]].
