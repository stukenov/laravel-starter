# The honeypot was attached to the one form that disappears

Bot protection lived on registration and nowhere else. Close registration —
the documented switch, and what most projects want — and the starter had no
honeypot anywhere, while the password-reset request stayed open to the
world.

Worse, it looked fine. `SpamProtectionTest` checked that every route named
in `security.spam_protected_routes` carried the middleware; with the list
holding one name and that route gone, there was nothing left to disagree
with. The canary that was supposed to catch a hollow test — "reads
middleware off a route at all" — asked about `register.store` too, so it
went quiet at exactly the same moment.

Now both public write forms carry it. The list is conditional the same way
`fortify.features` is, because naming a route that does not exist is the
typo the list is checked for:

```php
'spam_protected_routes' => array_values(array_filter([
    env('REGISTRATION_ENABLED', true) ? 'register.store' : null,
    'password.email',
])),
```

Three things had to move together, and this is the part worth remembering:

1. the view has to pass the honeypot props — without them the page has
   nothing to submit;
2. the page has to render `<HoneypotFields>`;
3. **every test that posts to the route has to send the fields.**

`honeypot_fields_required_for_all_forms` is on, so a post without them is
treated as spam and **silently redirected** — no error, no mail, no trace.
Three real password-reset tests broke exactly the way a person would: by
quietly not working.

The canary now asks about `password.email`, which cannot be switched off.
