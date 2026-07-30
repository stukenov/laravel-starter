# The signed-in person is nullable, whatever the middleware knows

`$request->user()` is `?User`. Every action that reads it sits behind
`auth`, so null is not a state the application can reach — but the
middleware knows that and the type does not, and level 10 says so at
every call site. Twenty-odd errors were this one fact.

Two answers, one per surface:

- A controller calls `currentUser()` on the base controller, which
  narrows once and throws `AuthenticationException` if it ever really is
  null. An authentication failure, not a 500: if the impossible happened,
  the honest answer to the visitor is "sign in".
- A form request's `authorize()` says `$this->user()?->can(…) ?? false`.
  For a guest, "not allowed" is not a fallback — it is the right answer.

Neither is an assertion or a cast. And the controller version paid for
itself immediately: `ProfileController::update` called `$request->user()`
five times in nine lines, and now calls it once.

The same shape already existed in the codebase — `target()` on the
update requests, narrowing a route-bound model with a `RuntimeException`
for the case binding rules out. Consistency was free.

Related: [[larastan-level-10-is-where-mixed-starts-speaking]],
[[a-list-and-a-form-are-different-screens]]
