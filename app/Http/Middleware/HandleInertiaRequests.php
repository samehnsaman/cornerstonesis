<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => ['user' => $request->user() ? [
                ...$request->user()->only('id', 'name', 'email', 'locale', 'must_change_password'),
                'can_admin' => $request->user()->hasPermission('admin.access'),
            ] : null],
            'locale' => app()->getLocale(),
            'demo' => true,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'temporary_password' => fn () => $request->session()->get('temporary_password'),
                'temporary_recovery_codes' => fn () => $request->session()->get('temporary_recovery_codes'),
            ],
        ];
    }
}
