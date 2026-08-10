# A route that can be switched off cannot be imported statically

`REGISTRATION_ENABLED=false` is a documented switch, and the state most
projects built on this starter want. It also stops Wayfinder generating
`resources/js/routes/register` — and every page importing that module then
fails the **build**:

```
[UNLOADABLE_DEPENDENCY] Could not load .../resources/js/routes/register
```

Not a test. Not a request. The build. All 498 backend tests and all 125
rendering tests stayed green, because neither level builds a bundle. The
first person to close registration on a fresh clone found out when
`npm run build` died, with a message that reads like a typo.

**A runtime guard does not help.** `login.tsx` already wrapped the sign-up
link in `{registrationEnabled && …}` and still broke: the import sits at the
top of the file and is resolved whether or not the branch beneath it ever
runs. Conditional rendering and a conditional dependency are different
things, and only one of them is decided at build time.

The fix is to have no dependency at all. `HandleInertiaRequests` shares
`registerUrl` and `registerAction`, both null when the feature is off, and
the two pages read them from props. Nothing to resolve when the routes do
not exist.

`ConditionalRoutesTest` keeps it that way, and carries a canary — a scanner
that always answers "no offenders" is worse than no scanner.

The same shape will appear again for any feature behind a flag. The rule:
**if a route can disappear, its url comes from the server, not from an
import.**

See also [[the-honeypot-was-attached-to-the-one-form-that-disappears]].
