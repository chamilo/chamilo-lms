<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations;

use Doctrine\DBAL\Connection;
use RuntimeException;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Private, streaming snapshots of recoverable data issues, scoped to a database.
 * Write the complete snapshot before modifying the source rows. A failed phase
 * can replace its snapshot on retry; committed phases retain their report.
 */
final class MigrationIssueReporter
{
    /**
     * @var array{count: int, path: string, report_id: string}|null
     */
    private ?array $lastSummary = null;
    private bool $summaryFailed = false;

    public function __construct(
        private readonly string $projectDir
    ) {}

    public function resetSummary(): void
    {
        $this->lastSummary = null;
        $this->summaryFailed = false;
    }

    public function markSummaryFailed(): void
    {
        $this->summaryFailed = true;
    }

    public function hasSummaryFailed(): bool
    {
        return $this->summaryFailed;
    }

    /**
     * @return array{count: int, path: string, report_id: string}|null
     */
    public function getLastSummary(): ?array
    {
        return $this->lastSummary;
    }

    /**
     * @param iterable<array<string, mixed>> $issues only identifiers, reasons and actions; no answer text or personal data
     */
    public function record(Connection $connection, string $migration, string $phase, iterable $issues, bool $retainPrevious = false): int
    {
        $name = str_replace('\\', '_', $migration).'_'.$phase.'.jsonl';
        if (1 !== preg_match('/^[A-Za-z0-9_]+\.jsonl$/D', $name)) {
            throw new RuntimeException('Invalid migration issue report name.');
        }

        $directory = $this->pendingDirectory($connection);
        $path = $directory.'/'.$name;
        if ($retainPrevious && is_file($path)) {
            $issues = $this->mergePreviousIssues($path, $issues);
        }
        $stream = null;
        $temporary = null;
        $count = 0;

        try {
            foreach ($issues as $issue) {
                if (null === $stream) {
                    $this->ensureDirectory($directory);
                    $temporary = $directory.'/'.bin2hex(random_bytes(16)).'.tmp';
                    $stream = fopen($temporary, 'xb');
                    if (false === $stream || !chmod($temporary, 0600)) {
                        throw new RuntimeException('Cannot create a private migration issue report.');
                    }
                }

                $line = json_encode(
                    ['migration' => $migration, 'phase' => $phase] + $issue,
                    JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES
                )."\n";
                if (\strlen($line) !== fwrite($stream, $line)) {
                    throw new RuntimeException('Cannot write the migration issue report.');
                }
                ++$count;
            }

            if (null !== $stream) {
                if (!fflush($stream) || !fsync($stream)) {
                    throw new RuntimeException('Cannot flush the migration issue report.');
                }
                fclose($stream);
                $stream = null;
                if (!rename($temporary, $path)) {
                    throw new RuntimeException('Cannot publish the migration issue report.');
                }
                $temporary = null;
            } elseif (is_file($path) && !unlink($path)) {
                // A previously rolled-back phase may have been repaired before retry.
                throw new RuntimeException('Cannot remove an obsolete migration issue snapshot.');
            }
        } finally {
            if (\is_resource($stream)) {
                fclose($stream);
            }
            if (null !== $temporary && is_file($temporary)) {
                unlink($temporary);
            }
        }

        return $count;
    }

    /**
     * Keep reports from interrupted attempts until the complete upgrade succeeds.
     * Archived reports remain available to the server administrator.
     *
     * @return array{count: int, path: string, report_id: string}|null
     */
    public function summarize(Connection $connection, bool $completed): ?array
    {
        $this->resetSummary();
        $directory = $this->pendingDirectory($connection);
        if (!is_dir($directory)) {
            return null;
        }

        $files = glob($directory.'/*.jsonl');
        if (false === $files) {
            throw new RuntimeException('Cannot list migration issue reports.');
        }

        $count = 0;
        foreach ($files as $file) {
            if (is_link($file)) {
                throw new RuntimeException('Unexpected symbolic link in migration issue reports.');
            }
            $stream = fopen($file, 'rb');
            if (false === $stream) {
                throw new RuntimeException('Cannot read the migration issue report.');
            }

            try {
                while (false !== fgets($stream)) {
                    ++$count;
                }
                if (!feof($stream)) {
                    throw new RuntimeException('Cannot read the complete migration issue report.');
                }
            } finally {
                fclose($stream);
            }
        }

        if (0 === $count) {
            return null;
        }

        // Publish the pending path before attempting an archive; it remains usable if
        // the rename fails, and no database changes are performed here.
        $this->lastSummary = ['count' => $count, 'path' => $this->relativePath($directory), 'report_id' => 'pending'];
        if ($completed) {
            $archive = \dirname($directory).'/'.gmdate('Ymd\THis\Z').'-'.bin2hex(random_bytes(6));
            if (!rename($directory, $archive)) {
                throw new RuntimeException('Cannot archive the migration issue reports.');
            }
            $this->lastSummary['path'] = $this->relativePath($archive);
            $this->lastSummary['report_id'] = basename($archive);
        }

        return $this->lastSummary;
    }

