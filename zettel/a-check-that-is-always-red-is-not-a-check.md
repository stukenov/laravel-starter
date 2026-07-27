# A check that is always red is not a check

Debug mode, environment and optimized-app assert how production is
configured. In development every one of them is meant to be false.

Registered unconditionally, they paint the dashboard permanently red, and
a dashboard that is always red gets ignored — including on the day
something real fails.

So they are registered only when the app is in production. The rule
generalises: a check that cannot pass in the environment it runs in
teaches people to stop reading checks.

Related: [[health-checks-are-not-run-by-the-page]]
