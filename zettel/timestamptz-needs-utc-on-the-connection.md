# timestamptz only works right when the connection runs in UTC

"All dates in `timestamptz`" looked self-sufficient. It has a hidden
precondition without which it does the opposite.

The mechanism:

1. PHP sends a date as a string **without an offset** — `2026-07-27 09:48:50`.
2. Postgres reads that string in **its session timezone**.
3. Postgres.app takes the system zone by default — here, `Asia/Almaty`.
4. An instant meant as 09:48 UTC is stored as 04:48 UTC.

Five hours lost. App in UTC, column `timestamptz`, everything "correct" —
and the instant is wrong.

It surfaced in the most opaque way possible: password reset tokens were born
already expired, and the user saw "This password reset token is invalid".
Nothing in that message mentions time zones.

One line fixes it — `'timezone' => 'UTC'` on the `pgsql` connection. The
Laravel connector issues `set time zone` on connect.

General lesson: **`timestamptz` protects against losing an offset, not
against having the wrong one substituted.** Both ends — app and connection —
must be UTC. Locked down by two tests: one checks the connection zone, the
other writes an instant and reads it back. See
[[store-utc-render-in-the-users-zone]].
