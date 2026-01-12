<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema;

use Closure;
use Illuminate\Http\Request;

/**
 * Manages authorization for Laravel Schema dashboard access.
 *
 * This class follows the same pattern as Laravel Horizon/Telescope for
 * configuring authorization via a callback.
 */
final class AuthorizationManager
{
    /**
     * The callback that should be used to authenticate users.
     */
    private static ?Closure $authCallback = null;

    /**
     * Register the authorization callback.
     *
     * @param  Closure(Request): bool  $callback
     */
    public static function gate(Closure $callback): void
    {
        self::$authCallback = $callback;
    }

    /**
     * Check if the given request is authorized.
     *
     * By default, access is only allowed in local environment for security.
     * Configure a custom callback using gate() for production access.
     */
    public static function check(Request $request): bool
    {
        if (self::$authCallback === null) {
            // Default to deny in production (fail-closed security)
            return app()->environment('local');
        }

        return (bool) (self::$authCallback)($request);
    }

    /**
     * Get the authorization callback.
     */
    public static function getCallback(): ?Closure
    {
        return self::$authCallback;
    }

    /**
     * Clear the authorization callback.
     *
     * Useful for testing to reset state between tests.
     */
    public static function clear(): void
    {
        self::$authCallback = null;
    }
}
