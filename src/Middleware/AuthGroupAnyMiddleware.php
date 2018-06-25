<?php

namespace Denismitr\Permissions\Middleware;

use Closure;
use Denismitr\Permissions\Exceptions\UnauthorizedException;

class AuthGroupAnyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param $groups
     * @return mixed
     */
    public function handle($request, Closure $next, $groups)
    {
        if ( ! auth()->check() ) {
            throw UnauthorizedException::guestUser();
        }

        if ( ! auth()->user()->isOneOfAny($groups)) {
            throw UnauthorizedException::notMemberOfRequiredAuthGroups($groups);
        }

        return $next($request);
    }
}
