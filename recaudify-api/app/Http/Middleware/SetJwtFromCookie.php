<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetJwtFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = config('jwt.cookie_key_name', 'token');

        if (! $request->headers->has('Authorization') && $request->hasCookie($cookieName)) {
            $request->headers->set('Authorization', 'Bearer '.$request->cookie($cookieName));
        }

        return $next($request);
    }
}
