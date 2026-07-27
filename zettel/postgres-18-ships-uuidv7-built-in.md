# PostgreSQL 18 ships uuidv7 as a function — no extension needed

Verified against the live database: Postgres.app 18.4 answers
`select uuidv7()`. Neither `uuid-ossp` nor `pgcrypto` is required.

That changes how primary keys work. UUIDv7 sorts by creation time, so
inserts land at the end of the index instead of the middle — the main
objection to UUIDv4 disappears.

The default is set **on the database**, not in the model, so a row inserted
outside Eloquent — by a migration, a script, or by hand in psql — still gets
an identifier.

`citext`, `pg_trgm`, `btree_gin`, `unaccent` and `pgcrypto` were all
confirmed available at the same time.

Two generators are needed, not one — see
[[uuid-is-generated-in-two-places-on-purpose]].
