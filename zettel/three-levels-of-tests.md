# Three levels, because each one is blind to what the others see

Pest asks whether the endpoint behaves. Vitest asks whether the component
draws. Playwright asks whether a person can click the thing.

None of them substitutes for another, and the gaps between them are where
this project kept shipping defects. A page can answer 200 with valid
markup and be entirely broken — three styling bugs in a row did exactly
that, and no assertion existed that could have caught them. An action can
be correct, routed and tested with nothing on screen able to call it; see
[[route-nobody-can-reach]].

Each level runs against three data states: correct, wrong, empty. Nine
scenarios is the floor for a feature, not the target.

A test written and never run does not count. Run it and show the output.

Related: [[a-rule-backed-by-a-test-is-a-rule]],
[[the-promise-in-the-docblock-was-never-true]]
