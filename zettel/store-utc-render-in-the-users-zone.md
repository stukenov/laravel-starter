# Store time in UTC, let the user's profile decide how it reads

Every timestamp column is `timestamptz` and the app runs in UTC. Each user
carries a `timezone`, defaulting to `Asia/Almaty`.

That forces one hard rule on the front end: **the server never sends a
formatted date.** It sends an instant in ISO 8601 and the client formats it
through the built-in `Intl.DateTimeFormat`.

A useful side effect: zero front-end dependencies. No moment, no dayjs, no
date-fns — which also satisfies the "nothing external" rule. See
[[nothing-external-at-render-time]].

The alternative, pinning everything to Asia/Almaty, is simpler right up to
the first user in another zone, after which every output needs rework.

The mechanism has a hidden precondition — see
[[timestamptz-needs-utc-on-the-connection]].
