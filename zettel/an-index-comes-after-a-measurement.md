# An index is added after a measurement, never before one

Two composite indexes were written for the activity page on perfectly
sound reasoning — the columns the page filters by, in the order it filters
them. Then they were measured: `pg_stat_user_indexes` reported
`idx_scan = 0` for both. The planner had never taken either.

They were removed. An index the planner ignores is not neutral: it slows
every write and occupies space to buy nothing.

So the order is `explain analyze` against a realistic number of rows,
then the index, then `pg_stat_user_indexes` to see whether it was taken.
Reasoning about which index a query wants is a hypothesis, not a result.

The real cost of that page turned out to be somewhere else entirely: two
`distinct` scans on every load, 73 ms, now cached.

Related: [[a-few-rows-prove-nothing]],
[[measure-the-shape-not-just-the-size]]
