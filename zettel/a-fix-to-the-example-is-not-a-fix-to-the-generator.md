# Repairing the reference page left the template that stamps out copies untouched

The user list was made mobile-first: header hidden below `sm`, row turned
into a card, 44px targets. `stubs/feature/page-index.stub` was not. So the
repair reached exactly one page, while `make:feature` went on producing
the broken shape — once per entity, for as long as anybody used it.

The defect multiplies where the fix does not. A page fixed by hand is one
page; a template fixed is every page anybody will generate. Which means
the generator is where a class of bug is worth hunting, and a fix applied
to an example is only half done until the same change reaches the stub.

Same story in the same night: [[generated-model-had-no-casts]], where the
model template omitted something the hand-written models had.

Related: [[a-generator-without-real-tests-is-not-a-generator]],
[[the-promise-in-the-docblock-was-never-true]]
