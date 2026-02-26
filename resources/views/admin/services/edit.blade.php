@extends('layouts.app')

@section('title', 'Edit Service')
@section('page-title', 'Edit Service')

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
            Edit Service
        </h2>

        <form action="{{ route('admin.services.update', $service) }}"
              method="POST"
              class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Service Name
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $service->name) }}"
                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                @error('name')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Category
                </label>

                <select name="category_id"
                        class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ $service->category_id == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach

                </select>
            </div>

            <!-- Price -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Price (₦)
                </label>

                <input type="number"
                       step="0.01"
                       name="price"
                       value="{{ old('price', $service->price) }}"
                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">
            </div>

            <!-- Actions -->
            <div class="flex justify-between items-center">

                <form action="{{ route('admin.services.destroy', $service) }}"
                      method="POST"
                      onsubmit="return confirm('Delete this service?')">
                    @csrf
                    @method('DELETE')

                    <button class="text-red-600 text-sm hover:underline">
                        Delete Service
                    </button>
                </form>

                <button type="submit"
                        class="bg-secondary text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary transition shadow">
                    Update Service
                </button>

            </div>

        </form>

    </div>

</div>

@endsection