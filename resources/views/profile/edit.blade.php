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
