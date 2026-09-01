<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Enables $this->authorize(...) so policies can guard controller actions.
    use AuthorizesRequests;
}
