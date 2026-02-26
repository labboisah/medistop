@extends('layouts.app')

@section('title', 'Create Service')
@section('page-title', 'Create Service')

@section('content')

<div class="max-w-3xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('admin.services.index') }}"
           class="text-sm text-primary hover:underline">
            ← Back to Services
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-lg p-8">

        <h2 class="text-2xl font-bold text-primary mb-6">
            Add New Service
        </h2>

        <form action="{{ route('admin.services.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Service Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Service Name
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name') }}"
                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                @error('name')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Category
                </label>

                <select name="category_id"
                        class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">
                    <option value="">Select Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                @error('category_id')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Price -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Price (₦)
                </label>

                <input type="number"
                       step="0.01"
                       name="price"
                       value="{{ old('price') }}"
                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                @error('price')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-accent text-white px-8 py-3 rounded-xl font-semibold hover:bg-green-600 transition shadow">
                    Save Service
                </button>
            </div>

        </form>

    </div>

</div>

@endsection