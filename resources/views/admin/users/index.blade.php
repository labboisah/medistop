@extends('layouts.app')

@section('page-title', 'Manage Users')

@section('content')

<div class="mb-6 flex justify-between">
    <h2 class="text-xl font-bold text-primary">Users</h2>

    <a href="{{ route('admin.users.create') }}"
       class="bg-accent text-white px-5 py-2 rounded-lg">
        + Add User
    </a>
</div>

<div class="bg-white rounded-xl shadow p-6">

    <table class="w-full text-left text-sm">
        <thead class="border-b">
            <tr>
                <th class="py-3">Name</th>
                <th>Email</th>
                <th>Role</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>

        <tbody>
        @foreach($users as $user)
            <tr class="border-b">
                <td class="py-3">{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <span class="px-3 py-1 rounded-full text-xs
                        {{ $user->role === 'admin' ? 'bg-primary text-white' : 'bg-lightbg text-secondary' }}">
                        {{ strtoupper($user->role) }}
                    </span>
                </td>
                <td class="text-right">
                    <a href="{{ route('admin.users.edit', $user) }}"
                       class="text-blue-600 hover:underline">
                        Edit
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="mt-6">
        {{ $users->links() }}
    </div>

</div>

@endsection