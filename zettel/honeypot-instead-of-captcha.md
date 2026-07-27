# Honeypot instead of captcha follows directly from the external-resource ban

Registration is open, so the form needs bot protection. The usual answer is
reCAPTCHA or hCaptcha.

Both are impossible here: they load a foreign script at render time. That
breaks the owner's requirement and simply will not pass our CSP — the
browser rejects the request.

So `spatie/laravel-honeypot`: a hidden field plus a timing check. All on our
side, zero external requests.

Honest trade-off: honeypot is weaker than a captcha against a targeted
attack. Against the mass spam captchas actually exist for, it works — and
the price of a foreign script on every page is not acceptable here. See
[[nothing-external-at-render-time]].
