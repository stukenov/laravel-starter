# NOTIFY arrives only after COMMIT

Postgres queues a notification and delivers it when the transaction
commits. Inside a transaction that never commits, a listener waits
forever and times out.

The test suite runs every test inside such a transaction, so
`activity()->log(...)` in a test produces a row the trigger sees and a
notification nobody ever receives. The failure looks like a broken
trigger, which is the wrong place to go looking.

`tests/Feature/Dash/ActivityStreamTest.php` writes through a connection
of its own for exactly the two tests that wait on a notification. In
production nothing special is needed: the request commits and the
notification goes out.

Related: [[pg-connect-hands-back-an-existing-connection]],
[[the-stream-sleeps-on-a-socket-instead-of-polling]]
