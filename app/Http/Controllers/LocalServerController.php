<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class LocalServerController extends Controller
{
    private const HOST = '0.0.0.0';
    private const PORT = 8080;

    public static function shouldShowControl(Request $request): bool
    {
        $clientIp = $request->ip();
        $allowedIps = array_merge(['127.0.0.1', '::1'], self::serverIps());

        if (in_array($clientIp, $allowedIps, true)) {
            return true;
        }

        return in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    }

    public function status(): JsonResponse
    {
        if (! self::shouldShowControl(request())) {
            return Response::json(['message' => 'Server control is only available on the server computer.'], 403);
        }

        return Response::json($this->statusPayload());
    }

    public function connect(): JsonResponse
    {
        if (! self::shouldShowControl(request())) {
            return Response::json(['message' => 'Server control is only available on the server computer.'], 403);
        }

        if ($this->isServerReachable()) {
            return Response::json($this->statusPayload('Server is already connected.'));
        }

        if (! function_exists('exec')) {
            return Response::json([
                'connected' => false,
                'message' => 'PHP exec() is disabled, so the server cannot be started from the web page.',
            ], 500);
        }

        $pid = $this->startProcess();

        if (! $pid) {
            return Response::json([
                'connected' => false,
                'message' => 'Unable to start the network server.',
            ], 500);
        }

        $this->writeState($pid);
        usleep(800000);

        if (! $this->isServerReachable()) {
            return Response::json([
                'connected' => false,
                'pid' => $pid,
                'message' => 'Server process was started, but port '.self::PORT.' is not reachable yet. Check storage/logs/server-control.log.',
            ], 500);
        }

        return Response::json($this->statusPayload('Server connected successfully.'));
    }

    public function disconnect(): JsonResponse
    {
        if (! self::shouldShowControl(request())) {
            return Response::json(['message' => 'Server control is only available on the server computer.'], 403);
        }

        $pid = $this->findArtisanServerPid() ?: $this->storedPhpPid();

        if (! $pid) {
            $this->clearState();

            return Response::json($this->statusPayload('No server started by this button was found.'));
        }

        $this->stopProcess($pid);
        $this->clearState();

        return Response::json([
            'connected' => false,
            'message' => 'Server disconnected successfully.',
        ]);
    }

    private function statusPayload(?string $message = null): array
    {
        $pid = $this->findArtisanServerPid() ?: $this->storedPhpPid();
        $connected = $this->isServerReachable() || ($pid && $this->isProcessRunning($pid));

        return [
            'connected' => (bool) $connected,
            'pid' => $pid,
            'url' => $this->advertisedUrl(),
            'message' => $message ?: ($connected ? 'Server is connected.' : 'Server is disconnected.'),
        ];
    }

    private function startProcess(): ?int
    {
        $php = $this->phpCliBinary();
        $artisan = base_path('artisan');
        $basePath = base_path();
        $output = [];
        $logFile = storage_path('logs/server-control.log');
        $errorLogFile = storage_path('logs/server-control-error.log');

        if (PHP_OS_FAMILY === 'Windows') {
            $command = '$p = Start-Process -FilePath '.$this->powerShellQuote($php)
                .' -ArgumentList @('.$this->powerShellQuote($artisan).', '.$this->powerShellQuote('serve').', '
                .$this->powerShellQuote('--host='.self::HOST).', '.$this->powerShellQuote('--port='.self::PORT).')'
                .' -WorkingDirectory '.$this->powerShellQuote($basePath)
                .' -RedirectStandardOutput '.$this->powerShellQuote($logFile)
                .' -RedirectStandardError '.$this->powerShellQuote($errorLogFile)
                .' -WindowStyle Hidden -PassThru; $p.Id';

            exec('powershell -NoProfile -ExecutionPolicy Bypass -Command '.escapeshellarg($command), $output);
        } else {
            $command = 'cd '.escapeshellarg($basePath).' && nohup '.escapeshellarg($php).' '.escapeshellarg($artisan)
                .' serve --host='.escapeshellarg(self::HOST).' --port='.escapeshellarg((string) self::PORT)
                .' > /dev/null 2>&1 & echo $!';

            exec($command, $output);
        }

        $startedPid = isset($output[0]) ? (int) trim($output[0]) : 0;
        usleep(500000);
        $pid = $this->findArtisanServerPid() ?: $startedPid;

        if ($pid <= 0) {
            Log::warning('Server control failed to start artisan serve.', [
                'php' => $php,
                'output' => $output,
            ]);
        }

        return $pid > 0 ? $pid : null;
    }

    private function phpCliBinary(): string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return PHP_BINARY;
        }

        $candidates = array_filter([
            env('SERVER_CONTROL_PHP'),
            PHP_BINDIR.DIRECTORY_SEPARATOR.'php.exe',
            'C:\\laragon\\bin\\php\\php-8.3.16-Win32-vs16-x64\\php.exe',
        ]);

        foreach ($candidates as $phpExe) {
            if (File::exists($phpExe)) {
                return $phpExe;
            }
        }

        $laragonPhpBins = File::glob('C:\\laragon\\bin\\php\\*\\php.exe');

        if (! empty($laragonPhpBins)) {
            return $laragonPhpBins[0];
        }

        return PHP_BINARY;
    }

    private function stopProcess(int $pid): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec('taskkill /PID '.((int) $pid).' /T /F');

            return;
        }

        exec('kill '.((int) $pid));
    }

    private function isProcessRunning(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            exec('tasklist /FI '.escapeshellarg('PID eq '.$pid).' /FO CSV /NH', $output);

            return isset($output[0]) && str_contains($output[0], (string) $pid);
        }

        exec('ps -p '.((int) $pid).' -o pid=', $output);

        return ! empty($output);
    }

    private function findArtisanServerPid(): ?int
    {
        if (! function_exists('exec')) {
            return null;
        }

        $output = [];

        if (PHP_OS_FAMILY === 'Windows') {
            exec('netstat -ano -p TCP', $output);

            foreach ($output as $line) {
                if (! preg_match('/^\s*TCP\s+\S+:'.self::PORT.'\s+\S+\s+LISTENING\s+(\d+)\s*$/i', $line, $matches)) {
                    continue;
                }

                $pid = (int) $matches[1];

                if ($this->isPhpProcess($pid)) {
                    return $pid;
                }
            }
        } else {
            exec('pgrep -f '.escapeshellarg('artisan serve --host='.self::HOST.' --port='.self::PORT), $output);
        }

        $pid = isset($output[0]) ? (int) trim($output[0]) : 0;

        return $pid > 0 ? $pid : null;
    }

    private function storedPhpPid(): ?int
    {
        $pid = $this->storedPid();

        if (! $pid || ! $this->isPhpProcess($pid)) {
            if ($pid) {
                $this->clearState();
            }

            return null;
        }

        return $pid;
    }

    private function isPhpProcess(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            return $this->isProcessRunning($pid);
        }

        $output = [];
        exec('tasklist /FI '.escapeshellarg('PID eq '.$pid).' /FO CSV /NH', $output);

        return isset($output[0]) && str_contains(strtolower($output[0]), 'php.exe');
    }

    private function isServerReachable(): bool
    {
        $connection = @fsockopen('127.0.0.1', self::PORT, $errorCode, $errorMessage, 0.25);

        if (! $connection) {
            return false;
        }

        fclose($connection);

        return true;
    }

    private function storedPid(): ?int
    {
        $path = $this->statePath();

        if (! File::exists($path)) {
            return null;
        }

        $state = json_decode((string) File::get($path), true);
        $pid = (int) ($state['pid'] ?? 0);

        return $pid > 0 ? $pid : null;
    }

    private function writeState(int $pid): void
    {
        File::put($this->statePath(), json_encode([
            'pid' => $pid,
            'host' => self::HOST,
            'port' => self::PORT,
            'started_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    }

    private function clearState(): void
    {
        File::delete($this->statePath());
    }

    private function statePath(): string
    {
        return storage_path('app/server-control.json');
    }

    private function advertisedUrl(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $host || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            $host = self::serverIps()[0] ?? request()->getHost();
        }

        return 'http://'.$host.':'.self::PORT;
    }

    private function powerShellQuote(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    private static function serverIps(): array
    {
        $ips = [];
        $hostname = gethostname();

        if ($hostname) {
            $resolvedIps = gethostbynamel($hostname);
            $ips = is_array($resolvedIps) ? $resolvedIps : [];
        }

        return array_values(array_unique(array_filter($ips)));
    }
}
