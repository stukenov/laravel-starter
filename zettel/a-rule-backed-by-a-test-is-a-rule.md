# A rule backed by a test is a rule; a rule in a file is a wish

Conventions only speed work up while they are followed. Written as prose
they survive until the first developer in a hurry, or the first AI agent
that finds another way more convenient.

So every hard rule rests on three supports:

1. **Text** — `AGENTS.md` in the root plus short nested files per
   directory. It explains *why*, and tools pick it up where the work is.
2. **A trap comment** — only where the wrong move is tempting. One marker,
   `RULE(topic)`, so every constraint is findable with one grep.
3. **A test** — `ArchitectureTest` for structure, ordinary tests for
   behaviour. A red test cannot be ignored.

Plus a closing check: a test asserts that every `RULE(...)` comment points
at an existing rule. **A stale rule is more dangerous than a missing one —
people believe it.**

Restraint matters too. Commenting everything turns into noise and then
nothing gets read. Ten places where a mistake is likely beat a hundred
obvious explanations. See [[project-rules-live-in-one-file]].
