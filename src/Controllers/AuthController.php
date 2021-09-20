<?php

namespace A2Workspace\LaravelJwt\Controllers;

use Illuminate\Routing\Controller;
use A2Workspace\LaravelJwt\AuthenticatesUsers;

class AuthController extends Controller
{
    use AuthenticatesUsers;
}
