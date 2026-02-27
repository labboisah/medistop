@extends('layouts.app')

@section('page-title','Financial Reports')

@section('content')

<div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow">

<form method="POST" action="{{ route('admin.reports.generate') }}" class="space-y-6">
@csrf

<div class="grid md:grid-cols-3 gap-6">

    <div>
        <label class="block text-sm mb-2">From Date</label>
        <input type="date" name="from" class="w-full px-4 py-3 border rounded-xl">
    </div>

    <div>
        <label class="block text-sm mb-2">To Date</label>
        <input type="date" name="to" class="w-full px-4 py-3 border rounded-xl">
    </div>

    <div class="flex items-center gap-3 mt-8">
        <input type="checkbox" name="today" value="1">
        <span>Today Only</span>
    </div>

</div>

<div class="flex gap-4 mt-6">

    <button name="type" value="summary"
        class="bg-primary text-white px-6 py-3 rounded-xl">
        View Summary
    </button>

    <button name="type" value="detailed"
        class="bg-secondary text-white px-6 py-3 rounded-xl">
        View Detailed
    </button>

</div>

</form>

</div>

@endsection