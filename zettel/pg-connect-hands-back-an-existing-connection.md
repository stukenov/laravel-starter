# pg_connect hands back an existing connection

Given a connection string it has seen before, `pg_connect()` returns the
already-open connection instead of making a new one. Two unrelated parts
of the app asking for the same database therefore share one connection —
and one of them calling `pg_close()` closes it for both.

For a listener this is fatal: `LISTEN` holds the connection for the whole
wait, and an unrelated `pg_close()` ends the wait with
"connection has already been closed".

`PostgresListener` passes `PGSQL_CONNECT_FORCE_NEW`. Any other place that
opens a libpq connection by hand has to do the same.

Related: [[notify-arrives-only-after-commit]]
