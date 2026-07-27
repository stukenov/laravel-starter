# The SSR process holds the bundle in memory — restart it after every build

Edited a component, ran `npm run build:ssr`, opened the page — no change.
Checked the file: the edit was there. Checked the server response: the **old**
class.

The cause is simple and annoying: `node bootstrap/ssr/app.js` loads the
bundle at start and keeps it in memory. Rebuilding changes the file on disk;
the running process never finds out.

It cost three false checks: twice I "fixed" what was already fixed, and once
I introduced an extra token believing the previous fix had failed.

Rule: **`npm run build:ssr` always comes paired with restarting the SSR
process.**

    npm run build:ssr && pkill -f 'bootstrap/ssr' && node bootstrap/ssr/app.js &

Development does not have this problem — the Vite dev server does the
rendering and picks up changes itself. The trap only springs while verifying
the production path, which is the least convenient moment.

The same applies on deploy: without a restart the site keeps serving the
previous UI against an already-updated backend. See
[[ssr-is-configured-by-the-plugin]].
