@extends('layouts.app')

@section('title', 'Create Category')
@section('page-title', 'Create Service Category')

@section('content')

<div class="max-w-3xl mx-auto">

    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('admin.categories.index') }}"
           class="text-sm text-primary hover:underline">
            ← Back to Categories
        </a>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-lg p-8">

        <h2 class="text-2xl font-bold text-primary mb-6">
            Add New Category
        </h2>

        <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Category Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Category Name
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name') }}"
                       placeholder="e.g. Radiological Services"
                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                @error('name')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-accent text-white px-8 py-3 rounded-xl font-semibold hover:bg-green-600 transition shadow">
                    Save Category
                </button>
            </div>

        </form>

    </div>

</div>

@endsection