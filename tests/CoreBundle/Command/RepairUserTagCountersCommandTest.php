<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Command;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * app:repair-user-tag-counters recalculates tag.count from the relation rows. Read-only by default, writes only with
 * --apply and the exact --expected-count of the read-only run. Fixtures are prepared INSERTs (users 1 and 2 exist),
 * everything runs inside the DAMA transaction.
 */
final class RepairUserTagCountersCommandTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private int $drifted;   // 2 links, stored 7
    private int $orphan;    // 0 links, stored 3
    private int $correct;   // 1 link, stored 1

    protected function setUp(): void
    {
        self::bootKernel();
        $this->drifted = $this->tag('sv2 drifted', 7);
        $this->orphan = $this->tag('sv2 orphan', 3);
        $this->correct = $this->tag('sv2 correct', 1);
        $this->link(1, $this->drifted);
        $this->link(2, $this->drifted);
        $this->link(1, $this->correct);
    }

    private function conn(): \Doctrine\DBAL\Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function tag(string $name, int $count): int
    {
        $this->conn()->executeStatement('INSERT INTO tag (field_id, tag, count) VALUES (NULL, ?, ?)', [$name, $count]);

        return (int) $this->conn()->lastInsertId();
    }

    private function link(int $userId, int $tagId): void
    {
        $this->conn()->executeStatement('INSERT INTO user_rel_tag (user_id, tag_id) VALUES (?, ?)', [$userId, $tagId]);
    }

    private function stored(int $tagId): int
    {
        return (int) $this->conn()->fetchOne('SELECT count FROM tag WHERE id = ?', [$tagId]);
    }

    private function runCommand(array $args): CommandTester
    {
        $tester = new CommandTester((new Application(self::$kernel))->find('app:repair-user-tag-counters'));
        $tester->execute($args);

        return $tester;
    }

    /**
     * @return array{int, int} exit status and the count suggested by the read-only run
     */
    private function readOnlyRun(): array
    {
        $tester = $this->runCommand([]);
        $this->assertSame(1, preg_match('/--apply --expected-count=(\d+)/', $tester->getDisplay(), $m), $tester->getDisplay());

        return [$tester->getStatusCode(), (int) $m[1]];
    }

    public function testDefaultRunReportsTheDriftAndChangesNothing(): void
    {
        $tester = $this->runCommand([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Read-only mode', $tester->getDisplay());
        $this->assertStringContainsString('sv2 drifted', $tester->getDisplay());
        $this->assertStringContainsString('sv2 orphan', $tester->getDisplay());
        $this->assertStringNotContainsString('sv2 correct', $tester->getDisplay());
        $this->assertSame([7, 3, 1], [$this->stored($this->drifted), $this->stored($this->orphan), $this->stored($this->correct)]);
        $this->assertSame(2, $this->conn()->fetchOne('SELECT COUNT(*) FROM user_rel_tag WHERE tag_id = ?', [$this->drifted]) + 0);
    }

    public function testApplyWithoutAnExpectedCountIsRefusedAndWritesNothing(): void
    {
        $tester = $this->runCommand(['--apply' => true]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('--expected-count is required with --apply', $tester->getDisplay());
        $this->assertSame([7, 3, 1], [$this->stored($this->drifted), $this->stored($this->orphan), $this->stored($this->correct)]);
    }

    public function testApplyWithAWrongExpectedCountIsRefusedAndWritesNothing(): void
    {
        [, $expected] = $this->readOnlyRun();

        $tester = $this->runCommand(['--apply' => true, '--expected-count' => $expected + 1]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Repair refused', $tester->getDisplay());
        $this->assertSame([7, 3, 1], [$this->stored($this->drifted), $this->stored($this->orphan), $this->stored($this->correct)]);
    }

    public function testApplyCombinedWithTheDeprecatedDryRunFlagIsRefused(): void
    {
        $tester = $this->runCommand(['--apply' => true, '--dry-run' => true, '--expected-count' => 0]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertSame(7, $this->stored($this->drifted));
    }

    public function testApplyWithTheExactCountRecalculatesFromTheRelationRowsThenFindsNothingLeft(): void
    {
        [, $expected] = $this->readOnlyRun();
        $this->assertGreaterThanOrEqual(2, $expected);

        $tester = $this->runCommand(['--apply' => true, '--expected-count' => $expected]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
        $this->assertSame([2, 0, 1], [$this->stored($this->drifted), $this->stored($this->orphan), $this->stored($this->correct)]);
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM tag t WHERE t.count <> (SELECT COUNT(*) FROM user_rel_tag u WHERE u.tag_id = t.id) + (SELECT COUNT(*) FROM extra_field_rel_tag e WHERE e.tag_id = t.id) + (SELECT COUNT(*) FROM portfolio_rel_tag p WHERE p.tag_id = t.id)'));
        // The relation rows are never touched.
        $this->assertSame(2, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM user_rel_tag WHERE tag_id = ?', [$this->drifted]));

        $second = $this->runCommand(['--apply' => true, '--expected-count' => 0]);
        $this->assertSame(Command::SUCCESS, $second->getStatusCode());
        $this->assertStringContainsString('Nothing to repair', $second->getDisplay());
    }
}
