<?php

namespace App\Http\Controllers;

abstract class Controller
{
    // Menyediakan $this->authorize(...) untuk mengecek Policy.
    // Tanpa trait ini, pemanggilan authorize akan error.
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
}
