# Three tests were green and empty on the same night

Each one looked like protection and checked nothing. All three were found
the same way — by breaking the thing on purpose and watching the test stay
green.

- `hasAll([...])->etc(false)` on Inertia props passed with the whole model
  in place. It had to become a comparison of the key set.
- `Str::between($readme, '## Tests', '##')` takes the **last** occurrence
  of the closing needle, so the slice ran to the end of the file and
  picked up a line from the deploy section. It passed with the line it was
  guarding deleted.
- A pattern matching `->where(` missed `Model::where(`, which is the
  commonest form there is. Its own falsification check caught it.

None of the three would have been noticed by running the suite, because
all three passed. A test only earns trust once you have seen it fail for
the reason it exists.

So the loop is: find, reproduce by measurement, fix, **break it back**,
confirm the test goes red, restore. The last three steps are the cheap
part and the only part that proves anything.

Related: [[a-rule-backed-by-a-test-is-a-rule]],
[[the-promise-in-the-docblock-was-never-true]],
[[measure-the-shape-not-just-the-size]]
