# A macro has no return type, and a PHPStan stub cannot give it one

`Route::inertia('/', 'home')->name('home')` fails at level 10: the macro
is a closure registered by inertia-laravel, a closure declares no return
type, so the analyser sees `mixed` and `->name()` is a call on nothing.
Which also means nothing was checking that the route got named at all,
and naming is the entire reason those lines are chained.

Two attempts at a stub file failed, and the failure is worth recording:

- A stub declaring `inertia()` on `Illuminate\Routing\Router` did
  nothing. PHPStan stubs supply **PHPDoc for symbols that exist**; they
  cannot add a method.
- A stub adding `@method static Route inertia(...)` to the `Route`
  facade did worse than nothing: it replaced the facade's own class
  docblock, so `Route::middleware()` and `Route::prefix()` became
  undefined — eleven new errors, and `inertia` was still `mixed`,
  because larastan resolves a facade through the real class rather than
  through its `@method` tags.

So the type is established in our own code instead:
`App\Support\Http\Page::at()` calls the macro, checks the result is a
`Route`, and returns it typed. It delegates rather than reimplementing —
if inertia changes what the macro registers, we inherit the change.
Route files now read `Page::at('/', 'home')->name('home')`.

Related: [[larastan-level-10-is-where-mixed-starts-speaking]]
