# Every Inertia project shares one SSR port

The SSR server listens on 13714, and the number is baked into the bundle
by the `@inertiajs/vite` plugin — `createServer(renderPage)` is generated
with no options, so there is nothing to configure per project.

Two Inertia applications on one machine therefore share a renderer.
Whichever started first answers for both, and the second one asks it to
render a component it has never heard of. The log fills with
`Page not found: <component>` from a project you are not working on, and
your own pages come back as an empty container.

Nothing errors. The client bundle hydrates and the site looks fine in a
browser, which is why this can survive a whole session unnoticed — a
`curl` shows an empty `<div id="app">` and a browser does not.

`tests/Feature/SsrTest.php` asks the running server to render a page only
this project has. If somebody else's renderer answers, that test fails.

On a development machine: stop the other project's SSR process. In
production each application has a machine of its own and the question
does not arise.

Related: [[the-ssr-process-holds-the-bundle-in-memory]]
