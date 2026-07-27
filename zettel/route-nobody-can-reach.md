# A route nobody can reach is a feature nobody has

Three times in one night the same defect shipped: a controller action
complete, its route registered, a backend test asserting it behaved
correctly — and nothing on any screen able to call it.

- Roles could be defined and never assigned to anybody.
- A list of 37 records showed 20, with no control to reach the rest.
- A record could be created and edited and never deleted.

All three passed the whole suite. Every test asked whether the endpoint
behaved; none asked whether anything could call it. The suite was not
weak — it was answering a different question, confidently.

`tests/Feature/ReachableTest.php` is the mechanical half: a writing route
under `dash.` must be named by a page. It cannot tell whether a button is
visible or a person would find it — that is Playwright's job — but it
catches an action shipped with no caller at all, which is what actually
kept happening.

The general form: a test that checks the back of a thing proves nothing
about whether the front exists.

Related: [[the-promise-in-the-docblock-was-never-true]],
[[a-typo-across-two-languages-hides-a-section-forever]],
[[three-levels-of-tests]]
