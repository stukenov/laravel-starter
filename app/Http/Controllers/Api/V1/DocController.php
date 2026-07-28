<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Dash\DocController as DocActions;

/**
 * RULE(api): the same controller, the same policies — AGENTS.md.
 *
 * Empty on purpose. Everything is inherited; this class exists
 * so Wayfinder sees one controller per surface and keeps the
 * `.form()` helpers unambiguous.
 */
class DocController extends DocActions {}
