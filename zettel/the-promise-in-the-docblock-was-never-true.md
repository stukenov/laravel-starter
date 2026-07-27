# The generator's acceptance criterion was written down and never held

`MakeFeatureCommand`'s docblock states it plainly: after generation,
`php artisan test` and `npm run test:js` pass with no edits at all.

It failed three separate ways in one night, each found by generating an
entity and running its own suite — which nobody had done, because
`MakeFeatureTest` inspects the emitted files and never runs them.

- A policy referenced an `Ability` constant nothing declared: fatal on
  every request, including `php artisan test` itself.
- No `casts()`, so a `date` field made the list page answer 500 and ten of
  thirteen backend tests fail. See [[generated-model-had-no-casts]].
- Two test levels shipped where the criterion said three.

The pattern is not "the code was wrong". The pattern is that a written
promise and a passing suite were both present and neither described what
happened. The gap between them is where the whole night's findings lived.

Related: [[a-generator-without-real-tests-is-not-a-generator]],
[[a-fix-to-the-example-is-not-a-fix-to-the-generator]],
[[a-rule-backed-by-a-test-is-a-rule]]
