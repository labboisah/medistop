@extends('layouts.app')

@section('page-title', 'Create User')

@section('content')

<div class="max-w-3xl mx-auto bg-white p-8 rounded-xl shadow">

<form method="POST" action="{{ route('admin.users.store') }}">
@csrf

<div class="space-y-6">

<input type="text" name="name" placeholder="Full Name"
class="w-full px-4 py-3 border rounded-xl">

<input type="email" name="email" placeholder="Email"
class="w-full px-4 py-3 border rounded-xl">

<input type="password" name="password" placeholder="Password"
class="w-full px-4 py-3 border rounded-xl">

<input type="password" name="password_confirmation"
placeholder="Confirm Password"
class="w-full px-4 py-3 border rounded-xl">

<select name="role"
class="w-full px-4 py-3 border rounded-xl">
<option value="user">User</option>
<option value="admin">Admin</option>
</select>

<button class="bg-accent text-white px-6 py-3 rounded-xl">
Create User
</button>

</div>

</form>

</div>

@endsection