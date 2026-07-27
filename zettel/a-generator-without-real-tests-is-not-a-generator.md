# A generator that emits stub tests counts as unfinished

Rails' speed rests on `rails g scaffold`. Here one entity needs fifteen
files: migration, model, factory, policy, two form requests, controller,
routes, React pages, TypeScript types, translations in three locales and
tests at three levels.

An hour of mechanical work per entity — that is where the speed goes, and
that is what `make:feature` must close.

The acceptance criterion is strict and checkable: after
`php artisan make:feature Post title:string`, the commands
`php artisan test`, `npx vitest run` and `npx playwright test` all pass
**immediately, with no edits**. Nine scenarios that genuinely work.

The trap here is generating skipped or empty tests. That is worse than
generating none: **a stub creates the appearance of coverage, and the next
person believes it.** An empty test protects nothing while looking like
protection.

Which is also why the generator is written after the first working slice
exists, not before. It is extracted from code proven to work, not from a
guess about how it will look.
