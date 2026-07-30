# `config()` answers mixed, and the reader has to say what it wanted

The single biggest source of level 10 errors was config. `config('x')`
returns `mixed` by construction — a config file may hold anything — so
every `Storage::disk(config('files.disk'))` and every
`'max:'.config('files.max_kilobytes')` was an untyped value flowing into
a typed parameter.

The fix is Laravel's own typed readers: `Config::string()`,
`Config::integer()`, `Config::array()`, `Config::boolean()`. They return
the type they are named after and throw when the value is not it, which
is better than a cast in both directions — a misconfigured value fails
loudly at the read instead of quietly becoming `0` or `""`.

Two places they do not fit:

- **Inside a config file.** Config files are loaded before
  `RegisterFacades` runs, so the facade has no root and `Config::string()`
  dies during boot. Found by running `php artisan config:clear` after
  changing `config/fortify.php`: "A facade root has not been set". That
  file narrows by hand instead, with a comment saying why.
- **A key that is legitimately null.** `health.json_secret` is unset in
  most projects and the answer to that is a 404, not an exception; the
  same for spatie's `column_names.role_pivot_key`, which ships as null
  meaning "use the default".

Related: [[larastan-level-10-is-where-mixed-starts-speaking]]
