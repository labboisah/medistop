<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        abort_if($request->user()->is($user), 422, 'You are already signed in to this account.');
        abort_if($request->session()->has('impersonator_admin_id'), 403, 'You are already impersonating an account.');

        $request->session()->put('impersonator_admin_id', $request->user()->id);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->role === 'staff' ? 'staff.dashboard' : 'dashboard')
            ->with('success', "You are now impersonating {$user->name}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull('impersonator_admin_id');
        abort_unless($adminId, 403, 'No impersonation session is active.');

        $admin = User::findOrFail($adminId);
        Auth::login($admin);
        $request->session()->regenerate();

        $destination = match ($admin->role) {
            'admin' => 'admin.users.index',
            'staff' => 'staff.dashboard',
            default => 'dashboard',
        };

        return redirect()->route($destination)
            ->with('success', 'You have returned to your original account.');
    }
}
