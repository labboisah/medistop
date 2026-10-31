<?php

return [
    'enabled' => (bool) env('PERFORMANCE_MONITORING_ENABLED', false),
    'slow_request_ms' => (int) env('PERFORMANCE_SLOW_REQUEST_MS', 1000),
    'slow_query_ms' => (int) env('PERFORMANCE_SLOW_QUERY_MS', 100),
    'retention_days' => (int) env('PERFORMANCE_METRICS_RETENTION_DAYS', 90),
    'resource_warning' => [
        'cpu_load_percent' => (int) env('PERFORMANCE_CPU_LOAD_WARNING_PERCENT', 80),
        'memory_percent' => (int) env('PERFORMANCE_MEMORY_WARNING_PERCENT', 85),
        'disk_percent' => (int) env('PERFORMANCE_DISK_WARNING_PERCENT', 90),
    ],
];
