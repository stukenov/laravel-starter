# A suite that signs in every test fights the throttle

Fortify allows five login attempts a minute per email and address. The
first end-to-end suite signed in inside `beforeEach` — twenty-five times,
from one machine, with one account.

The first few tests passed. Everything after them waited thirty seconds
for a redirect that was never coming, because the login was being
throttled and the page simply stayed where it was.

It reads like a broken login and it is nothing of the sort: the limiter
did exactly its job. The test design was wrong.

The fix is the ordinary Playwright one. A setup project signs in once and
saves the cookies; every spec starts from them. `auth.spec.ts` still logs
in for real — that is what it tests — and clears the limiter between its
own cases.

It is also much faster, which is the usual shape of this kind of fix.

Related: [[the-cabinet-speaks-the-browsers-language]]
