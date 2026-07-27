# A honeypot instead of a captcha

The CSP forbids every third-party domain at render time, so a captcha is
impossible here by construction — the browser blocks the script before it
loads. What is left needs only our own HTML: a field a person never sees,
and a signed timestamp that rejects a form filled faster than a person
types.

Two decisions make it actually protect anything:

`honeypot_fields_required_for_all_forms` is **true**. Left false — the
default — a bot posting only the fields it can see is waved straight
through, which is most of them. It is safe to be strict because the
middleware sits on the registration route alone.

The spam response is a redirect back, not spatie's blank 200. A blank
page makes Inertia draw its error modal, and this project answers with
pages.

Related: [[csp-breaks-the-page-silently]], [[errors-are-pages-not-modals]]
