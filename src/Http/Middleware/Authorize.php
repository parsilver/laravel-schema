<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Http\Middleware;

use Closure;
use Farzai\LaravelSchema\AuthorizationManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class Authorize
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('schema.authorization.enabled', true)) {
            return $next($request);
        }

        if (AuthorizationManager::check($request)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access to Laravel Schema.');
    }
}
