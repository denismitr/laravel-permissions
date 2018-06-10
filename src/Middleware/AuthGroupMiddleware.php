<?php

namespace Denismitr\Permissions\Middleware;

use Closure;

class AuthGroupMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param $groups
     * @param null $permission
     * @return mixed
     */
    public function handle($request, Closure $next, $groups, $permission = null)
    {
        if ( ! $request->user()->isOneOf($groups)) {
            abort(403);
        }

        return $next($request);
    }
}
