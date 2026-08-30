<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PasswordChangeController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function update(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'confirmed', 'min:12']]);
        $request->user()->update(['password' => Hash::make($data['password']), 'must_change_password' => false]);
        $audit->record('auth.password_changed', $request->user());

        return redirect()->route('dashboard')->with('success', __('auth.password_changed'));
    }
}
