# CSP breaks a page silently — only a browser will tell you

Set the policy: `default-src 'self'`, no external hosts, nonce for scripts
instead of `unsafe-inline`. Headers came out correct, eight tests green,
HTTP 200, markup in place.

And the application was completely dead. The browser blocked **every**
script — eighteen blocked files in the console.

The cause is not obvious: `script-src` held only the nonce. Our own Vite
bundles arrive as `<script src="...">` tags and carry no nonce, so they do
not match the directive. Both sources must be listed:
`script-src 'self' 'nonce-...'`. Then our files load, inline scripts need
the nonce, and foreign domains stay blocked.

The real lesson is not about CSP but about verification. **None of my eight
tests could have caught this**: they inspected the header, and the header was
flawless. The bug lived purely in the browser, between a correct header and
correct markup.

Hence the rule: **any CSP change is verified by opening the page in a real
browser and reading the console.** Green tests prove nothing here.

A test asserting `'self'` in `script-src` was added afterwards — it catches
this exact regression. But automation did not find it. See
[[nothing-external-at-render-time]],
[[a-rule-backed-by-a-test-is-a-rule]].
