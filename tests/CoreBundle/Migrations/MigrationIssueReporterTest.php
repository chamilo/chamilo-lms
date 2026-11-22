<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\CoreBundle\Migrations\MigrationIssueReporter;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

use const FILE_IGNORE_NEW_LINES;
use const JSON_THROW_ON_ERROR;

final class MigrationIssueReporterTest extends TestCase
{
    private string $directory;
    private MigrationIssueReporter $reporter;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/chamilo-migration-report-'.bin2hex(random_bytes(8));
        $this->reporter = new MigrationIssueReporter($this->directory);
        $this->connection = $this->connectionFor('test_database');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    public function testCleanPhaseDoesNotCreateAReport(): void
    {
        self::assertSame(0, $this->reporter->record($this->connection, 'Version1', 'clean', []));
        self::assertNull($this->reporter->summarize($this->connection, true));
        self::assertDirectoryDoesNotExist($this->directory);
    }

    public function testCompleteSnapshotSurvivesRetryAndIsArchivedOnSuccess(): void
    {
        $issues = static function (): iterable {
            for ($id = 1; $id <= 151; ++$id) {
                yield ['answer_iid' => $id, 'reason' => 'missing_target'];
            }
        };
        self::assertSame(151, $this->reporter->record($this->connection, 'Version1', 'matching', $issues()));
        $first = $this->reporter->summarize($this->connection, false);
        self::assertSame(151, $first['count']);
        self::assertStringEndsWith('/pending', $first['path']);
        self::assertSame(151, $this->reporter->record($this->connection, 'Version1', 'matching', $issues()));
        $summary = $this->reporter->summarize($this->connection, true);
        self::assertSame(151, $summary['count']);
        self::assertNotSame($first['path'], $summary['path']);
        $path = $this->directory.'/'.$summary['path'].'/Version1_matching.jsonl';
        self::assertFileExists($path);
        self::assertSame(0600, fileperms($path) & 0777);
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        self::assertCount(151, $lines);
        self::assertSame(151, json_decode($lines[150], true, 512, JSON_THROW_ON_ERROR)['answer_iid']);
        self::assertNull($this->reporter->summarize($this->connection, false));
        self::assertFileExists($path);
    }

    public function testRolledBackPhaseCanReplaceItsSnapshotWithoutRemovingOtherPhases(): void
    {
        $this->reporter->record($this->connection, 'Version1', 'matching', [['answer_iid' => 10]]);
        $this->reporter->record($this->connection, 'Version1', 'orphan', [['answer_iid' => 20]]);
        $this->reporter->record($this->connection, 'Version1', 'matching', []);
        $summary = $this->reporter->summarize($this->connection, false);
        self::assertSame(1, $summary['count']);
        self::assertFileDoesNotExist($this->directory.'/'.$summary['path'].'/Version1_matching.jsonl');
    }

    public function testNonTransactionalPhaseRetainsEarlierIssuesWithoutDuplicates(): void
    {
        $this->reporter->record($this->connection, 'Version2', 'survey', [['row_iid' => 1], ['row_iid' => 2]], true);
        self::assertSame(3, $this->reporter->record($this->connection, 'Version2', 'survey', [['row_iid' => 2], ['row_iid' => 3]], true));
        self::assertSame(3, $this->reporter->record($this->connection, 'Version2', 'survey', [], true));
        self::assertSame(3, $this->reporter->summarize($this->connection, false)['count']);
    }

    public function testDatabaseIsolationAndNoCredentialsInPath(): void
    {
        $this->reporter->record($this->connection, 'Version1', 'matching', [['answer_iid' => 1]]);
        $summary = $this->reporter->summarize($this->connection, false);
        self::assertStringNotContainsString('secret-password', $summary['path']);
        self::assertStringNotContainsString('test_database', $summary['path']);
        self::assertNull($this->reporter->summarize($this->connectionFor('another_database'), false));
    }

    public function testInterruptedSnapshotDoesNotReplacePreviousReport(): void
    {
        $this->reporter->record($this->connection, 'Version1', 'matching', [['answer_iid' => 7]]);
        $issues = static function (): iterable {
            yield ['answer_iid' => 8];

            throw new RuntimeException('Interrupted query');
        };

        try {
            $this->reporter->record($this->connection, 'Version1', 'matching', $issues());
            self::fail('Expected a failed snapshot.');
        } catch (RuntimeException $exception) {
            self::assertSame('Interrupted query', $exception->getMessage());
        }
        $summary = $this->reporter->summarize($this->connection, false);
        self::assertSame(1, $summary['count']);
        self::assertStringContainsString('"answer_iid":7', file_get_contents($this->directory.'/'.$summary['path'].'/Version1_matching.jsonl'));
        self::assertSame([], glob($this->directory.'/'.$summary['path'].'/*.tmp'));
    }

    public function testDownloadFilesAreScopedToTheRequestedDatabaseAndReportId(): void
    {
        $this->reporter->record($this->connection, 'Version1', 'matching', [['answer_iid' => 1]]);
        $summary = $this->reporter->summarize($this->connection, true);
        self::assertCount(1, $this->reporter->getReportFiles($this->connection, $summary['report_id']));
        self::assertSame([], $this->reporter->getReportFiles($this->connectionFor('another_database'), $summary['report_id']));
        self::assertSame([], $this->reporter->getReportFiles($this->connection, '../etc/passwd'));
        self::assertSame([], $this->reporter->getReportFiles($this->connection, 'pending'));
    }

    public function testRejectsPathTraversal(): void
    {
        $this->expectException(RuntimeException::class);
        $this->reporter->record($this->connection, '../Version1', 'matching', [['answer_iid' => 1]]);
    }

    private function connectionFor(string $database): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getParams')->willReturn([
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'dbname' => $database,
            'password' => 'secret-password',
        ]);

        return $connection;
    }
}
