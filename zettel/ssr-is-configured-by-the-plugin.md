# In Inertia 3 the plugin configures SSR, not the developer

The plan called for writing `resources/js/ssr.tsx` with `createServer` and
wiring it into `vite.config.ts`. That turned out to be the Inertia 2 way and
is unnecessary in version 3.

The `@inertiajs/vite` plugin does three things by itself:

- **finds the entry point** — a plain `app.tsx` will do, no separate file;
- **injects the server bootstrap** straight into the built bundle;
- **renders through its own dev server** in development, so no Node process
  needs starting.

Two changes in `config/inertia.php` were all it took: uncomment the bundle
path and fix its name.

That second one is the point. The plugin builds **`bootstrap/ssr/app.js`**,
not the `ssr.mjs` from Inertia 2 docs. Getting it wrong fails **silently**:
the app runs, pages open, and they arrive as an empty container with no
markup. No error, no log line — SSR just quietly does nothing.

So the test compares the configured path against what the build actually
produces. See [[the-ssr-process-holds-the-bundle-in-memory]].
