# Front end

- Light theme only. Never add `dark:`. The ones inside `components/ui/`
  are upstream shadcn and never fire.
- Dates are formatted on the client through `lib/datetime.ts` (built-in
  `Intl`). No date libraries.
- Copy goes through `hooks/use-translations.ts`. New strings land in all
  three locales at once.
- Static objects (layout, breadcrumbs, nav) hold a translation key, not
  text — a static object cannot call a hook.
- "Go home" is `/`, not `home()`: the server fills in the locale.
- Element marker is `data-test`, not `data-testid`.
- Nothing external: any foreign domain is blocked by CSP.

Full rules: `AGENTS.md` in the repo root.
