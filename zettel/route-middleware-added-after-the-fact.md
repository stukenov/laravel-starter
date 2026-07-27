# Adding middleware to somebody else's route

Fortify declares `register.store` itself and offers no hook, so the
honeypot middleware is attached to the route afterwards, in
`AppServiceProvider`. Two traps, both silent.

It has to wait for `booted`: this provider boots before the route files
are loaded, and there is nothing to find any earlier.

And it has to scan `Route::getRoutes()->getRoutes()` rather than call
`getByName()`. The name index is rebuilt in a `booted` callback of its
own, registered after this one — so the lookup returns null, `?->` does
nothing, and registration ships unguarded with no sign anything failed.

That is what happened. The tests said so; nothing else would have.

Related: [[a-honeypot-instead-of-a-captcha]]
