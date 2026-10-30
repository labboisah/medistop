@php
    $filterSearch = $search ?? request('search', '');
    $filterFrom = $from ?? request('from', '');
    $filterTo = $to ?? request('to', '');
    $filterPlaceholder = $searchPlaceholder ?? 'Search this table...';
    $filterStatus = $status ?? request('status', '');
    $filterStatusOptions = $statusOptions ?? [];
    $filterStatusLabel = $statusLabel ?? 'Status';
    $filterId = 'table-filter-'.\Illuminate\Support\Str::uuid();
    $preservedQuery = request()->except(['page', 'search', 'from', 'to']);
    if (count($filterStatusOptions)) {
        unset($preservedQuery['status']);
    }
@endphp

<form method="GET" action="{{ route($routeName) }}" class="mb-4 rounded-xl border-b border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-secondary">
    @foreach($preservedQuery as $key => $value)
        @if(is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <div class="flex flex-col gap-3 md:flex-row md:items-end">
        <div class="relative min-w-0 flex-1">
            <label for="{{ $filterId }}-search" class="sr-only">{{ $filterPlaceholder }}</label>
            <input id="{{ $filterId }}-search" name="search" type="search" value="{{ $filterSearch }}" placeholder="{{ $filterPlaceholder }}" oninput="clearTimeout(this._filterTimer); this._filterTimer = setTimeout(() => this.form.requestSubmit(), 400)" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
        </div>
        <div class="w-full shrink-0 md:w-44">
            <label for="{{ $filterId }}-from" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">From date</label>
            <input id="{{ $filterId }}-from" name="from" type="date" value="{{ $filterFrom }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
        </div>
        <div class="w-full shrink-0 md:w-44">
            <label for="{{ $filterId }}-to" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">To date</label>
            <input id="{{ $filterId }}-to" name="to" type="date" value="{{ $filterTo }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
        </div>
        @if(count($filterStatusOptions))
            <div class="w-full shrink-0 md:w-56">
                <label for="{{ $filterId }}-status" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">{{ $filterStatusLabel }}</label>
                <select id="{{ $filterId }}-status" name="status" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-gray-600 dark:bg-primary dark:text-white">
                    @foreach($filterStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $filterStatus === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="flex shrink-0 items-center gap-2">
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-secondary">Apply</button>
            <a href="{{ route($routeName, collect($preservedQuery)->filter(fn ($value) => is_scalar($value))->all()) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Reset</a>
        </div>
    </div>
</form>
