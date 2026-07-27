# The panel root is /dash

Every panel resource lives under `/dash`, but the panel's own home page
kept the upstream starter's `/dashboard` for a while. Trimming the
address bar to `/dash` gave a 404 — the one address a person types by
hand was the one that did not exist.

It is `Route::inertia('/', 'dashboard')->name('home')` inside the `dash.`
group now, so the URL is `/dash` and the route name is `dash.home`.
`config/fortify.php` sends people there after login.

The Inertia page file is still `pages/dashboard.tsx`: the page path
mirrors the URL for generated entities, and this one is hand-written.

Related: [[conventions-decide-where-a-file-goes]]
