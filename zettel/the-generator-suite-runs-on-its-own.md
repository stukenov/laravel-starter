# The generator suite runs on its own

`MakeFeatureTest` took eleven of the twelve minutes a full run cost. The
ordinary edit-test loop stopped being usable, and the usual answer to a slow
loop is to stop running the tests.

It is now its own testsuite:

```
php artisan test        the fast suite — 42 seconds
composer test:generator the generator, on purpose
composer test           both, plus lint and static analysis
```

**`defaultTestSuite="Feature"` on the root element is what makes the split
real.** Without it PHPUnit runs every suite it knows about and the exclusion
does nothing — which is exactly what happened on the first attempt. It
looked done, and the only symptom was that the "fast" run took five minutes
instead of one. Nothing failed; the clock was the only witness.

The trade is real and accepted: the generator no longer runs on every
change. `composer test` runs both, and the rule is to run
`composer test:generator` before shipping anything that touches
`make:feature`, its stubs, or the conventions it relies on.

The deeper fix — giving the generator its own route files and a sandbox
instead of editing the real ones — is still not done. This only stops it
costing eleven minutes every time.

See also [[cleaning-up-by-substring-deletes-somebody-elses-code]].
