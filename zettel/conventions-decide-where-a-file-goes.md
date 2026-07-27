# A file's place follows from the entity name, and that is what makes the generator possible

`Post` implies `app/Models/Post.php`, `app/Policies/PostPolicy.php`,
`app/Http/Controllers/Dash/PostController.php`,
`resources/js/pages/dash/posts/index.tsx`, and eleven more — each of them
derivable, none of them a choice.

This is not taste. `make:feature` writes fifteen files by computing their
paths from one word; a project that puts a controller somewhere else has
no generator. The convention buys the automation, and the automation is
what makes a new entity ten minutes instead of an hour.

Tables are plural snake_case, models singular StudlyCase, routes RESTful,
and the Inertia page path mirrors the URL.

Conventions bind the typical. Trees, versioning and translatable content
are written by hand, and each deviation gets a card here.

Related: [[a-generator-without-real-tests-is-not-a-generator]],
[[a-fix-to-the-example-is-not-a-fix-to-the-generator]]
