# Models

- Key is `uuid`. `HasUuids` sets it on the model, the database default
  `uuidv7()` covers inserts that bypass Eloquent. Both are needed.
- Every timestamp is `timestamptz`; the app runs in UTC.
- Logic lives here, in form requests and in policies. There are no
  repositories and no service layer — ArchitectureTest enforces it.
- Queries belong here too: controllers may not touch the database.
- Identifiers are strings in types, never numbers.

Full rules: `AGENTS.md` in the repo root.
