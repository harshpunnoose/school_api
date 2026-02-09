<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // Only redirect for web routes
        if ($request->is('api/*')) {
            return null; // do NOT redirect
        }

        // Otherwise, web routes can still redirect
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

}
