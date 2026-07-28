<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Settings\TokenController as TokenActions;

/**
 * RULE(api): the same controller, the same policies — AGENTS.md.
 *
 * Empty on purpose. Every action, policy and form request is inherited;
 * a rule enforced in the panel is enforced here because it is literally
 * the same method. The subclass exists for one mechanical reason:
 * Wayfinder generates typed helpers per controller action, and an action
 * reachable at two URLs loses its `.form()` variant — which the whole
 * auth flow uses. One class per surface keeps the generated helpers
 * unambiguous and costs a file with no body.
 */
class TokenController extends TokenActions {}
