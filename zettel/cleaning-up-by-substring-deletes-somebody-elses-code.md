# Cleaning up by substring deletes somebody else's code

`MakeFeatureTest` exercises the generator for real: it writes models,
controllers and pages, and appends to the shared `routes/dash.php`,
`routes/api.php`, `Ability.php` and the sidebar. There is nothing to
substitute for those files, so it edits them and strips its own lines
afterwards.

It stripped them by substring:

```php
! str_contains($line, "{$studly}Controller")
```

One of the generated entities is `Note`. A project built on this starter had
a hand-written `ProjectNoteController` — which contains `NoteController` —
and the cleanup deleted its route **and its import**, from a file under
version control, in the middle of a run about something else.

What made it expensive was the shape of the failure. The route line was
visibly present in the file; three tests said the route did not exist; the
line vanished again between runs. The first theory was that the formatter
had eaten it. The truth only came out of an experiment: put the line back,
run one generator test, look.

Fixed with word boundaries. `\bNoteController\b` does not match inside
`ProjectNoteController`.

Two tests hold both ends now: a planted neighbouring controller survives,
and a genuinely generated route is still removed. A cleanup that keeps
everything is not a cleanup.

**The general rule: comparing class names by substring is always wrong.**
`Foo` lives inside `ProjectFoo`, `BarFoo`, `FooBar`. If a routine deletes
code, it has to match on a boundary.

And the operational one, paid for twice: **after a killed or failed test
run, check `git status` before doing anything else.** An interrupted run
leaves whole generated entities behind — twenty-six files on the occasion
that proved it, visible only because the build failed.

See also [[the-generator-suite-runs-on-its-own]].
