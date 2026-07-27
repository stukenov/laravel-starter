# The existing codex package was rejected by reading its source, not its description

`symfony/ai-codex-platform` was the only live candidate: Symfony team, MIT,
updated a week earlier. The pull toward reusing it was strong.

Reading the source turned up four blockers:

- the prompt goes in as the last command-line argument, so it is visible in
  `ps aux`; a search across all seven files for `stdin` returned nothing;
- there is no session resumption at all — zero hits for `resume` and
  `session`, and our whole conversation model rests on it;
- the flag map contains `--allowedTools`, which `codex exec` 0.144.2 does not
  have;
- it drags in `symfony/ai-platform` 0.12 — a foreign abstraction layer
  before its 1.0.

The conclusion is broader than one package: **a description and a download
count do not replace reading the code.** A package with zero downloads is
dismissed immediately, but for a live one you have to check the specific
places you depend on.
