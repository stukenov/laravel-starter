# The activity stream waits on the database socket rather than asking again

A live feed built on a timer asks the database for new rows every second
whether or not any arrived — thirty viewers is thirty queries a second
that almost always return nothing.

Instead `PostgresListener` issues `LISTEN`, then hands the raw socket to
`stream_select` and blocks. The process consumes nothing until Postgres
writes to it. A `NOTIFY` from a trigger wakes it, and only then does it
read.

Two consequences that are easy to trip over and impossible to see in a
green test run: `NOTIFY` is delivered at COMMIT, so nothing inside an
uncommitted transaction is ever heard (see
[[notify-arrives-only-after-commit]]), and `pg_connect()` hands back an
existing connection unless told otherwise, so somebody's `pg_close()` can
end a wait that is in progress (see
[[pg-connect-hands-back-an-existing-connection]]).

Related: [[sse-connections-live-25-seconds]],
[[a-stream-has-to-say-something-to-count-as-open]]
