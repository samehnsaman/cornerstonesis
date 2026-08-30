<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireStaffMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ($user->status ?? 'active') !== 'active') {
            abort(403, __('auth.account_inactive'));
        }

        if ($user?->must_change_password && ! $request->routeIs('password.change.*', 'logout')) {
            return redirect()->route('password.change.form');
        }

        if ($user?->mfa_required && $user->isPrivileged()
            && $request->session()->get('mfa_user_id') !== $user->id
            && ! $request->routeIs('mfa.*', 'logout')) {
            return redirect()->route('mfa.challenge');
        }

        return $next($request);
    }
}
