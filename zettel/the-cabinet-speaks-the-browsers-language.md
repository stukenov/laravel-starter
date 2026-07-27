# The cabinet speaks the browser's language

Public pages carry the locale in the URL. The cabinet does not — it takes
the language from the profile, and an anonymous visitor has no profile.
So the login page is rendered in whatever the browser asks for.

This is correct, and it broke the first end-to-end run in a way that took
a screenshot to see: Chromium ships `Accept-Language: en-US`, the login
page came back in English, and every selector written against the Russian
copy waited thirty seconds and gave up.

Two conclusions, both kept:

The suite pins `locale: 'ru-RU'`. A run whose language depends on the
machine it runs on is not a test.

And the selectors go through `data-test`, not through copy. Text is the
one thing on a page guaranteed to change.

Related: [[the-element-marker-is-data-test]]
