<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;

class GuessAuthenticate extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                $this->auth->shouldUse($guard);
            }
        }
    }
}
