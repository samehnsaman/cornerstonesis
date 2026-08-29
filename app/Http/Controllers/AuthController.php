<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function loginForm(): Response { return Inertia::render('Auth/Login'); }
    public function registerForm(): Response { return Inertia::render('Auth/Register'); }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
        }
        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);
        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'given_name' => ['required', 'string', 'max:100'], 'family_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'confirmed', 'min:12'], 'locale' => ['required', 'in:en,ar'],
        ]);
        $user = DB::transaction(function () use ($data): User {
            $user = User::create(['name' => $data['given_name'].' '.$data['family_name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'locale' => $data['locale']]);
            Person::create(['user_id' => $user->id, 'external_id' => 'APP-'.Str::upper(Str::random(10)), 'given_name' => $data['given_name'], 'family_name' => $data['family_name'], 'email' => $data['email'], 'locale' => $data['locale']]);
            return $user;
        });
        event(new Registered($user));
        Auth::login($user);
        return redirect()->route('dashboard')->with('success', __('app.welcome'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function locale(Request $request): RedirectResponse
    {
        $locale = $request->validate(['locale' => ['required', 'in:en,ar']])['locale'];
        $request->session()->put('locale', $locale); $request->user()?->update(['locale' => $locale]);
        return back();
    }
}
