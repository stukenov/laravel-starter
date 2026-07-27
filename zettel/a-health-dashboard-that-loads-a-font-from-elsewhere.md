# The health dashboard loads a font from elsewhere

Spatie ships a Blade dashboard with laravel-health. Its first line is a
stylesheet from `rsms.me`, which the CSP here blocks outright — the page
would arrive unstyled and the reason would appear only in a console.

So the panel reads the same stored results into a page of its own. It
costs a controller and gains one design language across the panel.

The general shape of the lesson: a package that renders HTML has opinions
about where its assets come from, and a project with a strict CSP has to
check that before adopting the view rather than the data.

Related: [[csp-breaks-the-page-silently]], [[health-checks-are-not-run-by-the-page]]
