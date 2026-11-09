@extends('layouts.app')

@section('page-title', 'Profile')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <section class="rounded-2xl bg-white p-6 shadow dark:bg-gray-800 sm:p-8">
        @include('profile.partials.update-profile-information-form')
    </section>

    @if($user->role === 'staff')
    <section class="rounded-2xl bg-white p-6 shadow dark:bg-gray-800 sm:p-8">
        <h2 class="text-lg font-semibold text-gray-900">Commission payment account</h2>
        <p class="mt-1 text-sm text-gray-600">These details are shown to the revenue owner when you submit a commission claim.</p>
        @if(session('status') === 'bank-details-updated')<p class="mt-3 text-sm text-green-700">Account details saved.</p>@endif
        <form method="POST" action="{{ route('profile.bank-details.update') }}" class="mt-5 space-y-4">
            @csrf @method('PATCH')
            <div><label for="bank_name" class="block text-sm font-medium">Bank name</label><input id="bank_name" name="bank_name" value="{{ old('bank_name', $user->bank_name) }}" class="mt-1 w-full rounded-lg border-gray-300" maxlength="120"></div>
            <div><label for="bank_account_name" class="block text-sm font-medium">Account name</label><input id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name', $user->bank_account_name) }}" class="mt-1 w-full rounded-lg border-gray-300" maxlength="160"></div>
            <div><label for="bank_account_number" class="block text-sm font-medium">Account number</label><input id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $user->bank_account_number) }}" class="mt-1 w-full rounded-lg border-gray-300" maxlength="32"></div>
            @foreach(['bank_name', 'bank_account_name', 'bank_account_number'] as $field) @error($field)<p class="text-sm text-red-600">{{ $message }}</p>@enderror @endforeach
            <button class="rounded-lg bg-primary px-5 py-2 font-semibold text-white">Save account details</button>
        </form>
    </section>
    @endif

    <section class="rounded-2xl bg-white p-6 shadow dark:bg-gray-800 sm:p-8">
        @include('profile.partials.update-password-form')
    </section>

</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('signature');
    const preview = document.getElementById('signature-preview');
    const placeholder = document.getElementById('signature-preview-placeholder');
    const removeCheckbox = document.getElementById('remove-signature');
    const savedSignature = @js($signatureDataUri);

    input?.addEventListener('change', () => {
        const file = input.files?.[0];
        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            if (removeCheckbox) removeCheckbox.checked = false;
            return;
        }

        if (savedSignature) {
            preview.src = savedSignature;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        } else {
            preview.removeAttribute('src');
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
        }
    });
});
</script>
@endsection
