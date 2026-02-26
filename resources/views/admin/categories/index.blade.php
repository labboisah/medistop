@extends('layouts.app')

@section('page-title', 'Service Categories')

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.categories.create') }}"
       class="bg-accent text-white px-5 py-2 rounded-lg">
        + Add Category
    </a>
</div>

<div class="bg-white rounded-xl shadow p-6">

    <table class="w-full text-left">
        <thead class="border-b">
            <tr>
                <th class="py-3">Name</th>
                <th>Services</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>

        <tbody>
            @foreach($categories as $category)
                <tr class="border-b">
                    <td class="py-3">{{ $category->name }}</td>
                    <td>{{ $category->services->count() }}</td>
                    <td class="text-right space-x-2">
                        <a href="{{ route('admin.categories.edit', $category) }}"
                           class="text-blue-600">Edit</a>

                        <form action="{{ route('admin.categories.destroy', $category) }}"
                              method="POST"
                              class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600" onclick="return confirm('Are you sure you want to delete this category of services?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection