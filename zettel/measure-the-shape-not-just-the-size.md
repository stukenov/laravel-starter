# Comparing two measurements is only honest if the data has the same shape

A query-count check on the list pages was written to catch N+1: measure at
a small row count, measure at a large one, demand they match.

It failed on the activity page — 2 queries against 3 — and not because of
N+1. The page eager loads the author of each entry, and Laravel skips that
query entirely when page one happens to hold no entry that has an author.
At six rows there were none. So the comparison was between a degenerate
fixture and a realistic one, and it measured the fixture.

The fix was to assert a ceiling instead of an equality: at 61, 461 and
1661 users the counts are 4/4/4, 2/2/2 and 2/3/3. Flat, which is the whole
point; a budget of six leaves room for another join and no room for a
query per row.

Two related traps, both walked into the same night: measuring indexes on
an empty database, where Postgres prefers a sequential scan whatever
exists, and comparing a cold cache against a warm one, which reports a
saving that is not there.

Related: [[an-index-comes-after-a-measurement]],
[[a-few-rows-prove-nothing]]
