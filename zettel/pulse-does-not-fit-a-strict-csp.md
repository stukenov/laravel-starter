# Pulse does not fit a strict CSP

Installed, migrated, gated and opened in a browser. The result:

- the page requests a font from `fonts.bunny.net`, which the CSP blocks —
  the same failure as spatie's health dashboard;
- the dashboard never fills in. Its cards stay as loading skeletons
  forever, and no Livewire request is ever made;
- it brings `livewire/livewire`, a second frontend runtime, into a
  codebase whose whole shape is Inertia and React.

Making it work would mean publishing and patching a vendor view to strip
the font, then keeping that fork current, and carrying Livewire in the
dependency tree for one page.

So it was removed. What Pulse answers — what is slow, what is failing —
is worth having, but the way to get it here is our own page over data we
already collect, not a second framework.

The general lesson, and this is the second package to teach it: a package
that renders its own HTML has opinions about where its assets come from.
Check that before adopting the view; the data is usually fine.

Related: [[a-health-dashboard-that-loads-a-font-from-elsewhere]],
[[csp-breaks-the-page-silently]]
