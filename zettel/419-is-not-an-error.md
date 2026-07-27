# 419 is an expired session, not an error — so it gets a redirect

Formally 419 sits in the same list as 404 and 500, and the temptation is to
render it the same way.

For the user it is a different event. Nothing broke and no link was wrong —
they simply took too long over a form. A page saying "Error 419" explains
nothing and offers no next step.

So 419 redirects to login with a plain message.

General principle: **an error page is for when the user has nowhere to go.**
When the next step is obvious, take them there instead of showing a number.
See [[errors-are-pages-not-modals]].
