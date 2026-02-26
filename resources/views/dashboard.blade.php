@extends('layouts.app')

@section('content')
    @if(auth()->user()->role == 'admin')
        @include('admin.dashboard')
    @elseif(auth()->user()->role == 'user')
        @include('users.dashboard')
    @endif
@endsection