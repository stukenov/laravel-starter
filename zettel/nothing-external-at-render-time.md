# The ban on external resources is enforced by a header, not a promise

Requirement: rendering a page must not reach any foreign domain — no font,
no icon, no analytics.

The important part: simply "not adding them" is not enough. A year from now
somebody drops in a widget and nobody notices. So the ban lives in **CSP
headers** and the browser itself rejects the request.

Three concrete consequences:

- the font ships through `@fontsource`-style build-time fetching and is
  served from our own domain;
- avatars are initials in a circle, Gravatar is out as a foreign domain;
- Google or Cloudflare captcha is impossible, so honeypot instead.

The last one is telling: a security requirement ruled out the most common
way to protect a form. See [[honeypot-instead-of-captcha]].

And the ban itself can misfire — see [[csp-breaks-the-page-silently]].
