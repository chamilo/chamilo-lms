<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\CoreBundle\Migrations\MigrationIssueReporter;
use Chamilo\CoreBundle\Migrations\Schema\V200\Version20170904145500;
use Chamilo\CoreBundle\Migrations\Schema\V200\Version20180319145700;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

use const FILE_IGNORE_NEW_LINES;
use const JSON_THROW_ON_ERROR;

/**
 * Run only on the disposable, socket-only MariaDB server created for issue #9462.
 * Never connect to Chamilo's configured database or use its credentials.
 */
final class LegacyQuizInconsistencyMigrationTest extends TestCase
{
    private ?Connection $server = null;
    private ?Connection $connection = null;
    private ?string $database = null;
    private ?string $reportDirectory = null;
    private MigrationIssueReporter $reporter;

    protected function setUp(): void
    {
        $socket = getenv('CHAMILO_9462_TEST_SOCKET');
        if (false === $socket || '' === $socket) {
            self::markTestSkipped('Use the isolated MariaDB validation script for issue #9462.');
        }
        $directory = realpath(\dirname($socket));
        self::assertIsString($directory);
        self::assertMatchesRegularExpression('~^/tmp/chamilo-9462-test\.[A-Za-z0-9]+$~D', $directory);
        self::assertSame($directory.'/server.sock', $socket);
        self::assertFileExists($directory.'/.chamilo-9462-test');
        self::assertSame('socket', filetype($socket));
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        $params = ['driver' => 'pdo_mysql', 'unix_socket' => $socket, 'user' => 'root', 'password' => ''];
        $this->server = DriverManager::getConnection($params, $configuration);
        self::assertSame(1, (int) $this->server->fetchOne('SELECT @@skip_networking'));
        self::assertSame($directory.'/data', rtrim((string) $this->server->fetchOne('SELECT @@datadir'), '/'));
        $this->database = 'chamilo_9462_test_'.bin2hex(random_bytes(6));
        $this->server->executeStatement('CREATE DATABASE '.$this->database.' CHARACTER SET utf8mb4');
        $this->connection = DriverManager::getConnection($params + ['dbname' => $this->database], $configuration);
        $this->reportDirectory = $directory.'/report-'.bin2hex(random_bytes(6));
        $this->reporter = new MigrationIssueReporter($this->reportDirectory);
    }

    protected function tearDown(): void
    {
        $this->connection?->close();
        if (null !== $this->server && null !== $this->database) {
            $this->server->executeStatement('DROP DATABASE '.$this->database);
        }
        $this->server?->close();
        if (null !== $this->reportDirectory) {
            (new Filesystem())->remove($this->reportDirectory);
        }
    }

