# Mass assignment drops what is not fillable, without a word

`User::create([... 'email_verified_at' => now()])` creates the user and
throws that key away. `email_verified_at` is not in the model's `Fillable`
attribute, so Eloquent ignores it silently — no exception, no warning, a
row that looks right in every way except one.

The account then signs in fine and is bounced to `/email/verify` by the
`verified` middleware. Which looks like a broken login, not a dropped
attribute — it cost a full end-to-end run to trace.

Factories do not have this problem: they write attributes directly, which
is why `UserFactory` sets the same field and works.

Where a non-fillable attribute is genuinely wanted, say so out loud:
`$user->markEmailAsVerified()` is both explicit and readable, and it
cannot be dropped by a change to the fillable list.

Related: [[the-cabinet-speaks-the-browsers-language]]
