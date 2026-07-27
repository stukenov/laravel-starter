# The starter has no sanitizer because nothing in it renders HTML — which is what makes it a trap

React escapes everything it draws. `dangerouslySetInnerHTML` is the one
way out, and it is how stored XSS arrives.

No field type here stores HTML, so nothing needs the escape hatch, so no
sanitizer was ever added. Today that is correct. The trap is what happens
next: the first project with a formatting editor — a blog, a catalogue, a
news site — reaches for `dangerouslySetInnerHTML` because it is the only
way, finds no sanitizer, notices nothing wrong, and ships an editor
account that can run scripts against every visitor. One port did exactly
this, in five files, and a person reading the diff was the only thing that
caught it.

So the door is held visibly shut by a test rather than left quietly
unlocked. Opening it means sanitizing on write — in PHP, once, so the
database holds something already safe — rather than on read in each of the
places that display it. That needs a dependency, which is the owner's
decision.

The shape worth remembering: an absent safeguard is invisible while the
feature that needs it is also absent. The moment to write it down is
before the feature arrives, not after.

Related: [[csp-breaks-the-page-silently]], [[nothing-external-at-render-time]]
