<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /*
     * Laravel 13 does not include this trait by default. Without it,
     * $this->authorize() fails with "method does not exist" — a 500 where
     * you meant a 403, which reads like a broken policy and sends you
     * looking in the wrong place.
     */
    use AuthorizesRequests;
}
