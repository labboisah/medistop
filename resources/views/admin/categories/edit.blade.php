@extends('layouts.app')

@section('title', 'Edit Category')
@section('page-title', 'Edit Service Category')

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
            Edit Category
        </h2>

        <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Category Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Category Name
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $category->name) }}"
                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                @error('name')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex justify-between items-center">

                

                <!-- Update -->
                <button type="submit"
                        class="bg-secondary text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary transition shadow">
                    Update Category
                </button>

            </div>

        </form>

        <!-- Delete -->
        <form action="{{ route('admin.categories.destroy', $category) }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to delete this category?')">
            @csrf
            @method('DELETE')

            <button class="text-red-600 hover:underline text-sm">
                Delete Category
            </button>
        </form>

    </div>

</div>

@endsection