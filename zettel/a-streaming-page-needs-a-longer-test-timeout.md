# A streaming page needs a longer test timeout

Three activity tests timed out at thirty seconds. All three, and only
those three, made a **second** navigation inside one test — a reload or a
jump to a filtered address. The first navigation was always fine.

It was mistaken for three other things first: a login throttle, a dev
server out of workers, and the `load` event never firing. All three were
investigated, two were real problems worth fixing on their own, and none
of them was this.

What settled it was measurement rather than argument: the same ten tests
at a ninety-second limit pass, all of them.

The cause is the feature. The page holds a connection open for
twenty-five seconds on purpose, and a navigation away waits for the old
stream to let go. Nothing is broken; thirty seconds is simply less than
the thing being tested takes.

So `activity.spec.ts` runs at ninety and the rest of the suite stays at
thirty. If `LIFETIME_SECONDS` in the controller ever comes down, this can
come down with it.

The habit worth keeping: when a theory is cheap to test, test it instead
of arguing for it. Three rounds of reasoning cost more than one
measurement would have.

Related: [[a-suite-that-signs-in-every-test-fights-the-throttle]],
[[the-stream-sleeps-on-a-socket-instead-of-polling]]
