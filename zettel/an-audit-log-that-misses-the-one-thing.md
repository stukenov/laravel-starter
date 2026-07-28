# The log recorded everything except who granted access

`logOnly(['name', 'email', 'locale', 'timezone'])` watches columns. A role
is not a column — it is a row in a pivot table — so `syncRoles()` fired no
model event and wrote nothing. Granting somebody administrator left the
activity log exactly as it was: measured, two entries before and two after.

The gap was worse for being surrounded by completeness. Creating, editing
and deleting a person were all recorded, so the log looked finished, and
the single action worth auditing was the one it had never seen.

Two things had to be right, and only the first is obvious. `withChanges()`
writes to `attribute_changes`, which is what the panel reads;
`withProperties()` writes to `properties`, which it does not. Sent to the
wrong column the entry appears with nothing in it — worse than no entry,
because it reads as a change that had no content.

The general form: automatic instrumentation covers what it can see, and
what it cannot see leaves no gap you can notice. Anything stored outside
the model's own columns needs recording by hand.

Related: [[an-audit-log-that-can-be-edited-is-not-one]],
[[permissions-are-code-roles-are-data]], [[route-nobody-can-reach]]
