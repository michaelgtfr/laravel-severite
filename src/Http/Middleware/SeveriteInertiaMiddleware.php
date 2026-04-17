<?php

namespace Severite\Http\Middleware;

use Closure;
use Inertia\Inertia;

class SeveriteInertiaMiddleware
{
    public function handle($request, Closure $next)
    {
        Inertia::setRootView('severite');
        return $next($request);
    }
}
