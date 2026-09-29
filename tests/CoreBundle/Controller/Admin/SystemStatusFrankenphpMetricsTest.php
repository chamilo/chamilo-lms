<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\Admin\SystemStatusController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit test for SystemStatusController's FrankenPHP/Caddy Prometheus metrics parsing
 * (parsePrometheusTextMetrics() / parseFrankenphpMetrics()), added so the admin
 * system-status webserver panel can show load metrics under FrankenPHP the same way
 * it already does for Apache mod_status / Nginx stub_status.
 *
 * Deliberately avoids the DB/container: the controller is instantiated via
 * ReflectionClass::newInstanceWithoutConstructor() since these methods only parse
 * their string argument, never touch $this.
 */
class SystemStatusFrankenphpMetricsTest extends TestCase
{
    private SystemStatusController $controller;

    protected function setUp(): void
    {
        $reflection = new ReflectionClass(SystemStatusController::class);
        $this->controller = $reflection->newInstanceWithoutConstructor();
    }

    private function callParseFrankenphpMetrics(string $body): ?array
    {
        $method = new ReflectionMethod(SystemStatusController::class, 'parseFrankenphpMetrics');
        $method->setAccessible(true);

        return $method->invoke($this->controller, $body);
    }

    public function testParsesTotalsAndPerWorkerBreakdown(): void
    {
        $body = <<<'METRICS'
            # HELP frankenphp_total_threads The total number of PHP threads
            # TYPE frankenphp_total_threads gauge
            frankenphp_total_threads 10
            # HELP frankenphp_busy_threads The number of PHP threads currently processing a request
            # TYPE frankenphp_busy_threads gauge
            frankenphp_busy_threads 4
            # HELP frankenphp_queue_depth The number of regular queued requests
            # TYPE frankenphp_queue_depth gauge
            frankenphp_queue_depth 0
            # HELP frankenphp_total_workers Total worker count
            # TYPE frankenphp_total_workers gauge
            frankenphp_total_workers{worker="/app/public/worker.php"} 4
            frankenphp_busy_workers{worker="/app/public/worker.php"} 1
            frankenphp_worker_request_time_sum{worker="/app/public/worker.php"} 12.5
            frankenphp_worker_request_time_count{worker="/app/public/worker.php"} 100
            METRICS;

        $result = $this->callParseFrankenphpMetrics($body);

        $this->assertNotNull($result);
        $this->assertSame(10, $result['totalThreads']);
        $this->assertSame(4, $result['busyThreads']);
        $this->assertSame(0, $result['queueDepth']);
        $this->assertSame(40.0, $result['busyThreadsPercent']);

        $this->assertCount(1, $result['workers']);
        $worker = $result['workers'][0];
        $this->assertSame('/app/public/worker.php', $worker['name']);
        $this->assertSame(4, $worker['totalWorkers']);
        $this->assertSame(1, $worker['busyWorkers']);
        // _sum (12.5) / _count (100) = a real average, not the two figures added together.
        $this->assertSame(0.125, $worker['avgRequestTimeSeconds']);
    }

    public function testHandlesPlainGaugeShapeForRequestTime(): void
    {
        $body = <<<'METRICS'
            frankenphp_total_threads 4
            frankenphp_busy_threads 1
            frankenphp_total_workers{worker="worker.php"} 2
            frankenphp_busy_workers{worker="worker.php"} 0
            frankenphp_worker_request_time{worker="worker.php"} 0.042
            METRICS;

        $result = $this->callParseFrankenphpMetrics($body);

        $this->assertNotNull($result);
        $this->assertSame(0.042, $result['workers'][0]['avgRequestTimeSeconds']);
    }

    public function testReturnsNullForUnrelatedBody(): void
    {
        $apacheBody = "ServerVersion: Apache/2.4.61\nBusyWorkers: 2\nIdleWorkers: 3\n";

        $this->assertNull($this->callParseFrankenphpMetrics($apacheBody));
        $this->assertNull($this->callParseFrankenphpMetrics('<html><body>404 Not Found</body></html>'));
        $this->assertNull($this->callParseFrankenphpMetrics(''));
    }

    public function testHandlesMinimalBodyWithoutWorkers(): void
    {
        $body = "frankenphp_total_threads 2\nfrankenphp_busy_threads 0\n";

        $result = $this->callParseFrankenphpMetrics($body);

        $this->assertNotNull($result);
        $this->assertSame(2, $result['totalThreads']);
        $this->assertSame(0, $result['busyThreads']);
        $this->assertNull($result['queueDepth']);
        $this->assertSame(0.0, $result['busyThreadsPercent']);
        $this->assertSame([], $result['workers']);
    }
}
