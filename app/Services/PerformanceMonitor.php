<?php

namespace App\Services;

use App\Models\PerformanceMetric;
use Illuminate\Http\Request;
use Throwable;

class PerformanceMonitor
{
    private bool $active = false;

    private int $startedAt = 0;

    private int $queryCount = 0;

    private float $databaseTimeMs = 0;

    private int $slowQueryCount = 0;

    public function begin(): void
    {
        $this->startedAt = hrtime(true);
        $this->queryCount = 0;
        $this->databaseTimeMs = 0;
        $this->slowQueryCount = 0;
        $this->active = true;
    }

    public function recordQuery(float $durationMs): void
    {
        if (! $this->active) {
            return;
        }

        $this->queryCount++;
        $this->databaseTimeMs += $durationMs;

        if ($durationMs >= config('performance.slow_query_ms', 100)) {
            $this->slowQueryCount++;
        }
    }

    public function finish(Request $request, int $statusCode): void
    {
        if (! $this->active) {
            return;
        }

        $durationMs = (int) round((hrtime(true) - $this->startedAt) / 1_000_000);
        $this->active = false;

        try {
            $routeName = $request->route()?->getName()
                ?? $request->route()?->uri()
                ?? 'unmatched';

            PerformanceMetric::create([
                'route_name' => substr((string) $routeName, 0, 160),
                'method' => substr($request->method(), 0, 10),
                'status_code' => $statusCode,
                'duration_ms' => max(0, $durationMs),
                'query_count' => min($this->queryCount, 65535),
                'database_time_ms' => max(0, (int) round($this->databaseTimeMs)),
                'slow_query_count' => min($this->slowQueryCount, 65535),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Monitoring storage must never break a user request.
        }
    }
}
