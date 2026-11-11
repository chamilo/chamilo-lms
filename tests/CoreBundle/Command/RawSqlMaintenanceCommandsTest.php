<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Command;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The guards of the raw-SQL maintenance commands (migration repairs, multi-URL conversion): which invocation is
 * read-only, which one refuses to write, and that a refused or dry run leaves the database untouched. Only the
 * read-only and refused paths are run: a real conversion inserts resource_node rows (deadlocks against concurrent
 * suites) and the repairs write across many tables. The test database is the installer one: a single access URL.
 * The commands run inside the DAMA transaction, nothing is committed.
 */
final class RawSqlMaintenanceCommandsTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private function tester(string $name): CommandTester
    {
        return new CommandTester((new Application(self::$kernel))->find($name));
    }

    private function scalar(string $sql, array $params = []): mixed
    {
        return $this->getEntityManager()->getConnection()->fetchOne($sql, $params);
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testMultiUrlConversionIsReadOnlyByDefault(): void
    {
        $urls = (int) $this->scalar('SELECT COUNT(*) FROM access_url');
        $url = (string) $this->scalar('SELECT url FROM access_url ORDER BY id LIMIT 1');
        $users = (int) $this->scalar('SELECT COUNT(*) FROM access_url_rel_user');
        $nodes = (int) $this->scalar('SELECT COUNT(*) FROM resource_node');

        $tester = $this->tester('app:multi-url:convert');
        $status = $tester->execute(['admin-url' => 'https://sv2-admin.invalid/']);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('Dry-run complete. No changes were committed.', $tester->getDisplay());
        $this->assertStringContainsString('Re-run with --apply to write', $tester->getDisplay());
        $this->assertSame($urls, (int) $this->scalar('SELECT COUNT(*) FROM access_url'));
        $this->assertSame($url, (string) $this->scalar('SELECT url FROM access_url ORDER BY id LIMIT 1'));
        $this->assertSame($users, (int) $this->scalar('SELECT COUNT(*) FROM access_url_rel_user'));
        $this->assertSame($nodes, (int) $this->scalar('SELECT COUNT(*) FROM resource_node'));
    }

    public function testMultiUrlConversionWithPreservedIdPlansTheMoveWithoutWritingByDefault(): void
    {
        $url = (string) $this->scalar('SELECT url FROM access_url ORDER BY id LIMIT 1');

        $tester = $this->tester('app:multi-url:convert');
        $status = $tester->execute(['admin-url' => 'https://sv2-admin.invalid/', '--preserve-admin-id' => true]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('Keep current row as ADMIN', $tester->getDisplay());
        $this->assertSame($url, (string) $this->scalar('SELECT url FROM access_url ORDER BY id LIMIT 1'));
    }

    public function testMultiUrlConversionRefusesAPortalThatAlreadyHasSeveralUrlsWithoutForce(): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->executeStatement(
            "INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl, parent_id, tree_root)
             VALUES ('http://sv2-second.invalid/', 1, '', 1, 1, 2, 0, NULL, NULL)"
        );
        $urls = (int) $this->scalar('SELECT COUNT(*) FROM access_url');

        $tester = $this->tester('app:multi-url:convert');
        $status = $tester->execute(['admin-url' => 'https://sv2-admin.invalid/', '--apply' => true]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('expected exactly 1 row in access_url', $tester->getDisplay());
        $this->assertSame($urls, (int) $this->scalar('SELECT COUNT(*) FROM access_url'), 'Refused before any write');
        $this->assertSame(0, (int) $this->scalar("SELECT COUNT(*) FROM access_url WHERE url = 'https://sv2-admin.invalid/'"));
    }

    public function testMultiUrlConversionRequiresTheAdminUrlArgument(): void
    {
        $this->expectException(RuntimeException::class);
        $this->tester('app:multi-url:convert')->execute([]);
    }

    public function testQuizRelationRepairIsReadOnlyByDefaultAndRefusesApplyWithoutLegacyDatabase(): void
    {
        $relations = (int) $this->scalar('SELECT COUNT(*) FROM c_quiz_rel_question');

        $audit = $this->tester('chamilo:migration:repair-quiz-relations-from-legacy');
        $this->assertSame(Command::SUCCESS, $audit->execute([]));
        $this->assertStringContainsString('Read-only target audit', $audit->getDisplay());

        $apply = $this->tester('chamilo:migration:repair-quiz-relations-from-legacy');
        $this->assertSame(Command::FAILURE, $apply->execute(['--apply' => true]));
        $this->assertStringContainsString('--source-database is required with --apply', $apply->getDisplay());

        $this->assertSame($relations, (int) $this->scalar('SELECT COUNT(*) FROM c_quiz_rel_question'));
    }

    public function testQuizRelationRepairRejectsAnInjectedSourceDatabaseName(): void
    {
        $tester = $this->tester('chamilo:migration:repair-quiz-relations-from-legacy');

        $status = $tester->execute(['--source-database' => 'chamilo`; DROP TABLE user; --', '--apply' => true]);

        $this->assertSame(Command::INVALID, $status);
        $this->assertStringContainsString('only letters, numbers, and underscores', $tester->getDisplay());
        $this->assertGreaterThan(0, (int) $this->scalar('SELECT COUNT(*) FROM user'));
    }

    public function testQuizRelationRepairRefusesTheTargetDatabaseAsItsOwnLegacySource(): void
    {
        $db = (string) $this->scalar('SELECT DATABASE()');
        $tester = $this->tester('chamilo:migration:repair-quiz-relations-from-legacy');

        $status = $tester->execute(['--source-database' => $db, '--apply' => true]);

        // The legacy source must be another database: pointing it at the target is refused before any write.
        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Source and target databases must be different', $tester->getDisplay());
    }

    public function testGradebookStructureRepairRejectsACategoryWithoutExactlyOneCourse(): void
    {
        $tester = $this->tester('chamilo:migration:repair-gradebook-structure');

        $this->assertSame(Command::INVALID, $tester->execute(['--category-id' => 5]));
        $this->assertStringContainsString('--category-id requires exactly one --course-id', $tester->getDisplay());
        $this->assertSame(Command::INVALID, $this->tester('chamilo:migration:repair-gradebook-structure')->execute(['--category-id' => 5, '--course-id' => [1, 2]]));
    }

    public function testGradebookStructureRepairOfAnUnknownCourseFailsWithoutWriting(): void
    {
        $links = (int) $this->scalar('SELECT COUNT(*) FROM gradebook_link');
        $tester = $this->tester('chamilo:migration:repair-gradebook-structure');

        $status = $tester->execute(['--course-id' => [999999990]]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertSame($links, (int) $this->scalar('SELECT COUNT(*) FROM gradebook_link'));
    }

    public function testGroupForumRepairExitsCleanlyWithoutTheLegacyItemPropertyTable(): void
    {
        // A migrated database still holds the legacy c_item_property table; a fresh install does not, and the
        // command exits cleanly with an explanation (it used to fail with SQLSTATE 42S02) without repairing anything.
        $legacyTable = (int) $this->scalar("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'c_item_property'");
        if ($legacyTable > 0) {
            $this->markTestSkipped('The legacy c_item_property table exists: the repair would run for real.');
        }
        $nodes = (int) $this->scalar('SELECT COUNT(*) FROM resource_node');
        $tester = $this->tester('chamilo:migration:repair-group-forums-without-item-property');

        foreach ([['--dry-run' => true], []] as $args) {
            $status = $tester->execute($args);

            $this->assertSame(Command::SUCCESS, $status);
            $this->assertStringContainsString('does not exist: nothing to repair', $tester->getDisplay());
            $this->assertStringNotContainsString('Dry-run enabled', $tester->getDisplay());
        }
        $this->assertSame($nodes, (int) $this->scalar('SELECT COUNT(*) FROM resource_node'));
    }

    public function testRepairCommandsRefuseApplyCombinedWithTheDeprecatedDryRunFlag(): void
    {
        $nodes = (int) $this->scalar('SELECT COUNT(*) FROM resource_node');

        foreach ([
            'chamilo:migration:repair-gradebook-structure',
            'chamilo:migration:repair-quiz-questions-without-resource-node',
            'chamilo:migration:repair-forum-posts-without-item-property',
            'chamilo:migration:repair-translatehtml-language-blocks',
        ] as $name) {
            $tester = $this->tester($name);
            $status = $tester->execute(['--apply' => true, '--dry-run' => true, '--expected-count' => 0]);

            $this->assertSame(Command::FAILURE, $status, $name);
            $this->assertStringContainsString('--apply and --dry-run cannot be combined', $tester->getDisplay(), $name);
        }

        $tester = $this->tester('app:multi-url:convert');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot be combined');
        try {
            $tester->execute(['admin-url' => 'https://sv2-admin.invalid/', '--apply' => true, '--dry-run' => true]);
        } finally {
            $this->assertSame($nodes, (int) $this->scalar('SELECT COUNT(*) FROM resource_node'));
        }
    }

    public function testRepairCommandsRefuseApplyWithoutAnExpectedCountBeforeWritingAnything(): void
    {
        $nodes = (int) $this->scalar('SELECT COUNT(*) FROM resource_node');
        $links = (int) $this->scalar('SELECT COUNT(*) FROM gradebook_link');

        foreach ([
            'chamilo:migration:repair-gradebook-structure' => ['--course-id' => [999999990]],
            'chamilo:migration:repair-quiz-questions-without-resource-node' => [],
            'chamilo:migration:repair-forum-posts-without-item-property' => [],
            'chamilo:migration:repair-translatehtml-language-blocks' => [],
        ] as $name => $args) {
            $tester = $this->tester($name);
            $status = $tester->execute($args + ['--apply' => true]);

            $this->assertSame(Command::FAILURE, $status, $name);
            $this->assertStringContainsString('--expected-count is required with --apply', $tester->getDisplay(), $name);
        }

        $this->assertSame($nodes, (int) $this->scalar('SELECT COUNT(*) FROM resource_node'));
        $this->assertSame($links, (int) $this->scalar('SELECT COUNT(*) FROM gradebook_link'));
    }

    public function testBatchRepairCommandsAreReadOnlyByDefaultAndReportTheExpectedCount(): void
    {
        $nodes = (int) $this->scalar('SELECT COUNT(*) FROM resource_node');
        $questionsWithoutNode = (int) $this->scalar('SELECT COUNT(*) FROM c_quiz_question WHERE resource_node_id IS NULL');

        foreach ([
            'chamilo:migration:repair-quiz-questions-without-resource-node',
            'chamilo:migration:repair-forum-posts-without-item-property',
        ] as $name) {
            $tester = $this->tester($name);
            $status = $tester->execute([]);

            $this->assertSame(Command::SUCCESS, $status, $name);
            $this->assertStringContainsString('Read-only mode', $tester->getDisplay(), $name);
            $this->assertMatchesRegularExpression('/--apply --expected-count=\d+/', $tester->getDisplay(), $name);
        }

        $this->assertSame($nodes, (int) $this->scalar('SELECT COUNT(*) FROM resource_node'));
        $this->assertSame($questionsWithoutNode, (int) $this->scalar('SELECT COUNT(*) FROM c_quiz_question WHERE resource_node_id IS NULL'));
    }

    public function testBatchRepairApplyWithAWrongExpectedCountIsRefusedWithoutWriting(): void
    {
        $nodes = (int) $this->scalar('SELECT COUNT(*) FROM resource_node');
        $questionsWithoutNode = (int) $this->scalar('SELECT COUNT(*) FROM c_quiz_question WHERE resource_node_id IS NULL');

        $tester = $this->tester('chamilo:migration:repair-quiz-questions-without-resource-node');
        $status = $tester->execute(['--apply' => true, '--expected-count' => 999999]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Repair refused', $tester->getDisplay());
        $this->assertSame($nodes, (int) $this->scalar('SELECT COUNT(*) FROM resource_node'));
        $this->assertSame($questionsWithoutNode, (int) $this->scalar('SELECT COUNT(*) FROM c_quiz_question WHERE resource_node_id IS NULL'));
    }

    private function insertCorruptedTranslatedQuestion(): int
    {
        // The exact corruption shape the command repairs: one language block outside the wrapper, one inside it.
        $html = '<span class="mce-translatehtml" lang="fr">F</span>'
            .'<div class="tiny-content"><span class="mce-translatehtml" lang="en">E</span></div>';
        $conn = $this->getEntityManager()->getConnection();
        $conn->executeStatement(
            'INSERT INTO c_quiz_question (question, description, ponderation, position, type, level, mandatory)
             VALUES (:q, :d, 0, 0, 1, 1, 0)',
            ['q' => 'sv2 repair fixture', 'd' => $html]
        );

        return (int) $conn->lastInsertId();
    }

    public function testTranslateHtmlRepairIsReadOnlyByDefaultThenWritesOnlyWithTheExactExpectedCount(): void
    {
        $id = $this->insertCorruptedTranslatedQuestion();
        $before = (string) $this->scalar('SELECT description FROM c_quiz_question WHERE iid = ?', [$id]);

        // 1. default: reports, changes nothing
        $tester = $this->tester('chamilo:migration:repair-translatehtml-language-blocks');
        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Read-only mode', $display);
        $this->assertStringContainsString('c_quiz_question.description #'.$id, $display);
        $this->assertSame($before, (string) $this->scalar('SELECT description FROM c_quiz_question WHERE iid = ?', [$id]));
        $this->assertSame(1, preg_match('/--apply --expected-count=(\d+)/', $display, $m));
        $expected = (int) $m[1];
        $this->assertGreaterThanOrEqual(1, $expected);

        // 2. wrong expected count: refused, rolled back
        $tester = $this->tester('chamilo:migration:repair-translatehtml-language-blocks');
        $this->assertSame(Command::FAILURE, $tester->execute(['--apply' => true, '--expected-count' => $expected + 1]));
        $this->assertStringContainsString('Repair refused', $tester->getDisplay());
        $this->assertSame($before, (string) $this->scalar('SELECT description FROM c_quiz_question WHERE iid = ?', [$id]));

        // 3. exact expected count: written
        $tester = $this->tester('chamilo:migration:repair-translatehtml-language-blocks');
        $this->assertSame(Command::SUCCESS, $tester->execute(['--apply' => true, '--expected-count' => $expected]));
        $after = (string) $this->scalar('SELECT description FROM c_quiz_question WHERE iid = ?', [$id]);
        $this->assertNotSame($before, $after);
        $this->assertStringContainsString('lang="fr"', $after);
        $this->assertMatchesRegularExpression('/tiny-content.*lang="en".*lang="fr"/s', $after, 'The orphan block moved inside the wrapper');
    }

    public function testGradebookStructureRepairIsReadOnlyByDefault(): void
    {
        $links = (int) $this->scalar('SELECT COUNT(*) FROM gradebook_link');
        $tester = $this->tester('chamilo:migration:repair-gradebook-structure');

        // No persisted completion rule matches this course: the run fails loud, and writes nothing either way.
        $this->assertSame(Command::FAILURE, $tester->execute(['--course-id' => [999999990]]));
        $this->assertSame(Command::FAILURE, $this->tester('chamilo:migration:repair-gradebook-structure')->execute(['--course-id' => [999999990], '--apply' => true, '--expected-count' => 0]));
        $this->assertSame($links, (int) $this->scalar('SELECT COUNT(*) FROM gradebook_link'));
    }

    public function testCertificateHistoryRepairRefusesApplyWithoutAnExpectedCount(): void
    {
        $tester = $this->tester('chamilo:migration:repair-legacy-certificate-history');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('--expected-count is required with --apply');
        $tester->execute(['--source-database' => 'nolegacy', '--legacy-root' => '/nonexistent', '--apply' => true]);
    }
}
