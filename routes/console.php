<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use App\Models\PerformanceMetric;
use App\Models\PerformanceResourceSnapshot;
use App\Services\PerformanceResourceSampler;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('performance:prune', function () {
    if (! Schema::hasTable('performance_metrics')) {
        $this->info('Performance metrics table is not installed.');

        return;
    }

    $deleted = PerformanceMetric::where('created_at', '<', now()->subDays(config('performance.retention_days', 90)))
        ->delete();

    $this->info("Removed {$deleted} expired performance measurements.");
})->purpose('Remove performance measurements older than the configured retention period');

Schedule::command('performance:prune')->daily();

Artisan::command('performance:sample-resources', function (PerformanceResourceSampler $sampler) {
    if (! Schema::hasTable('performance_resource_snapshots')) {
        $this->info('Performance resource snapshots table is not installed.');

        return;
    }

    $sampler->sample();
    $this->info('System resource sample recorded.');
})->purpose('Record host CPU load, memory, network throughput, and disk capacity');

Artisan::command('performance:prune-resources', function () {
    if (! Schema::hasTable('performance_resource_snapshots')) {
        return;
    }

    PerformanceResourceSnapshot::where('created_at', '<', now()->subDays(config('performance.retention_days', 90)))
        ->delete();
})->purpose('Remove expired system resource samples');

Schedule::command('performance:sample-resources')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('performance:prune-resources')->daily();
