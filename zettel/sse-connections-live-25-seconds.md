# An SSE connection lives 25 seconds — that is what solves the worker problem

The main objection to SSE on PHP-FPM: every open connection holds a worker.
A dozen viewers and the site is down.

The fix is not a different server but a bounded connection lifetime. It
closes after roughly 25 seconds, the browser's `EventSource` reconnects on
its own and sends `Last-Event-ID`. Our events table carries a sequence
number, so the stream resumes exactly where it broke.

Inside the connection the process does not poll: it sleeps on
`stream_select()` over the socket and wakes on `NOTIFY`. Verified —
`pg_connect`, `pg_socket` and `pg_get_notify` all exist in PHP 8.5.5.

So the number of busy workers has a ceiling and nothing extra needs
installing: FrankenPHP is not in brew, Swoole is not compiled, RoadRunner is
not present.
