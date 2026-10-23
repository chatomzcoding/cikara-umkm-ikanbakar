<?php

namespace App\Http\Controllers;

use App\Services\UserImpersonationService;
use Illuminate\Http\RedirectResponse;

class UserImpersonationController extends Controller
{
    public function destroy(UserImpersonationService $impersonation): RedirectResponse
    {
        $user = $impersonation->stop();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun admin asal sudah tidak aktif. Silakan login kembali.',
            ]);
        }

        return redirect()->route('superadmin.sistem.user.index')
            ->with('status', 'Impersonasi selesai. Anda kembali ke akun admin.');
    }
}
