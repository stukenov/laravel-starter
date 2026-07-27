# Everything that ships is working functionality

No sample posts, no placeholder users, no "example" section wired to
nothing. A starter is cloned and built on, and demo content is the first
thing the next person has to identify and remove — which requires reading
enough of it to be sure it is really demo.

It also lies about coverage: a seeded row makes a list look finished when
nothing tested whether the empty state renders.

The one place where placeholder text is allowed is the legal pages, and
there it announces itself: every unfilled section says so in red on the
page. See [[legal-pages-ship-headings-not-wording]].

The seeder that once created `test@example.com` was removed for exactly
this reason — and it turned out to be the visible half of a blocker, since
that account had no role and no way to be given one.

Related: [[permissions-are-code-roles-are-data]]
