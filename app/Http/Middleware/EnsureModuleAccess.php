<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        $moduleSlug = Str::slug($module);

        $hasModuleAccess = $user !== null
            && $user->getAllPermissions()->contains(
                static fn ($permission): bool => Str::slug((string) $permission->category) === $moduleSlug
            );

        if (! $hasModuleAccess) {
            throw new HttpException(403, 'You do not have access to this module.');
        }

        return $next($request);
    }
}
