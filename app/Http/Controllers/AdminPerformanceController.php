<?php

namespace App\Http\Controllers;

use App\Models\PerformanceMetric;
use App\Models\PerformanceResourceSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPerformanceController extends Controller
{
    public function index(Request $request)
    {
        $window = $request->validate(['window' => 'nullable|in:24h,7d,30d'])['window'] ?? '24h';
        $report = $this->report($window);

        return view('admin.performance.index', compact('report', 'window'));
    }

    public function export(Request $request)
    {
        $window = $request->validate(['window' => 'nullable|in:24h,7d,30d'])['window'] ?? '24h';
        $report = $this->report($window);
        $fileName = 'medistop-performance-'.$window.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['MediStop production performance report']);
            fputcsv($output, ['Generated at', now()->toDateTimeString()]);
            fputcsv($output, ['Period starts', $report['start']->toDateTimeString()]);
            fputcsv($output, ['Period ends', now()->toDateTimeString()]);
            fputcsv($output, []);
            fputcsv($output, ['Metric', 'Value']);
            fputcsv($output, ['Requests', $report['requestCount']]);
            fputcsv($output, ['HTTP 5xx errors', $report['errorCount']]);
            fputcsv($output, ['Error rate (%)', $report['errorRate']]);
            fputcsv($output, ['Average response (ms)', $report['averageDuration']]);
            fputcsv($output, ['P50 response (ms)', $report['p50Duration']]);
            fputcsv($output, ['P95 response (ms)', $report['p95Duration']]);
            fputcsv($output, ['Slow requests', $report['slowRequestCount']]);
            fputcsv($output, ['Average DB time/request (ms)', $report['averageDatabaseTime']]);
            fputcsv($output, ['Average DB queries/request', $report['averageQueryCount']]);
            fputcsv($output, ['Slow DB queries', $report['slowQueryCount']]);
            fputcsv($output, ['Queued jobs', $report['queuedJobs'] ?? 'Unavailable']);
            fputcsv($output, ['Failed jobs', $report['failedJobs'] ?? 'Unavailable']);
            fputcsv($output, ['Uploaded imaging files', $report['uploadCount']]);
            fputcsv($output, ['Uploaded imaging bytes', $report['uploadBytes']]);
            fputcsv($output, ['Latest host CPU load (% of cores)', $report['latestResource']?->cpu_load_percent ?? 'Unavailable']);
            fputcsv($output, ['Latest host RAM used bytes', $report['latestResource']?->memory_used_bytes ?? 'Unavailable']);
            fputcsv($output, ['Latest host RAM total bytes', $report['latestResource']?->memory_total_bytes ?? 'Unavailable']);
            fputcsv($output, ['Latest network received bytes/sec', $report['latestResource']?->network_in_bytes_per_second ?? 'Unavailable']);
            fputcsv($output, ['Latest network sent bytes/sec', $report['latestResource']?->network_out_bytes_per_second ?? 'Unavailable']);
            fputcsv($output, ['Latest host disk free bytes', $report['latestResource']?->disk_free_bytes ?? 'Unavailable']);
            fputcsv($output, ['Resource warnings', implode(' | ', $report['resourceWarnings'])]);
            fputcsv($output, []);
            fputcsv($output, ['Slowest routes', 'Requests', 'Average response (ms)', 'Maximum response (ms)', '5xx errors', 'Slow DB queries']);

            foreach ($report['routes'] as $route) {
                fputcsv($output, [
                    $route->route_name,
                    $route->request_count,
                    round((float) $route->average_duration_ms, 1),
                    $route->maximum_duration_ms,
                    $route->error_count,
                    $route->slow_query_count,
                ]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function report(string $window): array
    {
        $duration = ['24h' => 24, '7d' => 168, '30d' => 720][$window];
        $start = now()->subHours($duration);
        $metrics = PerformanceMetric::query()->where('created_at', '>=', $start);
        $requestCount = (clone $metrics)->count();
        $errorCount = (clone $metrics)->where('status_code', '>=', 500)->count();
        $aggregates = (clone $metrics)
            ->selectRaw('COALESCE(AVG(duration_ms), 0) as average_duration, COALESCE(AVG(database_time_ms), 0) as average_database_time, COALESCE(AVG(query_count), 0) as average_query_count, COALESCE(SUM(slow_query_count), 0) as slow_query_count, COALESCE(SUM(CASE WHEN duration_ms >= ? THEN 1 ELSE 0 END), 0) as slow_request_count', [config('performance.slow_request_ms', 1000)])
            ->first();

        $routes = (clone $metrics)
            ->selectRaw('route_name, COUNT(*) as request_count, AVG(duration_ms) as average_duration_ms, MAX(duration_ms) as maximum_duration_ms, SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as error_count, SUM(slow_query_count) as slow_query_count')
            ->groupBy('route_name')
            ->orderByDesc('average_duration_ms')
            ->limit(15)
            ->get();

        $recent = (clone $metrics)->latest('created_at')->limit(20)->get();
        $resources = PerformanceResourceSnapshot::query()->where('created_at', '>=', $start)->oldest('created_at')->get();
        $latestResource = $resources->last();
        $charts = [
            'cpu' => $this->chart($resources, 'cpu_load_percent'),
            'memory' => $this->chart($resources, 'memory_percent'),
            'networkIn' => $this->chart($resources, 'network_in_bytes_per_second'),
            'networkOut' => $this->chart($resources, 'network_out_bytes_per_second'),
        ];
        $uploadCount = 0;
        $uploadBytes = 0;
        if (Schema::hasTable('bill_result_images')) {
            $uploads = DB::table('bill_result_images')->where('created_at', '>=', $start)
                ->selectRaw('COUNT(*) as file_count, COALESCE(SUM(file_size), 0) as total_bytes')
                ->first();
            $uploadCount = (int) $uploads->file_count;
            $uploadBytes = (int) $uploads->total_bytes;
        }

        [$queuedJobs, $failedJobs] = $this->queueHealth();
        [$diskFreeBytes, $diskTotalBytes] = $this->diskSpace();

        $databaseConnected = true;
        try {
            DB::connection()->select('select 1');
        } catch (\Throwable) {
            $databaseConnected = false;
        }

        $exportRequests = (clone $metrics)->whereIn('route_name', [
            'admin.reports.pdf', 'admin.reports.csv', 'admin.reports.excel', 'reports.pdf',
        ])->count();

        return [
            'enabled' => (bool) config('performance.enabled'),
            'windowLabel' => ['24h' => 'Last 24 hours', '7d' => 'Last 7 days', '30d' => 'Last 30 days'][$window],
            'start' => $start,
            'requestCount' => $requestCount,
            'errorCount' => $errorCount,
            'errorRate' => $requestCount ? round($errorCount * 100 / $requestCount, 2) : 0,
            'averageDuration' => round((float) $aggregates->average_duration, 1),
            'p50Duration' => $this->percentile($metrics, $requestCount, 0.50),
            'p95Duration' => $this->percentile($metrics, $requestCount, 0.95),
            'slowRequestCount' => (int) $aggregates->slow_request_count,
            'averageDatabaseTime' => round((float) $aggregates->average_database_time, 1),
            'averageQueryCount' => round((float) $aggregates->average_query_count, 1),
            'slowQueryCount' => (int) $aggregates->slow_query_count,
            'routes' => $routes,
            'recent' => $recent,
            'queuedJobs' => $queuedJobs,
            'failedJobs' => $failedJobs,
            'uploadCount' => $uploadCount,
            'uploadBytes' => $uploadBytes,
            'exportRequests' => $exportRequests,
            'databaseConnected' => $databaseConnected,
            'databaseDriver' => config('database.default'),
            'queueDriver' => config('queue.default'),
            'diskFreeBytes' => $diskFreeBytes,
            'diskTotalBytes' => $diskTotalBytes,
            'phpMemoryBytes' => memory_get_usage(true),
            'phpPeakMemoryBytes' => memory_get_peak_usage(true),
            'resources' => $resources,
            'latestResource' => $latestResource,
            'resourceCharts' => $charts,
            'resourceWarnings' => $this->resourceWarnings($latestResource),
            'cpuWarningThreshold' => config('performance.resource_warning.cpu_load_percent', 80),
            'memoryWarningThreshold' => config('performance.resource_warning.memory_percent', 85),
            'diskWarningThreshold' => config('performance.resource_warning.disk_percent', 90),
            'phpVersion' => PHP_VERSION,
            'slowRequestThreshold' => config('performance.slow_request_ms', 1000),
            'slowQueryThreshold' => config('performance.slow_query_ms', 100),
        ];
    }

    private function chart($samples, string $field): array
    {
        $values = $samples->map(function ($sample) use ($field) {
            if ($field === 'memory_percent') {
                return $sample->memory_total_bytes ? $sample->memory_used_bytes * 100 / $sample->memory_total_bytes : null;
            }
            if (in_array($field, ['network_in_bytes_per_second', 'network_out_bytes_per_second'], true)) {
                return $sample->{$field} === null ? null : $sample->{$field} / 1048576;
            }

            return $sample->{$field};
        })->filter(fn ($value) => $value !== null)->values();

        if ($values->isEmpty()) {
            return ['line' => '', 'area' => ''];
        }

        $min = min(0, (float) $values->min());
        $max = max(1, (float) $values->max());
        $span = max(1, $max - $min);
        $points = $values->values()->map(function ($value, $index) use ($values, $min, $span) {
            $x = $values->count() === 1 ? 50 : 4 + ($index * 92 / ($values->count() - 1));
            $y = 56 - (((float) $value - $min) / $span * 48);

            return round($x, 2).','.round($y, 2);
        })->implode(' ');

        return ['line' => $points, 'area' => '4,56 '.$points.' 96,56'];
    }

    private function resourceWarnings(?PerformanceResourceSnapshot $sample): array
    {
        if (! $sample) {
            return ['No resource samples yet. Confirm the Laravel scheduler is running; it records a sample every five minutes.'];
        }

        $warnings = [];
        $cpuThreshold = config('performance.resource_warning.cpu_load_percent', 80);
        $memoryThreshold = config('performance.resource_warning.memory_percent', 85);
        $diskThreshold = config('performance.resource_warning.disk_percent', 90);

        if ($sample->cpu_load_percent !== null && $sample->cpu_load_percent >= $cpuThreshold) {
            $warnings[] = 'CPU load is high relative to the detected core count; check PHP worker usage and slow routes.';
        }
        if ($sample->memory_total_bytes && $sample->memory_used_bytes / $sample->memory_total_bytes * 100 >= $memoryThreshold) {
            $warnings[] = 'RAM use is high; inspect PHP worker counts, queue workers, and available server memory.';
        }
        if ($sample->disk_total_bytes && $sample->disk_free_bytes !== null && (1 - $sample->disk_free_bytes / $sample->disk_total_bytes) * 100 >= $diskThreshold) {
            $warnings[] = 'Disk use is high; review imaging retention and storage capacity.';
        }
        if ($sample->network_in_bytes_per_second === null) {
            $warnings[] = 'Network throughput needs at least two samples and Linux interface counters to be available.';
        }
        if ($sample->created_at->lt(now()->subMinutes(15))) {
            $warnings[] = 'Resource samples are stale; check the system scheduler.';
        }

        return $warnings;
    }

    private function percentile($query, int $count, float $percentile): int
    {
        if ($count === 0) {
            return 0;
        }

        $offset = max(0, (int) ceil($count * $percentile) - 1);

        return (int) ((clone $query)->reorder()->orderBy('duration_ms')->offset($offset)->value('duration_ms') ?? 0);
    }

    private function queueHealth(): array
    {
        $connection = config('queue.default');
        if ($connection !== 'database') {
            return [null, null];
        }

        $queueConfig = config('queue.connections.database');
        $failedConfig = config('queue.failed');
        $jobsTable = $queueConfig['table'] ?? 'jobs';
        $failedTable = $failedConfig['table'] ?? 'failed_jobs';

        return [
            Schema::hasTable($jobsTable) ? DB::table($jobsTable)->count() : null,
            Schema::hasTable($failedTable) ? DB::table($failedTable)->count() : null,
        ];
    }

    private function diskSpace(): array
    {
        $path = storage_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        return [
            $free === false ? null : (int) $free,
            $total === false ? null : (int) $total,
        ];
    }
}
