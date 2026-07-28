# The tenant scope reads memory, never `auth()`

A global scope filtering by the current tenant has to get that tenant
from somewhere. The obvious source is `auth()->user()->tenant_id`, and it
cannot be used: resolving the user is itself a query on the users table,
which carries the scope, which asks for the user, which queries the users
table. Signing in recurses until the request dies.

So `SetTenant` runs once in the `web` group, after the session and the
user are resolved, and writes the tenant into `App\Support\Tenancy`. The
scope only ever reads a string already in memory. While the user is being
loaded nothing is set, the scope is inert, and the lookup that signs
somebody in is free to see the whole table — which is exactly what it
needs.

The same reasoning gives the operator behaviour for free: an account with
no tenant sets nothing, so nothing is scoped and they see everything.
That is deliberate, written in `config/tenancy.php`, and the reason an
account created without a tenant by accident is dangerous rather than
merely odd.

Work with no request — a queued job, a console command — has no
middleware to run, so it says which tenant it is for with
`Tenancy::as()`, which puts the previous value back even when the work
throws. Without that a single failed job rescopes every job the worker
handles afterwards.

Related: [[permissions-are-code-roles-are-data]],
[[route-nobody-can-reach]]
