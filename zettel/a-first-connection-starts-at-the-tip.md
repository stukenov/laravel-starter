# A first connection starts at the tip

The page arrives with the log already rendered server-side. If the stream
then replays from sequence 0, every visible row is delivered a second
time and the list doubles.

That is what happened. It looked like the log had more entries than the
table held, and it was invisible to the tests: the backend was correct,
the database was correct, only the screen was wrong.

So the controller resolves the cursor by whether one was sent at all:

- no `Last-Event-ID` and no `since` → `Activity::tip()`, send nothing
- a cursor → catch up from it

The client seeds its `seen` set with the ids the page rendered, because a
reconnect resumes from a cursor that can predate them.

Related: [[the-stream-sleeps-on-a-socket-instead-of-polling]],
[[a-stream-has-to-say-something-to-count-as-open]]
