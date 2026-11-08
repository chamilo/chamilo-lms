<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\UserMergeHelper;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Every table the merge moves to the target, driven by the helper's own rule lists (so a rule naming a table or a
 * column that no longer exists fails here, and so does a table that is dropped from the lists without noticing).
 * Rows are built from information_schema with filler values; the foreign keys are switched off for the test
 * (a session variable, not DDL) because the parents (courses, learning paths, tickets...) are irrelevant here.
 * Tag sv3_.
 */
final class UserMergeHelperOwnedTablesTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private UserMergeHelper $helper;
    private int $fillSeq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->helper = self::getContainer()->get(UserMergeHelper::class);
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
    }

    protected function tearDown(): void
    {
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        parent::tearDown();
    }

    private function rules(string $method): array
    {
        $m = new ReflectionMethod(UserMergeHelper::class, $method);

        return $m->invoke($this->helper);
    }

    private function insert(string $table, array $values): void
    {
        $cols = $this->conn()->fetchAllAssociative(
            'SELECT column_name AS n, data_type AS t, column_type AS ct, is_nullable AS nul, column_default AS d, extra AS x
             FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ordinal_position',
            [$table]
        );
        $this->assertNotEmpty($cols, "Table {$table} must exist");
        $names = [];
        $params = [];
        foreach ($cols as $c) {
            $n = $c['n'];
            if (array_key_exists($n, $values)) {
                $names[] = '`'.$n.'`';
                $params[] = $values[$n];
                continue;
            }
            if (str_contains((string) $c['x'], 'auto_increment') || 'YES' === $c['nul'] || null !== $c['d']) {
                continue;
            }
            $names[] = '`'.$n.'`';
            $params[] = $this->filler($c, ++$this->fillSeq);
        }
        $this->conn()->executeStatement(
            'INSERT INTO `'.$table.'` ('.implode(', ', $names).') VALUES ('.implode(', ', array_fill(0, \count($params), '?')).')',
            $params
        );
    }

    private function filler(array $c, int $seq): mixed
    {
        return match (true) {
            \in_array($c['t'], ['int', 'bigint', 'smallint', 'tinyint', 'mediumint', 'double', 'float', 'decimal'], true) => 0,
            'date' === $c['t'] => '2020-01-01',
            \in_array($c['t'], ['datetime', 'timestamp'], true) => '2020-01-01 00:00:00',
            'json' === $c['t'] => '[]',
            'enum' === $c['t'] => explode("','", trim(substr($c['ct'], 5, -1), "'"))[0],
            \in_array($c['t'], ['binary', 'varbinary', 'blob', 'longblob'], true) => 'x'.$seq,
            default => 'sv3'.$seq,
        };
    }

    private function rows(string $table, string $field, int $userId): int
    {
        return (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM `{$table}` WHERE `{$field}` = ?", [$userId]);
    }

    public function testEveryUpdateRuleMovesTheSourceRowsOnly(): void
    {
        $keep = $this->rawUser('keep', 5, 1, null);
        $merge = $this->rawUser('merge', 5, 1, null);
        $bystander = $this->rawUser('bystander', 5, 1, null);
        $rules = $this->rules('getUpdateRules');
        $this->assertGreaterThan(50, \count($rules));

        foreach ($rules as $rule) {
            $t = $rule['table'];
            $f = $rule['field'];
            // One row of the merged account and one of a bystander per rule, tagged by the column under test.
            $this->insert($t, [$f => $merge]);
            $this->insert($t, [$f => $bystander]);
        }

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        // A table can carry several rules (two columns): count per column.
        foreach ($rules as $rule) {
            $this->assertSame(0, $this->rows($rule['table'], $rule['field'], $merge), $rule['table'].'.'.$rule['field'].' still points to the merged account');
            $this->assertGreaterThanOrEqual(1, $this->rows($rule['table'], $rule['field'], $keep), $rule['table'].'.'.$rule['field'].' not moved to the target');
            $this->assertGreaterThanOrEqual(1, $this->rows($rule['table'], $rule['field'], $bystander), $rule['table'].'.'.$rule['field'].' of a bystander must stay');
        }
    }

    public function testEveryPivotRuleKeepsTheTargetRowOnConflictAndMovesTheOthers(): void
    {
        foreach ($this->rules('getPivotMergeRules') as $rule) {
            $t = $rule['table'];
            $f = $rule['userField'];
            $first = $rule['matchColumns'][0];
            $keep = $this->rawUser('keep', 5, 1, null);
            $merge = $this->rawUser('merge', 5, 1, null);

            // Nullable match columns are set explicitly: pivotMerge compares them with '=', so NULLs never match.
            $zeros = array_fill_keys($rule['matchColumns'], 0);
            $this->insert($t, [$f => $keep] + $zeros);
            $this->insert($t, [$f => $merge] + $zeros);                   // same match values as the target: dropped
            $this->insert($t, [$f => $merge, $first => 77] + $zeros);     // other match value: moved

            $this->assertTrue($this->helper->mergeUsers($keep, $merge), $t);

            $this->assertSame(2, $this->rows($t, $f, $keep), $t.' target rows');
            $this->assertSame(0, $this->rows($t, $f, $merge), $t.' source rows');
        }
    }

    public function testEveryMoveRuleKeepsIdsAndTheTargetRowOnConflict(): void
    {
        foreach ($this->rules('getMoveKeepingTargetRules') as $rule) {
            $t = $rule['table'];
            $f = $rule['userField'];
            $first = $rule['matchColumns'][0];
            $keep = $this->rawUser('keep', 5, 1, null);
            $merge = $this->rawUser('merge', 5, 1, null);
            $pk = (string) $this->conn()->fetchOne("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_key = 'PRI' AND extra LIKE '%auto_increment%'", [$t]);
            $this->assertNotSame('', $pk, $t);

            $zeros = array_fill_keys($rule['matchColumns'], 0);
            $this->insert($t, [$f => $keep] + $zeros);
            $this->insert($t, [$f => $merge] + $zeros);                // conflicts with the target's row
            $this->insert($t, [$f => $merge, $first => 77] + $zeros);  // free: moves and keeps its id
            $free = (int) $this->conn()->fetchOne("SELECT `{$pk}` FROM `{$t}` WHERE `{$f}` = ? AND `{$first}` = 77", [$merge]);

            $this->assertTrue($this->helper->mergeUsers($keep, $merge), $t);

            $this->assertSame(2, $this->rows($t, $f, $keep), $t.' target rows (own + moved)');
            $this->assertSame($keep, (int) $this->conn()->fetchOne("SELECT `{$f}` FROM `{$t}` WHERE `{$pk}` = ?", [$free]), $t.' keeps the id of the moved row');
            $this->assertSame($rule['deleteConflicts'] ? 0 : 1, $this->rows($t, $f, $merge), $t.' conflicting source row');
        }
    }

    public function testSameLearningPathViewOfTheTargetWinsAndChildrenOfTheDroppedViewStay(): void
    {
        // PINNED DECISION: a conflicting c_lp_view stays on the merged account (not deleted) because
        // c_lp_item_view rows reference it; the target's progress is what the learner keeps.
        $keep = $this->rawUser('keep', 5, 1, null);
        $merge = $this->rawUser('merge', 5, 1, null);
        $this->insert('c_lp_view', ['user_id' => $keep, 'lp_id' => 5, 'c_id' => 1, 'session_id' => null, 'progress' => 80]);
        $this->insert('c_lp_view', ['user_id' => $merge, 'lp_id' => 5, 'c_id' => 1, 'session_id' => null, 'progress' => 10]);
        $this->insert('c_lp_view', ['user_id' => $merge, 'lp_id' => 6, 'c_id' => 1, 'session_id' => null, 'progress' => 55]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame([[5, 80], [6, 55]], array_map(
            static fn (array $r): array => [(int) $r['lp_id'], (int) $r['progress']],
            $this->conn()->fetchAllAssociative('SELECT lp_id, progress FROM c_lp_view WHERE user_id = ? ORDER BY lp_id', [$keep])
        ), 'NULL session ids must match each other');
        $this->assertSame([10], array_map('intval', $this->conn()->fetchFirstColumn('SELECT progress FROM c_lp_view WHERE user_id = ?', [$merge])));
    }
}
