<?php

namespace App\Services;

use App\Models\PerformanceResourceSnapshot;
use Illuminate\Support\Facades\Cache;

class PerformanceResourceSampler
{
    public function sample(): PerformanceResourceSnapshot
    {
        $now = now();
        $previous = PerformanceResourceSnapshot::query()->latest('created_at')->first();
        $memory = $this->memory();
        $network = $this->networkCounters();
        $previousNetwork = Cache::get('performance.network_counters');
        $elapsed = $previous ? max(1, $now->diffInSeconds($previous->created_at)) : null;

        $inRate = $outRate = null;
        if ($elapsed && $network && is_array($previousNetwork)) {
            $inRate = max(0, $network['in'] - $previousNetwork['in']) / $elapsed;
            $outRate = max(0, $network['out'] - $previousNetwork['out']) / $elapsed;
        }
        if ($network) {
            Cache::put('performance.network_counters', $network, now()->addDays(2));
        }

        [$diskFree, $diskTotal] = $this->diskSpace();
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;
        $cores = $this->cpuCount();
        $cpuLoad = $load && $cores ? min(999.99, ($load[0] / $cores) * 100) : null;

        return PerformanceResourceSnapshot::create([
            'cpu_load_percent' => $cpuLoad,
            'memory_used_bytes' => $memory['used'],
            'memory_total_bytes' => $memory['total'],
            'network_in_bytes_per_second' => $inRate,
            'network_out_bytes_per_second' => $outRate,
            'disk_free_bytes' => $diskFree,
            'disk_total_bytes' => $diskTotal,
            'created_at' => $now,
        ]);
    }

    private function memory(): array
    {
        if (! is_readable('/proc/meminfo')) {
            return ['used' => null, 'total' => null];
        }

        $contents = file_get_contents('/proc/meminfo');
        preg_match('/^MemTotal:\s+(\d+)\s+kB/m', $contents, $totalMatch);
        preg_match('/^MemAvailable:\s+(\d+)\s+kB/m', $contents, $availableMatch);
        if (! isset($totalMatch[1], $availableMatch[1])) {
            return ['used' => null, 'total' => null];
        }

        $total = (int) $totalMatch[1] * 1024;
        $available = (int) $availableMatch[1] * 1024;

        return ['used' => max(0, $total - $available), 'total' => $total];
    }

    private function networkCounters(): ?array
    {
        if (! is_readable('/proc/net/dev')) {
            return null;
        }

        $in = $out = 0;
        foreach (array_slice(file('/proc/net/dev', FILE_IGNORE_NEW_LINES) ?: [], 2) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$interface, $stats] = explode(':', $line, 2);
            if (trim($interface) === 'lo') {
                continue;
            }
            $values = preg_split('/\s+/', trim($stats));
            if (count($values) >= 9) {
                $in += (int) $values[0];
                $out += (int) $values[8];
            }
        }

        return ['in' => $in, 'out' => $out];
    }

    private function cpuCount(): ?int
    {
        if (! is_readable('/proc/cpuinfo')) {
            return null;
        }

        $count = preg_match_all('/^processor\s*:/m', file_get_contents('/proc/cpuinfo'));

        return $count > 0 ? $count : null;
    }

    private function diskSpace(): array
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        return [$free === false ? null : (int) $free, $total === false ? null : (int) $total];
    }
}
