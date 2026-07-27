# Errors show as pages because the modal is Inertia's doing, not Laravel's

The owner disliked 404 and 500 appearing as an overlay. The cause is not
Laravel: when the server answers with a non-Inertia response, the Inertia
client renders it inside a modal.

Fixed in three layers: the exception handler returns a normal
`errors/error-page`, the modal never triggers because there is no longer a
non-Inertia response, and a blade fallback with inline styles stays behind.

The third layer is not decoration. If the SSR process dies there is nothing
left to render an Inertia page with, and without blade the user sees raw
text at exactly the worst moment.

419 is handled apart: it is an expired session, not a failure. See
[[419-is-not-an-error]].
