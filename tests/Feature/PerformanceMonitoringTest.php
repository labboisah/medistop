<?php

namespace Tests\Feature;

use App\Models\PerformanceMetric;
use App\Models\PerformanceResourceSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PerformanceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_request_metrics_are_collected_only_when_monitoring_is_enabled(): void
    {
        config(['performance.enabled' => false]);
        $this->get(route('login'))->assertOk();
        $this->assertDatabaseCount('performance_metrics', 0);

        config(['performance.enabled' => true]);
        $this->get(route('login'))->assertOk();

        $this->assertDatabaseHas('performance_metrics', [
            'route_name' => 'login',
            'method' => 'GET',
            'status_code' => 200,
        ]);
    }

    public function test_only_administrators_can_view_and_export_the_monitoring_report(): void
    {
        PerformanceMetric::create([
            'route_name' => 'bills.index',
            'method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 1500,
            'query_count' => 8,
            'database_time_ms' => 85,
            'slow_query_count' => 1,
            'created_at' => now(),
        ]);

        $administrator = User::factory()->create(['role' => 'admin']);
        $this->actingAs($administrator)
            ->get(route('admin.performance.index', ['window' => '24h']))
            ->assertOk()
            ->assertSee('Production Performance')
            ->assertSee('bills.index')
            ->assertSee('Slow requests');

        $this->actingAs($administrator)
            ->get(route('admin.performance.export', ['window' => '24h']))
            ->assertOk()
            ->assertDownload();

        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)
            ->get(route('admin.performance.index'))
            ->assertForbidden();
    }

    public function test_retention_command_removes_only_expired_measurements(): void
    {
        config(['performance.retention_days' => 90]);

        PerformanceMetric::create([
            'route_name' => 'old.route', 'method' => 'GET', 'status_code' => 200,
            'duration_ms' => 500, 'query_count' => 2, 'database_time_ms' => 20,
            'slow_query_count' => 0, 'created_at' => now()->subDays(91),
        ]);
        PerformanceMetric::create([
            'route_name' => 'recent.route', 'method' => 'GET', 'status_code' => 200,
            'duration_ms' => 500, 'query_count' => 2, 'database_time_ms' => 20,
            'slow_query_count' => 0, 'created_at' => now()->subDays(89),
        ]);

        Artisan::call('performance:prune');

        $this->assertDatabaseMissing('performance_metrics', ['route_name' => 'old.route']);
        $this->assertDatabaseHas('performance_metrics', ['route_name' => 'recent.route']);
    }

    public function test_resource_samples_are_shown_as_trends_with_actionable_warnings(): void
    {
        config(['performance.resource_warning.cpu_load_percent' => 80]);
        PerformanceResourceSnapshot::create([
            'cpu_load_percent' => 92,
            'memory_used_bytes' => 90_000_000,
            'memory_total_bytes' => 100_000_000,
            'network_in_bytes_per_second' => 1024,
            'network_out_bytes_per_second' => 512,
            'disk_free_bytes' => 100_000_000,
            'disk_total_bytes' => 1_000_000_000,
            'created_at' => now()->subMinutes(5),
        ]);

        $administrator = User::factory()->create(['role' => 'admin']);
        $this->actingAs($administrator)
            ->get(route('admin.performance.index', ['window' => '24h']))
            ->assertOk()
            ->assertSee('System resources')
            ->assertSee('CPU load is high relative to the detected core count')
            ->assertSee('RAM used (%)')
            ->assertSee('Network received (MB/s)');
    }
}