    /**
     * Return only report files from the current database's private directory.
     * The route accepts an identifier, never a filesystem path.
     *
     * @return list<string>
     */
    public function getReportFiles(Connection $connection, string $reportId): array
    {
        if (1 !== preg_match('/^(?:pending|[0-9]{8}T[0-9]{6}Z-[a-f0-9]{12})$/D', $reportId)) {
            return [];
        }
        $directory = \dirname($this->pendingDirectory($connection)).'/'.$reportId;
        if (is_link($directory) || !is_dir($directory) || !is_readable($directory)) {
            return [];
        }
        $files = glob($directory.'/*.jsonl');
        if (false === $files) {
            throw new RuntimeException('Cannot list migration issue reports.');
        }
        foreach ($files as $file) {
            if (is_link($file) || !is_file($file) || !is_readable($file)) {
                throw new RuntimeException('Cannot safely read a migration issue report.');
            }
        }

        return array_values($files);
    }

    /**
     * Non-transactional schema changes may have cleared a reference before a later
     * failure. Preserve those previously reported rows as well as newly found ones.
     * Only the exceptional rows, not the complete source table, are deduplicated.
     *
     * @param iterable<array<string, mixed>> $issues
     *
     * @return iterable<array<string, mixed>>
     */
    private function mergePreviousIssues(string $path, iterable $issues): iterable
    {
        if (is_link($path)) {
            throw new RuntimeException('Unexpected symbolic link in migration issue reports.');
        }
        $stream = fopen($path, 'rb');
        if (false === $stream) {
            throw new RuntimeException('Cannot read the previous migration issue snapshot.');
        }
        $seen = [];

        try {
            while (false !== ($line = fgets($stream))) {
                $issue = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                unset($issue['migration'], $issue['phase']);
                $key = hash('sha256', json_encode($issue, JSON_THROW_ON_ERROR));
                if (!isset($seen[$key])) {
                    $seen[$key] = true;

                    yield $issue;
                }
            }
            if (!feof($stream)) {
                throw new RuntimeException('Cannot read the complete previous migration issue snapshot.');
            }
        } finally {
            fclose($stream);
        }
        foreach ($issues as $issue) {
            // Invalid UTF-8, if any, is normalized the same way as the report file.
            $key = hash('sha256', json_encode($issue, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE));
            if (!isset($seen[$key])) {
                $seen[$key] = true;

                yield $issue;
            }
        }
    }

    private function pendingDirectory(Connection $connection): string
    {
        // Never include passwords, DSNs or user names in paths or report contents.
        $params = $connection->getParams();
        $identity = array_intersect_key($params, array_flip(['driver', 'host', 'port', 'unix_socket', 'dbname', 'path']));
        ksort($identity);
        $directory = rtrim($this->projectDir, '/').'/var/log/migration-data-issues';
        $databaseDirectory = $directory.'/'.hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR));
        foreach ([$directory, $databaseDirectory, $databaseDirectory.'/pending'] as $path) {
            if (is_link($path)) {
                throw new RuntimeException('Unexpected symbolic link in migration issue report directory.');
            }
            if (is_dir($path) && (!is_readable($path) || !is_executable($path))) {
                throw new RuntimeException('The private migration issue report directory cannot be read. Use the same OS account for the upgrade.');
            }
        }

        return $databaseDirectory.'/pending';
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create the migration issue report directory.');
        }
        if (!is_readable($directory) || !is_writable($directory)) {
            throw new RuntimeException('The migration issue report directory is not accessible.');
        }
    }

    private function relativePath(string $path): string
    {
        return substr($path, \strlen(rtrim($this->projectDir, '/')) + 1);
    }
}
