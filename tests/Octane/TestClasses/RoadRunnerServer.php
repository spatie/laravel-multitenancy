<?php

namespace Spatie\Multitenancy\Tests\Octane\TestClasses;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class RoadRunnerServer
{
    protected string $packagePath;

    protected int $port;

    protected Process $process;

    public function __construct()
    {
        $this->packagePath = dirname(__DIR__, 3);
        $this->port = $this->freePort();
    }

    public static function binaryPath(): string
    {
        return dirname(__DIR__, 3) . '/rr';
    }

    /** @param array<string, string> $env */
    public function start(array $env): static
    {
        $this->process = new Process([
            static::binaryPath(),
            'serve',
            '-c', "{$this->packagePath}/tests/Octane/RoadRunner/.rr.yaml",
            '-o', "http.address=127.0.0.1:{$this->port}",
            '-o', 'server.command=' . implode(',', [
                (new PhpExecutableFinder())->find(),
                '-d', 'variables_order=EGPCS',
                "{$this->packagePath}/vendor/laravel/octane/bin/roadrunner-worker",
            ]),
        ], $this->packagePath, [
            ...$env,
            'APP_BASE_PATH' => "{$this->packagePath}/tests/Octane/RoadRunner",
            'COMPOSER_VENDOR_DIR' => "{$this->packagePath}/vendor",
            'LARAVEL_OCTANE' => '1',
        ]);

        $this->process->start();

        $this->waitUntilListening();

        return $this;
    }

    public function get(string $host, string $uri): Response
    {
        return Http::withHeaders(['Host' => $host])->get("http://127.0.0.1:{$this->port}/{$uri}");
    }

    public function stop(): void
    {
        $this->process->stop();
    }

    public function output(): string
    {
        return $this->process->getOutput() . $this->process->getErrorOutput();
    }

    protected function waitUntilListening(): void
    {
        $deadline = microtime(true) + 30;

        while (microtime(true) < $deadline) {
            if (! $this->process->isRunning()) {
                throw new RuntimeException("RoadRunner stopped: {$this->output()}");
            }

            if ($this->isListening()) {
                return;
            }

            usleep(100_000);
        }

        throw new RuntimeException("RoadRunner did not start listening: {$this->output()}");
    }

    protected function isListening(): bool
    {
        $socket = @fsockopen('127.0.0.1', $this->port, timeout: 1);

        if (! $socket) {
            return false;
        }

        fclose($socket);

        return true;
    }

    protected function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');

        $port = (int) str_replace('127.0.0.1:', '', stream_socket_get_name($socket, false));

        fclose($socket);

        return $port;
    }
}
