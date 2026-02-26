@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')

<div class="max-w-3xl mx-auto">

    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}"
           class="text-sm text-primary hover:underline">
            ← Back to Users
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-lg p-8">

        <h2 class="text-2xl font-bold text-primary mb-6">
            Edit User
        </h2>

        <form method="POST"
              action="{{ route('admin.users.update', $user) }}"
              class="space-y-6">

            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Full Name
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $user->name) }}"
                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                @error('name')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Email Address
                </label>

                <input type="email"
                       name="email"
                       value="{{ old('email', $user->email) }}"
                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                @error('email')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Role
                </label>

                <select name="role"
                        class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                    <option value="user"
                        {{ $user->role === 'user' ? 'selected' : '' }}>
                        User
                    </option>

                    <option value="admin"
                        {{ $user->role === 'admin' ? 'selected' : '' }}>
                        Admin
                    </option>

                </select>

                @error('role')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Change Password (Optional) -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    New Password (Leave blank to keep current)
                </label>

                <input type="password"
                       name="password"
                       placeholder="Enter new password"
                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                @error('password')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex justify-between items-center pt-4">

                <!-- Delete Button -->
                @if(auth()->id() !== $user->id)
                    <form method="POST"
                          action="{{ route('admin.users.destroy', $user) }}"
                          onsubmit="return confirm('Are you sure you want to delete this user?')">
                        @csrf
                        @method('DELETE')

                        <button class="text-red-600 text-sm hover:underline">
                            Delete User
                        </button>
                    </form>
                @else
                    <span class="text-gray-400 text-sm">
                        You cannot delete yourself
                    </span>
                @endif

                <!-- Update -->
                <button type="submit"
                        class="bg-secondary text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary transition shadow">
                    Update User
                </button>

            </div>

        </form>

    </div>

</div>

@endsection