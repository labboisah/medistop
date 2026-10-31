<?php

namespace App\Http\Middleware;

use App\Services\PerformanceMonitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CollectPerformanceMetrics
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('performance.enabled') || in_array($request->method(), ['OPTIONS', 'HEAD'], true)) {
            return $next($request);
        }

        $monitor = app(PerformanceMonitor::class);
        $monitor->begin();

        try {
            $response = $next($request);
            $monitor->finish($request, $response->getStatusCode());

            return $response;
        } catch (Throwable $exception) {
            $monitor->finish($request, 500);

            throw $exception;
        }
    }
}
