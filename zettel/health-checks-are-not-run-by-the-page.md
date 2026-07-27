# Health checks are not run by the page

`health:check` runs on the schedule and writes its results. `/dash/health`
only reads them.

The tempting alternative — run the checks when somebody opens the page —
puts a disk scan and a database query on every refresh, and hands the
panel a way to hang: a check that blocks blocks the request.

It also changes what the page means. Results written every minute answer
"has anything broken since"; results computed on demand answer only "is
it broken this instant", which is the question that misses the outage
that ended an hour ago.

Related: [[a-health-dashboard-that-loads-a-font-from-elsewhere]]
