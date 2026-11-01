@extends('layouts.app')

@section('page-title', 'Profile')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <section class="rounded-2xl bg-white p-6 shadow dark:bg-gray-800 sm:p-8">
        @include('profile.partials.update-profile-information-form')
    </section>

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
