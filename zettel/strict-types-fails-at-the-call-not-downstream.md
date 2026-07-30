# strict_types moves the failure to the line that was wrong

Every PHP file now opens with `declare(strict_types=1)`. Without it PHP
coerces at each boundary: `"12abc"` reaching an `int` parameter becomes
`12` and the rest is silently gone, `null` becomes `0`, `"0"` becomes
`false`. Nothing errors. The place that finds out is somewhere
downstream of the place that was wrong, which is the expensive kind of
bug — the stack trace points at the victim.

It is enforced mechanically rather than by remembering. Pint's
`declare_strict_types` rule writes the line, `pint --test` refuses a file
without it, and `composer test` runs `lint:check` first. 199 files got
the line in one pass and the whole suite stayed green — the declaration
changes nothing about code that was already passing the right types.

`artisan` gets it by hand: it has no `.php` extension, so Pint never
looks at it. The generator's stubs get it too, or `make:feature` would
emit files that fail the lint check the moment they are created.

Related: [[larastan-level-10-is-where-mixed-starts-speaking]],
[[a-fix-to-the-example-is-not-a-fix-to-the-generator]]
