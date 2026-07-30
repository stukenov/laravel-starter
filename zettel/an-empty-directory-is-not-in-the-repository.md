# An empty directory is not in the repository, and phpunit.xml pointed at one

`phpunit.xml` listed a `Unit` testsuite over `tests/Unit`. That directory
was emptied when the upstream starter's `ExampleTest` went out with the
rest of the demo content, and git does not track directories — only
files. So the folder survived on the machine that deleted the file and
existed nowhere else.

PHPUnit does not skip a missing suite directory. It stops:

    Test directory "…/tests/Unit" not found

and runs nothing. Every clone of this starter would have failed on its
first `php artisan test`, and the message points at tests rather than at
configuration.

Found by accident: a `git stash push -u` during unrelated work ran
`git clean -d` underneath, which removes untracked directories, and the
suite that had passed a minute earlier stopped starting at all.

The suite entry is gone. There are three levels of tests here — Pest
over `tests/Feature`, Vitest over `tests/js`, Playwright over
`tests/e2e` — and none of them is `tests/Unit`.

Related: [[a-rule-backed-by-a-test-is-a-rule]]
