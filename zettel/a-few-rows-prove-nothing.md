# A plan taken against a small table says only that the table is small

On a small table Postgres prefers a sequential scan whatever indexes
exist — reading a few pages beats descending a tree and fetching rows.

So `explain analyze` against a development database with forty rows shows
a sequential scan for every query, and proves nothing about whether an
index would be used at fifty thousand. It was measured that way once, and
the conclusion drawn from it was wrong.

Load a realistic number of rows first: 200,000 in the activity log and
50,000 users made the difference visible immediately — a sort of 50,000
rows with no index behind it.

Related: [[an-index-comes-after-a-measurement]],
[[measure-the-shape-not-just-the-size]]
