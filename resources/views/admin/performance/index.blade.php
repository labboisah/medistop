@extends('layouts.app')

@section('page-title', 'Production Performance')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-primary">Production Performance</h2>
            <p class="mt-1 text-sm text-gray-600">Request, database, queue, storage, imaging upload, and export measurements.</p>
            <p class="mt-2 text-xs text-gray-500">{{ $report['windowLabel'] }} · from {{ $report['start']->format('d M Y, H:i') }} · generated {{ now()->format('d M Y, H:i:s') }}</p>
            <p class="mt-1 text-xs text-gray-500">Laravel cannot measure requests rejected by the proxy before reaching PHP, host CPU, or client network time.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('admin.performance.index') }}" class="flex items-center gap-2">
                <label for="monitoring-window" class="text-sm font-medium">Period</label>
                <select id="monitoring-window" name="window" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm">
                    <option value="24h" @selected($window === '24h')>24 hours</option>
                    <option value="7d" @selected($window === '7d')>7 days</option>
                    <option value="30d" @selected($window === '30d')>30 days</option>
                </select>
            </form>
            <a href="{{ route('admin.performance.export', ['window' => $window]) }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-secondary">Download CSV report</a>
        </div>
    </div>

    @unless($report['enabled'])
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Request collection is off.</strong>
            Set <code>PERFORMANCE_MONITORING_ENABLED=true</code> in the production environment, then refresh Laravel's configuration cache. This screen currently shows any previously collected records and live infrastructure checks.
        </div>
    @endunless

    <section aria-labelledby="system-resources-title">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h3 id="system-resources-title" class="text-lg font-bold text-primary">System resources</h3>
                <p class="mt-1 text-xs text-gray-500">Host samples are collected every five minutes. Charts cover {{ $report['windowLabel'] }}; CPU is load normalized by detected cores, not instantaneous CPU utilization.</p>
            </div>
            <span class="text-xs text-gray-500">Latest sample: {{ $report['latestResource']?->created_at?->format('d M Y, H:i') ?? 'not available' }}</span>
        </div>
        @if(count($report['resourceWarnings']))
            <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="mb-1 font-semibold">Areas to review</p>
                <ul class="list-inside list-disc space-y-1">
                    @foreach($report['resourceWarnings'] as $warning)<li>{{ $warning }}</li>@endforeach
                </ul>
            </div>
        @else
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">No resource thresholds are currently reporting a concern.</div>
        @endif
        @php($host = $report['latestResource'])
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">CPU load / cores</p><p class="mt-2 text-2xl font-bold">{{ $host?->cpu_load_percent === null ? 'Unavailable' : number_format($host->cpu_load_percent, 1).'%' }}</p><p class="mt-1 text-xs text-gray-500">Review above {{ $report['cpuWarningThreshold'] }}%</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Host RAM used</p><p class="mt-2 text-2xl font-bold">{{ $host?->memory_total_bytes ? number_format($host->memory_used_bytes / 1073741824, 1).' / '.number_format($host->memory_total_bytes / 1073741824, 1).' GB' : 'Unavailable' }}</p><p class="mt-1 text-xs text-gray-500">{{ $host?->memory_total_bytes ? number_format($host->memory_used_bytes * 100 / $host->memory_total_bytes, 1).'% used · review above '.$report['memoryWarningThreshold'].'%' : 'Host memory counters unavailable' }}</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Network received</p><p class="mt-2 text-2xl font-bold">{{ $host?->network_in_bytes_per_second === null ? 'Unavailable' : number_format($host->network_in_bytes_per_second / 1048576, 2).' MB/s' }}</p><p class="mt-1 text-xs text-gray-500">Inbound interface throughput</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Network sent</p><p class="mt-2 text-2xl font-bold">{{ $host?->network_out_bytes_per_second === null ? 'Unavailable' : number_format($host->network_out_bytes_per_second / 1048576, 2).' MB/s' }}</p><p class="mt-1 text-xs text-gray-500">Outbound interface throughput</p></div>
        </div>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            @foreach([['CPU load (% of cores)', 'cpu', 'text-sky-600', 'fill-sky-100'], ['RAM used (%)', 'memory', 'text-violet-600', 'fill-violet-100'], ['Network received (MB/s)', 'networkIn', 'text-emerald-600', 'fill-emerald-100'], ['Network sent (MB/s)', 'networkOut', 'text-orange-600', 'fill-orange-100']] as [$title, $key, $lineColor, $areaColor])
                <div class="rounded-xl bg-white p-5 shadow">
                    <h4 class="text-sm font-semibold text-gray-700">{{ $title }}</h4>
                    @if($report['resourceCharts'][$key]['line'])
                        <svg viewBox="0 0 100 60" preserveAspectRatio="none" class="mt-3 h-32 w-full overflow-visible" role="img" aria-label="{{ $title }} over {{ $report['windowLabel'] }}">
                            <polygon points="{{ $report['resourceCharts'][$key]['area'] }}" class="{{ $areaColor }}" />
                            <polyline points="{{ $report['resourceCharts'][$key]['line'] }}" fill="none" stroke="currentColor" stroke-width="1.5" vector-effect="non-scaling-stroke" class="{{ $lineColor }}" />
                        </svg>
                        <p class="mt-2 text-xs text-gray-500">Oldest sample on left · latest on right</p>
                    @else
                        <div class="mt-3 flex h-32 items-center justify-center rounded bg-gray-50 text-sm text-gray-500">Waiting for supported samples</div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="request-metrics-title">
        <h3 id="request-metrics-title" class="mb-3 text-lg font-bold text-primary">Request performance</h3>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Requests</p><p class="mt-2 text-2xl font-bold">{{ number_format($report['requestCount']) }}</p><p class="mt-1 text-xs text-gray-500">in selected period</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Average response</p><p class="mt-2 text-2xl font-bold">{{ number_format($report['averageDuration'], 1) }} ms</p><p class="mt-1 text-xs text-gray-500">P50 {{ number_format($report['p50Duration']) }} ms · P95 {{ number_format($report['p95Duration']) }} ms</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">HTTP 5xx errors</p><p class="mt-2 text-2xl font-bold {{ $report['errorCount'] ? 'text-red-600' : 'text-green-700' }}">{{ number_format($report['errorCount']) }}</p><p class="mt-1 text-xs text-gray-500">{{ number_format($report['errorRate'], 2) }}% of measured requests</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Slow requests</p><p class="mt-2 text-2xl font-bold {{ $report['slowRequestCount'] ? 'text-amber-700' : 'text-green-700' }}">{{ number_format($report['slowRequestCount']) }}</p><p class="mt-1 text-xs text-gray-500">≥ {{ number_format($report['slowRequestThreshold']) }} ms</p></div>
        </div>
    </section>

    <section aria-labelledby="database-metrics-title">
        <h3 id="database-metrics-title" class="mb-3 text-lg font-bold text-primary">Database and workload</h3>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Database connection</p><p class="mt-2 text-xl font-bold {{ $report['databaseConnected'] ? 'text-green-700' : 'text-red-600' }}">{{ $report['databaseConnected'] ? 'Connected' : 'Unavailable' }}</p><p class="mt-1 text-xs text-gray-500">{{ $report['databaseDriver'] }} · avg query time {{ number_format($report['averageDatabaseTime'], 1) }} ms/request</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Database queries</p><p class="mt-2 text-2xl font-bold">{{ number_format($report['averageQueryCount'], 1) }} <span class="text-sm font-normal">avg/request</span></p><p class="mt-1 text-xs text-gray-500">{{ number_format($report['slowQueryCount']) }} slow queries ≥ {{ number_format($report['slowQueryThreshold']) }} ms</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Queue</p><p class="mt-2 text-xl font-bold">{{ $report['queueDriver'] }}</p><p class="mt-1 text-xs text-gray-500">Queued {{ $report['queuedJobs'] === null ? 'unavailable' : number_format($report['queuedJobs']) }} · failed {{ $report['failedJobs'] === null ? 'unavailable' : number_format($report['failedJobs']) }}</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Storage volume</p><p class="mt-2 text-xl font-bold">{{ $report['diskFreeBytes'] === null ? 'Unavailable' : number_format($report['diskFreeBytes'] / 1073741824, 2).' GB free' }}</p><p class="mt-1 text-xs text-gray-500">{{ $report['diskTotalBytes'] === null ? 'Free space could not be read' : 'of '.number_format($report['diskTotalBytes'] / 1073741824, 2).' GB total · app storage volume' }}</p></div>
        </div>
    </section>

    <section aria-labelledby="workflow-metrics-title">
        <h3 id="workflow-metrics-title" class="mb-3 text-lg font-bold text-primary">Imaging and reporting activity</h3>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Imaging files uploaded</p><p class="mt-2 text-2xl font-bold">{{ number_format($report['uploadCount']) }}</p><p class="mt-1 text-xs text-gray-500">{{ number_format($report['uploadBytes'] / 1073741824, 2) }} GB stored in period</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Report exports</p><p class="mt-2 text-2xl font-bold">{{ number_format($report['exportRequests']) }}</p><p class="mt-1 text-xs text-gray-500">PDF, CSV, and Excel export requests</p></div>
            <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">PHP runtime</p><p class="mt-2 text-xl font-bold">{{ $report['phpVersion'] }}</p><p class="mt-1 text-xs text-gray-500">Monitoring request memory {{ number_format($report['phpMemoryBytes'] / 1048576, 1) }} MB · peak {{ number_format($report['phpPeakMemoryBytes'] / 1048576, 1) }} MB</p></div>
        </div>
        <p class="mt-2 text-xs text-gray-500">PHP memory is sampled during this monitoring page request, not a server-wide or historical per-request measurement.</p>
        <p class="mt-1 text-xs text-gray-500">Upload totals count files saved by MediStop; proxy or PHP upload rejections happen before this application metric is recorded.</p>
    </section>

    <section class="overflow-hidden rounded-2xl bg-white shadow" aria-labelledby="route-report-title">
        <div class="border-b px-5 py-4">
            <h3 id="route-report-title" class="font-bold text-primary">Slowest routes by average response</h3>
            <p class="mt-1 text-xs text-gray-500">Route names only; request parameters and patient data are not stored.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-lightbg text-xs uppercase text-gray-600"><tr><th class="px-4 py-3">Route</th><th class="px-4 py-3">Requests</th><th class="px-4 py-3">Average</th><th class="px-4 py-3">Maximum</th><th class="px-4 py-3">5xx</th><th class="px-4 py-3">Slow DB queries</th></tr></thead>
                <tbody>
                    @forelse($report['routes'] as $route)
                        <tr class="border-t"><td class="px-4 py-3 font-medium">{{ $route->route_name }}</td><td class="px-4 py-3">{{ number_format($route->request_count) }}</td><td class="px-4 py-3">{{ number_format($route->average_duration_ms, 1) }} ms</td><td class="px-4 py-3">{{ number_format($route->maximum_duration_ms) }} ms</td><td class="px-4 py-3">{{ number_format($route->error_count) }}</td><td class="px-4 py-3">{{ number_format($route->slow_query_count) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No request measurements for this period yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl bg-white shadow" aria-labelledby="recent-request-title">
        <div class="border-b px-5 py-4"><h3 id="recent-request-title" class="font-bold text-primary">Recent measured requests</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-lightbg text-xs uppercase text-gray-600"><tr><th class="px-4 py-3">Time</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Route</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Response</th><th class="px-4 py-3">Queries</th><th class="px-4 py-3">DB time</th></tr></thead>
                <tbody>
                    @forelse($report['recent'] as $metric)
                        <tr class="border-t"><td class="px-4 py-3">{{ $metric->created_at->format('d M H:i:s') }}</td><td class="px-4 py-3">{{ $metric->method }}</td><td class="px-4 py-3">{{ $metric->route_name }}</td><td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $metric->status_code >= 500 ? 'bg-red-100 text-red-800' : ($metric->status_code >= 400 ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800') }}">{{ $metric->status_code }}</span></td><td class="px-4 py-3">{{ number_format($metric->duration_ms) }} ms</td><td class="px-4 py-3">{{ number_format($metric->query_count) }}</td><td class="px-4 py-3">{{ number_format($metric->database_time_ms) }} ms</td></tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No recent request records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
