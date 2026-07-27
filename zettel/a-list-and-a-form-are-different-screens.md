# A list and a create form are different screens, always

Owner's rule: one page, one job. A list shows records. Creating and editing
live on their own page or in a dialog.

Standard shape: `/dash/users` lists, `/dash/users/new` creates,
`/dash/users/:id` edits.

Choosing between a page and a dialog is decided by the size of the form, not
by taste. Name, email and role fit in a dialog. A role's permission matrix
is a large form and gets `/dash/roles/:id`.

The owner noted separately that dumping a list and a form on one route reads
as AI-generated: real products do not do it.
