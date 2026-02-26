@extends('layouts.app')

@section('title', 'Services')
@section('page-title', 'Manage Services')

@section('content')

<div class="mb-6 flex justify-between items-center">
    <h2 class="text-xl font-bold text-primary">All Services</h2>

    <a href="{{ route('admin.services.create') }}"
       class="bg-accent text-white px-5 py-2 rounded-lg shadow hover:bg-green-600 transition">
        + Add Service
    </a>
</div>

<div class="bg-white rounded-2xl shadow p-6 overflow-x-auto">

    <table class="w-full text-left text-sm">

        <thead class="border-b">
            <tr>
                <th class="py-3">Service Name</th>
                <th>Category</th>
                <th>Price (₦)</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>

        <tbody>

            @foreach($services as $service)
                <tr class="border-b hover:bg-lightbg transition">

                    <td class="py-3 font-medium">
                        {{ $service->name }}
                    </td>

                    <td>
                        <span class="px-3 py-1 bg-lightbg rounded-full text-xs text-secondary font-semibold">
                            {{ $service->category->name }}
                        </span>
                    </td>

                    <td class="font-semibold text-primary">
                        {{ number_format($service->price, 2) }}
                    </td>

                    <td class="text-right space-x-3">
                        <a href="{{ route('admin.services.edit', $service) }}"
                           class="text-blue-600 hover:underline">
                            Edit
                        </a>

                        <form action="{{ route('admin.services.destroy', $service) }}"
                              method="POST"
                              class="inline"
                              onsubmit="return confirm('Delete this service?')">
                            @csrf
                            @method('DELETE')

                            <button class="text-red-600 hover:underline">
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