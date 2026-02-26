@extends('layouts.guest')

@section('title', 'Login')

@section('content')

<div class="mb-8 text-center">
    <h1 class="text-3xl font-extrabold text-primary">
        Welcome Back
    </h1>
    <p class="text-gray-600 mt-2">
        Login to access Annex System
    </p>
</div>

<form method="POST" action="{{ route('login') }}" class="space-y-6">
    @csrf

    <!-- Email -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Email Address
        </label>

        <input type="email"
               name="email"
               required
               autofocus
               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

        @error('email')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Password -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Password
        </label>

        <input type="password"
               name="password"
               required
               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

        @error('password')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Remember -->
    <div class="flex items-center justify-between">
        <label class="flex items-center">
            <input type="checkbox" name="remember"
                   class="rounded text-accent focus:ring-accent">
            <span class="ml-2 text-sm text-gray-600">Remember me</span>
        </label>
    </div>

    <!-- Submit -->
    <button type="submit"
            class="w-full bg-accent text-white py-3 rounded-xl font-semibold hover:bg-green-600 transition shadow-lg">
        Login
    </button>

</form>

@endsection