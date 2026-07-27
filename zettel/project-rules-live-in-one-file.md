# Project rules live in one file, everything else points at it

The starter gets cloned and continued alongside an AI tool. Without rules in
the root, every fresh agent starts by guessing: it installs SQLite "for
tests", adds a dark theme, pulls in Google Fonts, dumps a list and a form on
one route.

So `AGENTS.md` sits in the root — hard rules, stack, commands, where things
are, and what the project deliberately lacks.

Key decision: **one source of truth.** `CLAUDE.md` holds a single
`@AGENTS.md` line and the Cursor rule is also a pointer. Two copies of the
rules always drift, and then the agent picks whichever suits it.

Second decision: every hard rule must be backed by a test. "PostgreSQL only"
by `DatabaseDriverGuardTest`, "nothing external" by `SecurityHeadersTest`.
A rule that cannot be tied to a test is either redundant or missing a test.

Stale rules are worse than absent ones: agents believe them. See
[[a-rule-backed-by-a-test-is-a-rule]].
