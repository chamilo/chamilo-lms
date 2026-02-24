<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use DateTime;
use SplFileObject;
use Symfony\Component\HttpKernel\KernelInterface;

use const FILE_APPEND;
use const LOCK_EX;
use const PHP_INT_MAX;

/**
 * Writes and parses structured IDS event log entries to/from var/logs/ids/ids_events.log.
 *
 * Log line format:
 *   [2024-03-01 09:44:57] [XSS] [IP 192.168.1.1] [URI /page?q=test] [PARAM q] Detected XSS: '<script'
 */
class IntrusionDetectionLogHelper
{
    private string $logDir;
    private string $logFile;
    private int $maxLogs = 90;

    public function __construct(KernelInterface $kernel)
    {
        $this->logDir = $kernel->getLogDir().'/ids';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0o755, true);
        }
        $this->logFile = $this->logDir.'/ids_events.log';
    }

    /**
     * Append a structured IDS event to the log file.
     */
    public function logEvent(string $type, string $ip, string $uri, string $param, string $detail): void
    {
        $this->maybeRotate();

        // Sanitize all fields to prevent log injection
        $cleanIp = preg_replace('/[^\d.:a-fA-F]/', '', $ip) ?: 'unknown';
        $cleanType = preg_replace('/[^A-Za-z_]/', '', $type);
        $cleanUri = substr(preg_replace('/[\r\n\t]/', ' ', $uri) ?: '', 0, 300);
        $cleanParam = substr(preg_replace('/[\r\n\t\[\]]/', '', $param) ?: '', 0, 60);
        $cleanDetail = substr(preg_replace('/[\r\n]/', ' ', $detail) ?: '', 0, 300);

        $line = \sprintf(
            "[%s] [%s] [IP %s] [URI %s] [PARAM %s] %s\n",
            (new DateTime())->format('Y-m-d H:i:s'),
            strtoupper($cleanType),
            $cleanIp,
            $cleanUri,
            $cleanParam,
            $cleanDetail
        );

        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Read and parse IDS events, newest first, with optional filtering and pagination.
     *
     * @param array{ip?: string, type?: string, from?: string|null, to?: string|null} $filters
     *
     * @return array{items: array<int, array{date: string, type: string, ip: string, uri: string, param: string, detail: string}>, total: int, page: int, pageSize: int}
     */
    public function parseEvents(int $page = 1, int $pageSize = 25, array $filters = []): array
    {
        $allLines = $this->readAllLogLines();
        $parsed = $this->parseLines($allLines);

        // Newest first
        $parsed = array_reverse($parsed);

        // Apply filters
        if (!empty($filters['ip'])) {
            $ip = $filters['ip'];
            $parsed = array_values(array_filter($parsed, static fn ($r) => str_contains($r['ip'], $ip)));
        }
        if (!empty($filters['type'])) {
            $type = strtoupper($filters['type']);
            $parsed = array_values(array_filter($parsed, static fn ($r) => $r['type'] === $type));
        }
        if (!empty($filters['from'])) {
            $from = $filters['from'];
            $parsed = array_values(array_filter($parsed, static fn ($r) => substr($r['date'], 0, 10) >= $from));
        }
        if (!empty($filters['to'])) {
            $to = $filters['to'];
            $parsed = array_values(array_filter($parsed, static fn ($r) => substr($r['date'], 0, 10) <= $to));
        }

        $total = \count($parsed);
        $offset = ($page - 1) * $pageSize;

        return [
            'items' => \array_slice($parsed, $offset, $pageSize),
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * Events count grouped by day for the last $days days.
     *
     * @return array<string, int> e.g. ['2024-03-01' => 5, '2024-03-02' => 3]
     */
    public function getStatsByDay(int $days = 7): array
    {
        $cutoff = (new DateTime())->modify("-{$days} days")->format('Y-m-d');
        $byDay = [];

        foreach ($this->readAllLogLines() as $line) {
            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2})/', $line, $m)) {
                continue;
            }
            if ($m[1] < $cutoff) {
                continue;
            }
            $byDay[$m[1]] = ($byDay[$m[1]] ?? 0) + 1;
        }

        ksort($byDay);

        return $byDay;
    }

    /**
     * Events count grouped by attack type for the last $days days.
     *
     * @return array<string, int> e.g. ['SQLi' => 10, 'XSS' => 5]
     */
    public function getStatsByType(int $days = 30): array
    {
        $cutoff = (new DateTime())->modify("-{$days} days")->format('Y-m-d');
        $byType = [];

        foreach ($this->readAllLogLines() as $line) {
            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2}).*?\] \[([A-Z_]+)\]/', $line, $m)) {
                continue;
            }
            if ($m[1] < $cutoff) {
                continue;
            }
            $byType[$m[2]] = ($byType[$m[2]] ?? 0) + 1;
        }

        arsort($byType);

        return $byType;
    }

    /**
     * Top attacking IPs by event count for the last $days days.
     *
     * @return array<int, array{ip: string, count: int}>
     */
    public function getTopIps(int $days = 30, int $limit = 5): array
    {
        $cutoff = (new DateTime())->modify("-{$days} days")->format('Y-m-d');
        $ips = [];

        foreach ($this->readAllLogLines() as $line) {
            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2}).*?\] \[[A-Z_]+\] \[IP ([^\]]+)\]/', $line, $m)) {
                continue;
            }
            if ($m[1] < $cutoff) {
                continue;
            }
            $ips[$m[2]] = ($ips[$m[2]] ?? 0) + 1;
        }

        arsort($ips);

        $result = [];
        $i = 0;
        foreach ($ips as $ip => $count) {
            if ($i >= $limit) {
                break;
            }
            $result[] = ['ip' => (string) $ip, 'count' => $count];
            ++$i;
        }

        return $result;
    }

    /**
     * Returns all known attack types found in the logs (for filter dropdown).
     *
     * @return string[]
     */
    public function getKnownTypes(): array
    {
        $types = [];
        foreach ($this->readAllLogLines() as $line) {
            if (preg_match('/^\[.*?\] \[([A-Z_]+)\]/', $line, $m)) {
                $types[$m[1]] = true;
            }
        }

        return array_keys($types);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @return string[]
     */
    private function readAllLogLines(): array
    {
        $lines = [];

        if (file_exists($this->logFile)) {
            $content = @file_get_contents($this->logFile);
            if (false !== $content) {
                $lines = array_merge($lines, explode("\n", $content));
            }
        }

        // Read rotated files (.1 = yesterday, .2 = day before, etc.)
        for ($i = 1; $i <= $this->maxLogs; ++$i) {
            $rotated = $this->logFile.'.'.$i;
            if (!file_exists($rotated)) {
                continue; // Gaps are possible (no activity on some days)
            }
            $content = @file_get_contents($rotated);
            if (false !== $content) {
                $lines = array_merge($lines, explode("\n", $content));
            }
        }

        return $lines;
    }

    /**
     * @param string[] $lines
     *
     * @return array<int, array{date: string, type: string, ip: string, uri: string, param: string, detail: string}>
     */
    private function parseLines(array $lines): array
    {
        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[([A-Z_]+)\] \[IP ([^\]]+)\] \[URI ([^\]]*)\] \[PARAM ([^\]]*)\] (.+)$/';
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }
            if (!preg_match($pattern, $line, $m)) {
                continue;
            }
            $parsed[] = [
                'date' => $m[1],
                'type' => $m[2],
                'ip' => $m[3],
                'uri' => $m[4],
                'param' => $m[5],
                'detail' => $m[6],
            ];
        }

        return $parsed;
    }

    private function maybeRotate(): void
    {
        if (!file_exists($this->logFile)) {
            return;
        }

        $lastLine = $this->readLastLine($this->logFile);
        if ('' === $lastLine) {
            return;
        }

        if (!preg_match('/^\[(\d{4}-\d{2}-\d{2})/', $lastLine, $m)) {
            return;
        }

        $today = (new DateTime())->format('Y-m-d');
        if ($m[1] !== $today) {
            $this->rotateLogFiles();
        }
    }

    private function readLastLine(string $path): string
    {
        $fo = new SplFileObject($path, 'r');
        $fo->seek(PHP_INT_MAX);
        $last = $fo->key();
        if (0 === $last) {
            return '';
        }
        $fo->seek($last - 1);

        return trim((string) $fo->current());
    }

    private function rotateLogFiles(): void
    {
        for ($i = $this->maxLogs; $i > 0; --$i) {
            $old = $this->logFile.'.'.$i;
            $new = $this->logFile.'.'.($i + 1);
            if (file_exists($old)) {
                if (file_exists($new)) {
                    unlink($new);
                }
                rename($old, $new);
            }
        }
        rename($this->logFile, $this->logFile.'.1');
    }
}
