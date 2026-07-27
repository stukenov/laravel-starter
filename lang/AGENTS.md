# Translations

- Three locales: `ru`, `kk`, `en`. A new key goes into all three at once —
  a test compares the key sets.
- Per-entity copy lives in `entities/<plural>.php` and reaches the front
  end prefixed: `users.title`.
- A missing translation renders as its key, not as emptiness, so it is
  visible immediately.
- Kazakh letters in use: ә ғ қ ң ө ұ ү һ і. The font covers them, verified.
- This directory is the one place where Russian and Kazakh belong. Every
  other file in the repo is English.

Full rules: `AGENTS.md` in the repo root.
