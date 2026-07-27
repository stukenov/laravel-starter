# Kazakh letters are split across two Cyrillic subsets

"A font with Kazakh letters" sounds like one checkbox. In practice it comes
down to how the font is sliced by `unicode-range`.

Inspecting the built Inter files with fontTools gave the exact picture:

- **cyrillic-ext** — ә ғ қ ң ө ү һ (seven letters);
- **cyrillic** — ұ (U+04B1) and і (U+0456) (two letters).

So **both** subsets must be requested. Keep only `cyrillic-ext` and you lose
ұ and і; keep only `cyrillic` and you lose the other seven.

The danger is that the failure is **silent**: a missing letter does not break
the page, it falls back to a system font. You only notice if you know where
to look — and the person who notices is a Kazakh-speaking user, in
production.

So the check is automated at two depths: a test parses the declared
`unicode-range` values from the build manifest (catching a removed subset),
and `scripts/check-font-glyphs.py` looks inside the `.woff2` files
themselves (catching a mismatch between declaration and content).

It also confirmed that Laravel's font pipeline serves locally: the manifest
holds only `/build/assets/` paths, no external address. See
[[nothing-external-at-render-time]].
