@php
    $filterSearch = $search ?? request('search', '');
    $filterFrom = $from ?? request('from', '');
    $filterTo = $to ?? request('to', '');
    $filterPlaceholder = $searchPlaceholder ?? 'Search this table...';
@endphp

<form method="GET" action="{{ route($routeName) }}" class="mb-4 rounded-xl border-b border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-secondary">
    @foreach(request()->except(['page', 'search', 'from', 'to']) as $key => $value)
        @if(is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <div class="flex items-end gap-3 overflow-x-auto" style="display:flex; flex-direction:row; flex-wrap:nowrap; align-items:flex-end; gap:.75rem; overflow-x:auto;">
        <div class="relative min-w-0 flex-1" style="flex:1 1 auto; min-width:16rem;">
            <label for="table-filter-search" class="sr-only">{{ $filterPlaceholder }}</label>
            <input id="table-filter-search" name="search" type="search" value="{{ $filterSearch }}" placeholder="{{ $filterPlaceholder }}" oninput="clearTimeout(this._filterTimer); this._filterTimer = setTimeout(() => this.form.requestSubmit(), 400)" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
        </div>
        <div class="shrink-0" style="flex:0 0 11rem;">
            <label for="table-filter-from" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">From date</label>
            <input id="table-filter-from" name="from" type="date" value="{{ $filterFrom }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
        </div>
        <div class="shrink-0" style="flex:0 0 11rem;">
            <label for="table-filter-to" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">To date</label>
            <input id="table-filter-to" name="to" type="date" value="{{ $filterTo }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
        </div>
    </div>
</form>
