<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\CoreBundle\Command\DoctrineMigrationsMigrateCommandDecorator;
use Chamilo\CoreBundle\Controller\Admin\MigrationIssueReportController;
use Chamilo\CoreBundle\Migrations\MigrationIssueReporter;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const JSON_THROW_ON_ERROR;

final class MigrationIssueIntegrationTest extends TestCase
{
    private string $directory;
    private MigrationIssueReporter $reporter;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/chamilo-9462-integration-'.bin2hex(random_bytes(8));
        $this->reporter = new MigrationIssueReporter($this->directory);
        $this->connection = $this->createMock(Connection::class);
        $this->connection->method('getParams')->willReturn(['driver' => 'pdo_mysql', 'dbname' => 'test']);
        $this->reporter->record($this->connection, 'Version1', 'matching', [['course_code' => '=1+1', 'answer_iid' => 17, 'reason' => 'missing_target', 'extra_id' => 31]]);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    public function testSuccessfulNonInteractiveMigrationArchivesReport(): void
    {
        $tester = $this->commandTester(0);
        self::assertSame(0, $tester->execute([], ['interactive' => false]));
        self::assertStringContainsString('Migration completed with data warnings: 1', $tester->getDisplay());
        self::assertNotSame('pending', $this->reporter->getLastSummary()['report_id']);
    }

    public function testFailedMigrationRetainsReportAndExitCode(): void
    {
        $tester = $this->commandTester(1);
        self::assertSame(1, $tester->execute([], ['interactive' => false]));
        self::assertStringContainsString('Migration did not complete', $tester->getDisplay());
        self::assertSame('pending', $this->reporter->getLastSummary()['report_id']);
    }

    public function testDryRunDoesNotConsumeReport(): void
    {
        $tester = $this->commandTester(0);
        self::assertSame(0, $tester->execute(['--dry-run' => true], ['interactive' => false]));
        self::assertNull($this->reporter->getLastSummary());
        self::assertCount(1, $this->reporter->getReportFiles($this->connection, 'pending'));
    }

    public function testDownloadIsGlobalAdminOnlyAndEscapesCsvFormulas(): void
    {
        $attributes = (new ReflectionClass(MigrationIssueReportController::class))->getAttributes(IsGranted::class);
        self::assertCount(1, $attributes);
        self::assertSame('ROLE_GLOBAL_ADMIN', $attributes[0]->getArguments()[0]);
        $controller = new MigrationIssueReportController();
        $response = $controller('pending', $this->connection, $this->reporter);
        self::assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        ob_start();

        try {
            $response->sendContent();
        } finally {
            $csv = ob_get_clean();
        }
        $lines = explode("\n", trim($csv));
        self::assertCount(2, $lines);
        $header = array_map('strval', str_getcsv($lines[0], ',', '"', ''));
        $row = array_combine($header, str_getcsv($lines[1], ',', '"', ''));
        self::assertStringNotContainsString('=', substr($row['course_code'], 0, 1));
        self::assertStringContainsString('=1+1', $row['course_code']);
        self::assertSame('17', $row['answer_iid']);
        self::assertSame(31, json_decode($row['details_json'], true, 512, JSON_THROW_ON_ERROR)['extra_id']);
    }

    public function testUnknownReportReturnsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        (new MigrationIssueReportController())('20261121T010203Z-abcdef123456', $this->connection, $this->reporter);
    }

    private function commandTester(int $exitCode): CommandTester
    {
        $inner = new Command('doctrine:migrations:migrate');
        $inner->addArgument('version', InputArgument::OPTIONAL, '', 'latest');
        $inner->addOption('dry-run', null, InputOption::VALUE_NONE);
        $inner->setCode(static fn (): int => $exitCode);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getConnection')->willReturn($this->connection);

        return new CommandTester(new DoctrineMigrationsMigrateCommandDecorator($inner, $this->reporter, $registry));
    }
}