    public function testCompleteMigrationKeepsValidMappingsAndReportsAllInvalidRows(): void
    {
        $this->createQuizSchema();
        $this->connection->executeStatement('INSERT INTO c_quiz_answer VALUES
            (1001,1,5001,1,1,0,\'Option A\',0),
            (1002,2,5002,1,1,5001,\'Valid auto\',2),
            (1003,3,5003,1,1,1,\'Valid local\',2),
            (1004,4,5004,1,1,1011,\'Valid iid\',2),
            (1005,5,5005,1,1,9999,\'Missing target\',2),
            (1006,6,5006,1,1,6001,\'Other question\',2),
            (1007,7,5007,1,1,7001,\'Duplicate auto\',2),
            (1008,8,5008,1,1,21,\'Duplicate local\',2),
            (1009,9,5009,1,1,5002,\'Target is a prompt\',2),
            (1010,10,1001,1,1,0,\'Legacy auto collides with iid\',0),
            (1011,11,8001,1,1,0,\'Already normalized option\',0),
            (1021,21,7021,1,1,0,\'Duplicate local A\',0),
            (1022,21,7022,1,1,0,\'Duplicate local B\',0),
            (1031,31,7001,1,1,0,\'Duplicate auto A\',0),
            (1032,32,7001,1,1,0,\'Duplicate auto B\',0),
            (1101,1,6001,1,2,0,\'Other question option\',0),
            (1201,1,9001,1,3,1,\'Non-matching answer\',1),
            (2001,1,5001,2,1,0,\'Other course option\',0),
            (2002,2,5002,2,1,5001,\'Other course answer\',2),
            (3001,1,9002,1,999,1,\'Orphan answer\',1)');
        $this->connection->executeStatement('INSERT INTO c_quiz_rel_question VALUES (1,1,1,1),(2,1,1,999)');
        $this->connection->executeStatement("INSERT INTO track_e_attempt VALUES (1,1,1,'1',9.5)");
        $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM c_quiz_answer');

        $this->executeFullMigration();

        self::assertSame(1001, $this->correct(1002));
        self::assertSame(1001, $this->correct(1003));
        self::assertSame(1011, $this->correct(1004));
        self::assertSame(2001, $this->correct(2002));
        self::assertSame(1, $this->correct(1201));
        foreach ([1005 => 9999, 1006 => 6001, 1007 => 7001, 1008 => 21, 1009 => 5002] as $answer => $original) {
            self::assertNull($this->connection->fetchOne('SELECT question_id FROM c_quiz_answer WHERE iid = ?', [$answer]));
            self::assertSame($original, $this->correct($answer));
        }
        self::assertNull($this->connection->fetchOne('SELECT question_id FROM c_quiz_answer WHERE iid = 3001'));
        self::assertSame($count, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM c_quiz_answer'));
        self::assertSame('Missing target', $this->connection->fetchOne('SELECT answer FROM c_quiz_answer WHERE iid = 1005'));
        self::assertSame(9.5, (float) $this->connection->fetchOne('SELECT marks FROM track_e_attempt WHERE id = 1'));
        self::assertSame(101, (int) $this->connection->fetchOne('SELECT question_id FROM track_e_attempt WHERE id = 1'));
        self::assertSame('1001', (string) $this->connection->fetchOne('SELECT answer FROM track_e_attempt WHERE id = 1'));
        self::assertSame(10, (int) $this->connection->fetchOne('SELECT quiz_id FROM c_quiz_rel_question WHERE iid = 1'));
        self::assertNull($this->connection->fetchOne('SELECT question_id FROM c_quiz_rel_question WHERE iid = 2'));
        self::assertTrue($this->connection->createSchemaManager()->introspectTable('c_quiz_answer')->hasForeignKey('FK_AEBC3EFF1E27F6BF'));
        self::assertFalse($this->connection->createSchemaManager()->tablesExist(['migration_v20170904145500_reference_state']));
        $summary = $this->reporter->summarize($this->connection, true);
        self::assertSame(7, $summary['count']); // five matching, one orphan answer, one orphan relation
        $records = $this->reportRows($summary['path']);
        $answers = array_column(array_filter($records, static fn (array $row): bool => 'matching_answers' === $row['phase']), null, 'answer_iid');
        self::assertSame('missing_matching_target', $answers[1005]['reason']);
        self::assertSame('invalid_legacy_auto_target', $answers[1006]['reason']);
        self::assertSame('duplicate_legacy_auto_id', $answers[1007]['reason']);
        self::assertSame('duplicate_legacy_local_id', $answers[1008]['reason']);
        self::assertSame('invalid_legacy_auto_target', $answers[1009]['reason']);
        self::assertSame(101, (int) $answers[1005]['question_iid']);
        self::assertSame(1, (int) $answers[1005]['legacy_question_id']);
        self::assertSame(5005, (int) $answers[1005]['legacy_answer_auto_id']);
        self::assertSame(9999, (int) $answers[1005]['original_reference']);
    }

    public function testCleanMigrationProducesNoReport(): void
    {
        $this->createQuizSchema();
        $this->connection->executeStatement("INSERT INTO c_quiz_answer VALUES (1001,1,5001,1,1,0,'Option',0),(1002,2,5002,1,1,5001,'Prompt',2)");
        $this->executeFullMigration();
        self::assertSame(1001, $this->correct(1002));
        self::assertNull($this->reporter->summarize($this->connection, true));
    }

    public function testMoreThanOneHundredWarningsAndPhaseRetryDoNotDuplicateOrRemap(): void
    {
        $this->createQuizSchema();
        $this->connection->executeStatement("INSERT INTO c_quiz_answer VALUES (1001,1,5001,1,1,0,'Option',0),(1002,2,5002,1,1,5001,'Prompt',2),(1010,10,1001,1,1,0,'Collision',0)");
        for ($i = 1; $i <= 125; ++$i) {
            $this->connection->insert('c_quiz_answer', ['iid' => 2000 + $i, 'id' => 100 + $i, 'id_auto' => 10000 + $i, 'c_id' => 1, 'question_id' => 1, 'correct' => 999999, 'answer' => 'Missing', 'ponderation' => 1]);
        }
        // Stop before the queued DDL, as if a later migration step had failed.
        $this->normalize();
        self::assertSame(1001, $this->correct(1002));
        self::assertSame(125, $this->reporter->summarize($this->connection, false)['count']);
        $this->normalize();
        self::assertSame(1001, $this->correct(1002));
        self::assertSame(125, $this->reporter->summarize($this->connection, false)['count']);
    }

    public function testSqlFailureRollsBackDataAndRetryPreservesReport(): void
    {
        $this->createQuizSchema();
        $this->connection->executeStatement("INSERT INTO c_quiz_answer VALUES (1001,1,5001,1,101,0,'Option',0),(1002,2,5002,1,101,5001,'Prompt',2),(1005,5,5005,1,101,9999,'Missing',2)");
        $this->connection->executeStatement("CREATE TRIGGER reject_detach BEFORE UPDATE ON c_quiz_answer FOR EACH ROW BEGIN IF OLD.iid = 1005 AND NEW.question_id IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic critical failure'; END IF; END");
        $migration = $this->migration();
        $this->invoke($migration, 'ensureReferenceStateTable');

        try {
            $this->invoke($migration, 'normalizeMatchingAnswerReferences', $this->connection->createSchemaManager()->introspectSchema());
            self::fail('The SQL failure must not be ignored.');
        } catch (Throwable $exception) {
            self::assertStringContainsString('Synthetic critical failure', $exception->getMessage());
        }
        self::assertSame(5001, $this->correct(1002));
        self::assertSame(101, (int) $this->connection->fetchOne('SELECT question_id FROM c_quiz_answer WHERE iid = 1005'));
        self::assertSame(1, $this->reporter->summarize($this->connection, false)['count']);
        $this->connection->executeStatement('DROP TRIGGER reject_detach');
        $this->invoke($this->migration(), 'normalizeMatchingAnswerReferences', $this->connection->createSchemaManager()->introspectSchema());
        self::assertSame(1001, $this->correct(1002));
        self::assertNull($this->connection->fetchOne('SELECT question_id FROM c_quiz_answer WHERE iid = 1005'));
        self::assertSame(1, $this->reporter->summarize($this->connection, false)['count']);
    }

    public function testDuplicateQuestionIdentityRemainsFatal(): void
    {
        $this->createQuizSchema();
        $this->connection->executeStatement('INSERT INTO c_quiz_question VALUES (999,1,1,4)');
        $this->expectExceptionMessage('duplicated c_quiz_question');
        $this->normalize();
    }

    public function testUnresolvedHistoricalAttemptRemainsFatal(): void
    {
        $this->createQuizSchema();
        $this->connection->executeStatement("INSERT INTO track_e_attempt VALUES (1,1,999,'1',1)");
        $this->expectExceptionMessage('unresolved question references');
        $this->normalize();
    }

    public function testMissingReporterRefusesToDetachData(): void
    {
        $this->createQuizSchema();
        $this->connection->executeStatement("INSERT INTO c_quiz_answer VALUES (1001,1,5001,1,101,9999,'Missing',2)");
        $migration = new Version20170904145500($this->connection, new NullLogger());
        $this->invoke($migration, 'ensureReferenceStateTable');

        try {
            $this->invoke($migration, 'normalizeMatchingAnswerReferences', $this->connection->createSchemaManager()->introspectSchema());
            self::fail('A report is mandatory before detaching a row.');
        } catch (Throwable $exception) {
            self::assertStringContainsString('reporting is not configured', $exception->getMessage());
        }
        self::assertSame(9999, $this->correct(1001));
        self::assertSame(101, (int) $this->connection->fetchOne('SELECT question_id FROM c_quiz_answer WHERE iid = 1001'));
    }

    public function testSurveyOrphanReportsSurviveNonTransactionalRetry(): void
    {
        $this->connection->executeStatement('CREATE TABLE course (id INT PRIMARY KEY, code VARCHAR(40)) ENGINE=InnoDB');
        $this->connection->executeStatement("INSERT INTO course VALUES (1,'SURVEY')");
        $this->connection->executeStatement('CREATE TABLE c_survey (iid INT PRIMARY KEY, c_id INT, survey_id INT) ENGINE=InnoDB');
        $this->connection->executeStatement('INSERT INTO c_survey VALUES (101,1,1)');
        foreach (['c_survey_question', 'c_survey_question_option'] as $table) {
            $this->connection->executeStatement('CREATE TABLE '.$table.' (iid INT PRIMARY KEY, c_id INT, survey_id INT DEFAULT NULL) ENGINE=InnoDB');
            $this->connection->executeStatement('INSERT INTO '.$table.' VALUES (1,1,999)');
        }
        $migration = new Version20180319145700($this->connection, new NullLogger());
        $migration->setIssueReporter($this->reporter);
        $this->invoke($migration, 'validateLegacySurveyReferences');
        self::assertSame(2, $this->reporter->summarize($this->connection, false)['count']);
        // The original survey migration sets unresolved survey_id values to NULL.
        $this->connection->executeStatement('UPDATE c_survey_question SET survey_id = NULL');
        $this->invoke($migration, 'validateLegacySurveyReferences');
        self::assertSame(2, $this->reporter->summarize($this->connection, false)['count']);
    }

    private function createQuizSchema(): void
    {
        foreach ([
            'course (id INT PRIMARY KEY, code VARCHAR(40))',
            'resource_node (id INT PRIMARY KEY)',
            'c_quiz (iid INT PRIMARY KEY, id INT, c_id INT, active INT, type INT, save_correct_answers INT, page_result_configuration LONGTEXT)',
            'c_quiz_question (iid INT PRIMARY KEY, id INT, c_id INT, type INT)',
            'c_quiz_answer (iid INT PRIMARY KEY, id INT, id_auto INT, c_id INT, question_id INT NOT NULL, correct INT, answer TEXT, ponderation DOUBLE)',
            'c_quiz_question_category (iid INT PRIMARY KEY, description LONGTEXT)',
            'c_quiz_question_option (iid INT PRIMARY KEY, id INT, c_id INT, question_id INT)',
            'c_quiz_rel_category (iid INT PRIMARY KEY, c_id INT, exercise_id INT DEFAULT NULL, category_id INT DEFAULT NULL, count_questions INT)',
            'c_quiz_rel_question (iid INT PRIMARY KEY, c_id INT, exercice_id INT NOT NULL, question_id INT NOT NULL)',
            'c_quiz_question_rel_category (iid INT PRIMARY KEY, c_id INT, category_id INT NOT NULL, question_id INT NOT NULL)',
            'track_e_attempt (id INT PRIMARY KEY, c_id INT, question_id INT, answer TEXT, marks DOUBLE)',
        ] as $table) {
            $this->connection->executeStatement('CREATE TABLE '.$table.' ENGINE=InnoDB');
        }
        $this->connection->executeStatement("INSERT INTO course VALUES (1,'COURSE1'),(2,'COURSE2')");
        $this->connection->executeStatement("INSERT INTO c_quiz VALUES (10,1,1,1,1,0,'[]'),(20,1,2,1,1,0,'[]')");
        $this->connection->executeStatement('INSERT INTO c_quiz_question VALUES (101,1,1,4),(102,2,1,4),(103,3,1,1),(201,1,2,4)');
    }

    private function migration(): Version20170904145500
    {
        $migration = new Version20170904145500($this->connection, new NullLogger());
        $migration->setIssueReporter($this->reporter);

        return $migration;
    }

    private function normalize(): void
    {
        $this->invoke($this->migration(), 'normalizeLegacyQuizReferences', $this->connection->createSchemaManager()->introspectSchema());
    }

    private function executeFullMigration(): void
    {
        $migration = $this->migration();
        $migration->up($this->connection->createSchemaManager()->introspectSchema());
        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
        $migration->postUp($this->connection->createSchemaManager()->introspectSchema());
    }

    private function invoke(object $migration, string $method, mixed ...$arguments): void
    {
        (new ReflectionMethod($migration, $method))->invoke($migration, ...$arguments);
    }

    private function correct(int $answer): int
    {
        return (int) $this->connection->fetchOne('SELECT correct FROM c_quiz_answer WHERE iid = ?', [$answer]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function reportRows(string $path): array
    {
        $rows = [];
        foreach (glob($this->reportDirectory.'/'.$path.'/*.jsonl') as $file) {
            foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
                $rows[] = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            }
        }

        return $rows;
    }
}
