<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Command;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
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

    public function testMultiUrlConversionDryRunWritesNothing(): void
    {
        $urls = (int) $this->scalar('SELECT COUNT(*) FROM access_url');
        $url = (string) $this->scalar('SELECT url FROM access_url ORDER BY id LIMIT 1');
        $users = (int) $this->scalar('SELECT COUNT(*) FROM access_url_rel_user');
        $nodes = (int) $this->scalar('SELECT COUNT(*) FROM resource_node');

        $tester = $this->tester('app:multi-url:convert');
        $status = $tester->execute(['admin-url' => 'https://sv2-admin.invalid/', '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('Dry-run complete. No changes were committed.', $tester->getDisplay());
        $this->assertSame($urls, (int) $this->scalar('SELECT COUNT(*) FROM access_url'));
        $this->assertSame($url, (string) $this->scalar('SELECT url FROM access_url ORDER BY id LIMIT 1'));
        $this->assertSame($users, (int) $this->scalar('SELECT COUNT(*) FROM access_url_rel_user'));
        $this->assertSame($nodes, (int) $this->scalar('SELECT COUNT(*) FROM resource_node'));
    }

    public function testMultiUrlConversionDryRunWithPreservedIdPlansTheMoveWithoutWriting(): void
    {
        $url = (string) $this->scalar('SELECT url FROM access_url ORDER BY id LIMIT 1');

        $tester = $this->tester('app:multi-url:convert');
        $status = $tester->execute(['admin-url' => 'https://sv2-admin.invalid/', '--dry-run' => true, '--preserve-admin-id' => true]);

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
        $status = $tester->execute(['admin-url' => 'https://sv2-admin.invalid/']);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('expected exactly 1 row in access_url', $tester->getDisplay());
        $this->assertSame($urls, (int) $this->scalar('SELECT COUNT(*) FROM access_url'), 'Refused before any write');
        $this->assertSame(0, (int) $this->scalar("SELECT COUNT(*) FROM access_url WHERE url = 'https://sv2-admin.invalid/'"));
    }

    public function testMultiUrlConversionRequiresTheAdminUrlArgument(): void
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
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
}
