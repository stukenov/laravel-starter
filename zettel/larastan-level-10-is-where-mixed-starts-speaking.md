# Larastan level 10 is where `mixed` starts speaking

Level 7 to level 10 was 149 errors, and almost none of them were exotic.
Level 10 is the level that stops treating `mixed` as compatible with
everything, and Laravel hands out `mixed` constantly: `config()`,
`validated()`, `pluck()`, `getRawOriginal()`, `max()`, the query
builder's rows, a middleware's `$next`.

Grouped, the 149 were five things:

- config read and used as a string, an int or an array without ever
  being one — see [[config-answers-mixed-and-so-must-the-reader]];
- `$request->user()` dereferenced as though the type said `User` — see
  [[the-signed-in-person-is-nullable-to-the-analyser]];
- `$next($request)` returning `mixed` because `Closure` says nothing;
  fixed with the `@param Closure(Request): Response` the framework's own
  middleware carries;
- `fresh()` answering `?static` where `refresh()` answers `$this`;
- a macro with no return type — [[a-macro-has-no-return-type]].

No baseline, no `@phpstan-ignore`, no inline `@var`. Every one was
narrowed with `is_string()`, `instanceof` or a guard that throws, which
is the difference between saying what was assumed and hiding it.

Related: [[strict-types-fails-at-the-call-not-downstream]]
