# What a form accepts as an upload, said once

An image field's rules were written out at every call site:
`['nullable', 'image', 'mimes:'.implode(',', config('files.image_mimes')),
'max:'.config('files.max_kilobytes')]` — in `ProfileUpdateRequest` by
hand and in every request `make:feature` generates.

At level 10 that line stops working: both config reads are `mixed`, so
`implode` and the `'max:'.` concatenation both fail. Narrowing it in
place would have meant the same three-line narrowing copied into every
generated entity, and a 130-character generated line nobody reads.

So it moved to `App\Support\Files\FileRules`, with `image()` and
`any()`. The generator emits `'cover' => FileRules::image()`, which is
shorter than what it emitted before and now typed. A project that
changes what an upload may be changes one file.

The comment about why there are two checks — `mimes` reads the
extension, `image` reads what the file actually is — went with it,
because it belongs next to the rules and not next to each copy.

Related: [[a-fix-to-the-example-is-not-a-fix-to-the-generator]],
[[config-answers-mixed-and-so-must-the-reader]]
