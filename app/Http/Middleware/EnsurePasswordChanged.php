<?php

namespace App\Http\Middleware;

use App\Filament\Pages\AccountSecurity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user?->must_change_password
            && ! $request->routeIs(AccountSecurity::getRouteName())
            && ! $request->routeIs('filament.admin.auth.logout')
        ) {
            return redirect(AccountSecurity::getUrl());
        }

        return $next($request);
    }
}
