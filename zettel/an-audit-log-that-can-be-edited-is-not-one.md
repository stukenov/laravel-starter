# An audit log that can be edited is not an audit log

The activity screen is read-only by construction: there is no store, no
update, no destroy, and a test asserts that no route under `activity`
accepts anything but GET. Entries are written by the application and
removed only by the retention command.

Three decisions worth keeping:

**jsonb, not json.** On PostgreSQL `json` stores the raw text and cannot
take a GIN index, so every filter over the payload becomes a full scan.
The published spatie migration uses `json`; ours is rewritten. This is
where the "maximum PostgreSQL" decision finally earns its keep.

**The password is never logged.** A hash is useless in a log and dangerous
in a backup of one, so the model logs only name, email, locale and
timezone.

**Changes are flattened before they leave the model.** The raw shape is two
parallel objects, `old` and `attributes`, which is unreadable on screen and
forces every consumer to zip them itself. `toFeedItem()` turns it into one
row per changed field — and it is the same shape the SSE stream will push,
so the two cannot drift apart.

One truthfulness bug surfaced immediately: the log said `locale: null → kk`
for a user whose locale had always been `ru`. The cause was the recurring
trap — the factory left the column to the database default, so the model
held null in memory and the diff was computed against that. A log that
reports a change which never happened is worse than no log. See
[[uuid-is-generated-in-two-places-on-purpose]].
