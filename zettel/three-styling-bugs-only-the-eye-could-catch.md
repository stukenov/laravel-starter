# Three styling bugs in a row, and no test could catch any of them

Applied the design tokens, ran the tests — green. Opened the styleguide in a
browser and found three failures back to back.

**First.** The error message rendered empty. I had set
`--destructive-foreground` to white, thinking it was the text colour on the
red button. But the button hardcodes `text-white`; that variable colours the
**message**, which sits on a light background. White on white — markup
present, nothing readable.

**Second.** The loading placeholder turned blue along with the accent:
shadcn paints it via `bg-primary/10`. A neutral element started reading as an
active state.

**Third.** Fixing it with `bg-muted` made it vanish entirely — that shade is
nearly white and invisible on a white card. A dedicated `--skeleton` token
was needed.

What they share: **HTTP 200, valid markup, nothing for a test to grip.** The
bug lives between a correct server response and what the eye sees.

Hence the rule: a token change ends by opening the styleguide in a browser,
not by running tests. Tests are added after the find — they prevent a repeat
but cannot discover it. Exactly as with CSP: see
[[csp-breaks-the-page-silently]].

It also justifies the styleguide itself: every component on one screen. Three
bugs surfaced in a single glance instead of a walk through ten pages.
