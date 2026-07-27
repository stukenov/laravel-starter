# Tests select by `data-test`, never by the words on screen

Testing Library is configured for `data-test` and Playwright uses the same
attribute, so one marker serves both levels.

The reason is not tidiness. The cabinet has no locale in its URL, so its
pages render in whatever language the browser asks for — and a headless
Chromium asks for `en-US`. A suite written against the Russian copy passes
on a developer's machine and fails in CI, for a reason that looks like a
broken page.

Copy also changes. A selector tied to a word is a test that fails the day
somebody improves a label, which teaches people to distrust the suite.

Related: [[the-cabinet-speaks-the-browsers-language]],
[[three-levels-of-tests]]
