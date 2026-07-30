<?php

declare(strict_types=1);

return [

    /*
     * RULE(tenancy): off by default, and off means nothing changes — AGENTS.md.
     *
     * One database, one set of migrations, one backup. Every table the
     * application owns carries a nullable `tenant_id`, and a global scope
     * fills it in. With this false the scope never applies and the column
     * stays empty: a single-tenant project pays a column it does not read
     * and nothing else.
     *
     * The alternative — a database or a schema per tenant — buys physical
     * isolation and costs a migration run per tenant, a backup per tenant
     * and a development machine that has to know which one it is on. That
     * is a decision a project makes when it has the problem, not one a
     * starter makes for it.
     */
    'enabled' => (bool) env('TENANCY_ENABLED', false),

    /*
     * A person with no tenant sees everything.
     *
     * That is how an operator supports customers, and it is also how the
     * whole thing goes wrong if somebody creates an account without a
     * tenant by accident. So it is deliberate, it is tested, and the
     * panel shows it: `users.tenant` reads "все организации" for such an
     * account rather than leaving the column blank.
     */
    'operators_see_everything' => true,

];
