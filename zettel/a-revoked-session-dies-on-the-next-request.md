# A revoked session dies on the next request, and that is enough

"Sign out other devices" was going to push a logout down the stream so
the other tab reacted instantly. It does not, on purpose.

The push would need a stream open on every authenticated page, not just
the activity log. Every open connection holds a worker — that is the
whole reason the activity stream recycles every 25 seconds. Paying that
on every page, in every tab, to shorten the reaction to a rare event
would spend the budget the design was built around.

The revoked device is signed out on its next request, which is how every
Laravel application behaves. The delay is only visible on a tab nobody
is touching.

If this ever becomes worth it, the right shape is one stream per user
carrying every kind of event, not a second one beside the activity feed.

Related: [[the-stream-sleeps-on-a-socket-instead-of-polling]]
