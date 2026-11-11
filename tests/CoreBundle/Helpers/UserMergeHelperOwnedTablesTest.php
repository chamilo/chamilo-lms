<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\UserMergeHelper;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\GenericRowInsertTrait;
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
    use GenericRowInsertTrait;
    use RawReportingFixturesTrait;

    private UserMergeHelper $helper;

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

        $n = 0;
        foreach ($rules as $rule) {
            $t = $rule['table'];
            $f = $rule['field'];
            if ('user' === $t) {
                continue; // user.creator_id needs whole user rows: covered by UserMergeHelperHardeningTest
            }
            // One row of the merged account and one of a bystander per rule, tagged by the column under test.
            // c_toolbox_version has a unique key on (toolbox_id, version_number) that does not involve the user.
            $this->insert($t, [$f => $merge] + ('c_toolbox_version' === $t ? ['version_number' => ++$n] : []));
            $this->insert($t, [$f => $bystander] + ('c_toolbox_version' === $t ? ['version_number' => ++$n] : []));
        }

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        // A table can carry several rules (two columns): count per column.
        foreach ($rules as $rule) {
            if ('user' === $rule['table']) {
                continue;
            }
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
            if (isset($zeros['target_date'])) {
                $zeros['target_date'] = '2020-01-01';
            }
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

    public function testTheRuleListsPinTheMatchColumnsThatDecideWhichRowWins(): void
    {
        // The generic tests above follow the helper's own lists; this one pins the decisions in them.
        $pivots = [];
        foreach ($this->rules('getPivotMergeRules') as $r) {
            $pivots[$r['table']] = $r['matchColumns'];
        }
        $this->assertSame(['access_url_id'], $pivots['access_url_rel_user']);
        $this->assertSame(['c_id'], $pivots['course_rel_user']);
        $this->assertSame(['session_id', 'relation_type'], $pivots['session_rel_user']);
        $this->assertSame(['usergroup_id', 'relation_type'], $pivots['usergroup_rel_user']);
        $this->assertSame(['friend_user_id', 'relation_type'], $pivots['user_rel_user']);
        foreach (['c_group_rel_user' => 'group_id', 'c_group_rel_tutor' => 'group_id', 'c_blog_rel_user' => 'blog_id', 'ticket_rel_user' => 'ticket_id', 'c_lp_user_access' => 'lp_id', 'user_rel_tag' => 'tag_id'] as $table => $col) {
            $this->assertSame([$col], $pivots[$table], $table);
        }

        $moves = [];
        foreach ($this->rules('getMoveKeepingTargetRules') as $r) {
            $moves[$r['table']] = $r['matchColumns'];
        }
        $this->assertSame(['evaluation_id'], $moves['gradebook_result']);
        $this->assertSame(['cat_id'], $moves['gradebook_certificate']);
        $this->assertSame(['lp_id', 'c_id', 'session_id'], $moves['c_lp_view']);
        $this->assertSame(['attendance_id'], $moves['c_attendance_result']);

        $updates = array_map(static fn (array $r): string => $r['table'].'.'.$r['field'], $this->rules('getUpdateRules'));
        foreach (['c_blog_post.author_id', 'c_notebook.user_id', 'c_student_publication.user_id', 'gradebook_result_log.user_id', 'ticket_ticket.assigned_last_user', 'course_invitation.created_by', 'message.user_sender_id', 'track_e_login.login_user_id'] as $entry) {
            $this->assertContains($entry, $updates);
        }
        foreach (['user_auth_source', 'oauth_access_token', 'reset_password_request', 'push_subscription', 'user_api_key', 'ai_tutor_conversation'] as $table) {
            $this->assertNotContains($table, array_map(static fn (string $e): string => explode('.', $e)[0], $updates), 'Credentials and private conversations are never transferred');
        }
    }
}
