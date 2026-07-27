# A permission matrix is a page; a role name is a dialog

The list-versus-form rule says a short form may live in a dialog and a long
one gets its own page. Roles show both halves at once.

**Creating a role is one field** — a name. That is a dialog: opening a whole
page to type six characters and come back is worse for everyone.

**Editing a role is the permission matrix** — one row per ability, with the
technical name shown alongside the human label. That is a page. Cramming it
into a dialog means scrolling inside a scroll, and the row you are looking
for is always the one hidden.

So `/dash/roles` lists and creates, `/dash/roles/{id}` edits. Same entity,
two different shapes, chosen by the size of the form rather than by symmetry.

The technical name stays visible next to the label on purpose: the label is
for choosing, the name is for finding it in code when something does not
work. See [[permissions-are-code-roles-are-data]].
