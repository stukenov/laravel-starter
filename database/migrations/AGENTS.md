# Migrations

- Primary key: `$table->uuid('id')->primary()` plus
  `alter table ... set default uuidv7()`. Never `$table->id()`.
- Dates: `timestampTz()` and `timestampsTz()`, never `timestamp()`.
- Email is `citext` — case-insensitive uniqueness without any code.
- Extensions go in the first migration. `uuidv7()` needs none: it is
  built into PostgreSQL 18.
- A foreign key must match the type of the key it points at.

Full rules: `AGENTS.md` in the repo root.
