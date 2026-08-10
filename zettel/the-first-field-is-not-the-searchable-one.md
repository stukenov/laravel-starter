# The first field is not the searchable one

`{{ firstField }}` means "whatever was typed first", and the generator used
it for three different jobs. Two of them wanted something else.

**The trigram index.** The migration built
`create index … using gin (first_field gin_trgm_ops)`. For an entity whose
first field is an integer or a boolean that is not a warning — it is a
failed `php artisan migrate`, the very next command the generator prints,
with an error about operator classes that names neither the entity nor the
field.

**The search test.** It wrote a phrase into the first field and searched for
it. For `Run --fields=status:enum(open,closed),log:text` that field is an
enum behind a check constraint: the test could not insert its own fixture,
and `scopeSearch` — correctly — does not search enums anyway.

Both now use `searchField()`: the first `string`, `text` or `richtext`
column. When an entity has none, the index and the scenario are simply not
emitted, the same way the second-column scenario already was. A test that
cannot insert its fixture is worse than a missing test, and an index on a
column no query touches is worse than no index.

`{{ firstField }}` keeps the jobs it was right for: the column the list
shows first, the field the form fills, the input the browser test types
into.

**The lesson: one name for three jobs held while all three happened to
agree.** The first entity where they disagreed found all of it at once.

See also [[a-generated-model-needs-a-property-block]].
