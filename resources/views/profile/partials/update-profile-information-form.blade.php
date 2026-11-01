<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-300">
            <p><strong>Account type:</strong> {{ ucfirst($user->role) }}{{ $user->staff_type ? ' · '.ucfirst($user->staff_type) : '' }}</p>
            @if($user->designation)
                <p class="mt-1"><strong>Designation:</strong> {{ $user->designation }}</p>
            @endif
            <p class="mt-1">Role, staff type, and designation can only be changed by an administrator.</p>
        </div>

        <div class="space-y-3">
            <div>
                <x-input-label for="signature" value="Signature image" />
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Upload a PNG, JPG, or WebP image (max 2 MB). Radiologist signatures are included automatically on reports.</p>
                <input id="signature" name="signature" type="file" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full text-sm text-gray-700 dark:text-gray-300">
                <x-input-error class="mt-2" :messages="$errors->get('signature')" />
            </div>

            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Preview</p>
                <div class="flex min-h-24 items-center justify-center rounded bg-gray-50 p-3 dark:bg-gray-900">
                    <img id="signature-preview" src="{{ $signatureDataUri ?? '' }}" alt="Signature preview" class="max-h-24 max-w-full object-contain {{ $signatureDataUri ? '' : 'hidden' }}">
                    <p id="signature-preview-placeholder" class="text-sm text-gray-500 {{ $signatureDataUri ? 'hidden' : '' }}">Choose an image to preview it here.</p>
                </div>
                @if($signatureDataUri)
                    <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" id="remove-signature" name="remove_signature" value="1" class="rounded border-gray-300">
                        Remove current signature
                    </label>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
