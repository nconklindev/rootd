<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class SecurityController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        $twoFactorEnabled = $user?->hasEnabledTwoFactorAuthentication() ?? false;

        $isConfirming = false;
        if ($user) {
            // Confirm flow: secret set but not confirmed yet
            $isConfirming = !is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at);
        }

        $status = (string)$request->session()->get('status', '');

        return Inertia::render('settings/Security', [
            'twoFactorEnabled' => $twoFactorEnabled,
            'isConfirming' => $isConfirming,
            'backupCodesGenerated' => $status === Fortify::RECOVERY_CODES_GENERATED,
        ]);
    }
}
