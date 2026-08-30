<?php

namespace App\Http\Controllers;

use App\Models\MfaChallenge;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MfaController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Auth/Mfa', ['email' => Str::mask($request->user()->email, '*', 2, max(1, strlen($request->user()->email) - 6))]);
    }

    public function send(Request $request): RedirectResponse
    {
        $key = 'mfa-send:'.$request->user()->id.':'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($key, 3), 429);
        RateLimiter::hit($key, 60);

        abort_if(app()->environment('production') && config('mail.default') === 'log', 503, 'SMTP must be configured for staff MFA.');
        $code = (string) random_int(100000, 999999);
        MfaChallenge::where('user_id', $request->user()->id)->whereNull('used_at')->update(['used_at' => now()]);
        MfaChallenge::create(['user_id' => $request->user()->id, 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(10)]);
        Mail::raw("Your Cornerstone SIS verification code is {$code}. It expires in 10 minutes.", fn ($message) => $message->to($request->user()->email)->subject('Cornerstone SIS verification code'));

        return back()->with('success', __('auth.code_sent'));
    }

    public function verify(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:32']]);
        $recoveryCodes = $request->user()->mfa_recovery_codes ?? [];
        $recoveryIndex = collect($recoveryCodes)->search(fn ($hash) => Hash::check(Str::upper($data['code']), $hash));
        if ($recoveryIndex !== false) {
            unset($recoveryCodes[$recoveryIndex]);
            $request->user()->update(['mfa_recovery_codes' => array_values($recoveryCodes), 'mfa_verified_at' => now()]);
            $request->session()->put('mfa_user_id', $request->user()->id);
            $audit->record('auth.mfa_recovery_used', $request->user());

            return redirect()->intended(route('admin.dashboard'));
        }
        $challenge = MfaChallenge::where('user_id', $request->user()->id)->whereNull('used_at')->where('expires_at', '>', now())->latest()->first();
        if (! $challenge || $challenge->attempts >= 5 || ! Hash::check($data['code'], $challenge->code_hash)) {
            $challenge?->increment('attempts');

            return back()->withErrors(['code' => __('auth.invalid_code')]);
        }

        $challenge->update(['used_at' => now()]);
        $request->session()->put('mfa_user_id', $request->user()->id);
        $request->user()->update(['mfa_verified_at' => now()]);
        $audit->record('auth.mfa_verified', $request->user());

        return redirect()->intended(route('admin.dashboard'));
    }
}
