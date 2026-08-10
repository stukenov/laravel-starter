# ReachableTest could not see an invokable controller

`ReachableTest` answers one question: is there anything on any screen that
can call this writing route. It did it by splitting the route name into
`dash.entity.action` and looking for the helper.

Two blind spots, both found by the first project that used shapes the
starter itself does not:

**A four-segment name.** `dash.projects.touches.store` splits into three
with `array_pad`, so `$action` came out as `touches` — a verb no page will
ever import. Any nested route was unreachable by definition.

**An invokable controller.** `Route::post($uri, SomeController::class)`
records the action as a bare class name with no `@method`. The scan required
an `@`, skipped such routes when building its map, and then reported them
unreachable whatever called them. The starter has four invokable
controllers already — `FileController`, `ActivityStreamController`,
`SitemapController`, `RobotsController` — they are simply all reads, so
nothing noticed.

Both are gone: the controller and method now come from the route object
rather than from parsing its name, and `__invoke` is filled in when there is
no `@`.

Two caller shapes were invisible too, and are recognised now:
`import { store as writeNote } from '@/actions/…/XController'` and the
default import of a single-action module. For a controller with one route
and one export, importing the module is the whole of the proof.

The canary — "would notice a route with no caller" — is still green, which
is what stops a broadened check from becoming a check that always passes.

**The lesson: derive facts from the object that holds them, not from the
name somebody gave it.** A route knows its controller; the name is a label.
