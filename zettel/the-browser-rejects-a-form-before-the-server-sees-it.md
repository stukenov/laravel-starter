# The browser rejects a form before the server sees it

Two end-to-end tests looked for a validation message from the server and
found nothing. The fields carry `required` and `type="email"`, so the
browser refused to submit and no request was ever made.

The tests were wrong, not the forms. Rewritten to assert what actually
happens — `validity.valueMissing`, `validity.typeMismatch` — which is the
guard doing its job.

The server-side half of the same rule is covered by Pest, where a request
can be made without a browser in the way. Both halves matter: the browser
check is convenience, the request check is the one that is enforced.

Related: [[three-levels-of-tests]]
