# The generator's contract is a slice whose tests are green on first run

`make:feature Post --fields=title:string,body:text` writes fifteen files:
migration, model, factory, policy, two form requests, controller, route
registration, two React pages, translations in three locales and tests at
two levels.

The acceptance criterion is not "files appeared" but **21 tests passing
with no edits at all** — 13 backend, 8 rendering. That was verified by
generating a scratch entity, running the suite and deleting it again.

One wrinkle worth knowing: backend tests return 500 until `npm run build`
has run. The blade template eagerly includes the page component for the
current route, so a page absent from the Vite manifest takes the request
down. The generator prints the required order.

Two bugs surfaced while building it, both mine:

- the acceptance check counted only `expect(` and ignored Laravel's
  `assert*` helpers, so it measured the wrong thing and failed on a
  perfectly good generated suite;
- the cleanup routine stripped generated routes with a regex ending in
  `\n`. That pattern consumes the newline, so consecutive matching lines
  are skipped and half the block survives. Line-by-line filtering fixed it.

See [[a-generator-without-real-tests-is-not-a-generator]].
