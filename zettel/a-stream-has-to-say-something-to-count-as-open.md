# A stream has to say something to count as open

EventSource does not fire `open` when the headers arrive — it waits for
bytes of the body. A stream that opens and then goes quiet is, as far as
the page is concerned, not connected yet.

The live badge stayed dark for five seconds on every connection, until
the first heartbeat. With the connection recycling every 25 seconds, that
is a badge blinking off for a fifth of the time.

The handler now writes a `: open` comment before anything else. It costs
one line, and it also pushes the headers past a buffering proxy right
away instead of five seconds later.

Related: [[a-first-connection-starts-at-the-tip]]
