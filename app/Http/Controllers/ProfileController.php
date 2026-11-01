<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $signatureDataUri = null;

        if ($user->signature_path && Storage::disk('local')->exists($user->signature_path)) {
            $signatureMimeType = in_array($user->signature_mime_type, ['image/png', 'image/jpeg', 'image/webp'], true)
                ? $user->signature_mime_type
                : 'image/png';
            $signatureDataUri = 'data:'.$signatureMimeType.';base64,'.base64_encode(
                Storage::disk('local')->get($user->signature_path)
            );
        }

        return view('profile.edit', [
            'user' => $user,
            'signatureDataUri' => $signatureDataUri,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $disk = Storage::disk('local');

        if ($request->boolean('remove_signature') && ! $request->hasFile('signature')) {
            if ($user->signature_path) {
                $disk->delete($user->signature_path);
            }

            $user->signature_path = null;
            $user->signature_mime_type = null;
        }

        if ($request->hasFile('signature')) {
            if ($user->signature_path) {
                $disk->delete($user->signature_path);
            }

            $signature = $request->file('signature');
            $user->signature_path = $signature->store('user-signatures', 'local');
            $user->signature_mime_type = $signature->getMimeType();
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->signature_path) {
            Storage::disk('local')->delete($user->signature_path);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
