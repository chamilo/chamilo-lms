<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserMergeHelper;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Characterization of the account merge (admin "unify duplicate users" action and Statistics::unifyUsers()).
 * The helper has NO authorization of its own: whoever reaches it merges and soft-deletes accounts, so the admin gate
 * lives in the callers (AdminStatisticsMaintenanceActionService, ROLE_ADMIN API resource). Users are raw rows
 * (the trait names them "rp_", the extra fields and tags are "sv2_"); the hard-delete branch goes through the
 * resource-node repository, which deadlocks against concurrent suites, so it is only covered through its guards.
 * Every statement runs inside the DAMA transaction (the helper's own begin/commit become savepoints).
 */
final class UserMergeHelperTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private UserMergeHelper $helper;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->helper = self::getContainer()->get(UserMergeHelper::class);
    }

    private function scalar(string $sql, array $params = []): mixed
    {
        return $this->conn()->fetchOne($sql, $params);
    }

    private function courseIds(int $userId): array
    {
        $ids = array_map('intval', $this->conn()->fetchFirstColumn('SELECT c_id FROM course_rel_user WHERE user_id = ? ORDER BY c_id', [$userId]));

        return $ids;
    }

    private function extraField(string $variable, int $itemType = 1): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, visible_to_others, changeable, filter, created_at, auto_remove)
             VALUES (?, 1, ?, 'x', 0, 0, 0, 0, '2020-01-01 00:00:00', 0)",
            [$itemType, 'sv2_'.$variable.'_'.getmypid().'_'.(++$this->rpSeq)]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function extraValue(int $fieldId, int $itemId, string $value): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at) VALUES (?, ?, ?, '2020-01-01 00:00:00', '2020-01-01 00:00:00')",
            [$fieldId, $value, $itemId]
        );
    }

    private function extraValues(int $itemId, int $fieldId): array
    {
        return $this->conn()->fetchFirstColumn('SELECT field_value FROM extra_field_values WHERE item_id = ? AND field_id = ? ORDER BY id', [$itemId, $fieldId]);
    }

    private function login(int $userId, string $date): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, ?, '', ?)",
            [$userId, $date, $date]
        );
    }

    public function testInvalidParametersMergeNothing(): void
    {
        $keep = $this->rawUser('keep');

        $this->assertFalse($this->helper->mergeUsers(0, $keep));
        $this->assertFalse($this->helper->mergeUsers($keep, 0));
        $this->assertFalse($this->helper->mergeUsers(-5, $keep));
        $this->assertFalse($this->helper->mergeUsers($keep, $keep), 'A user cannot be merged into itself');
        $this->assertSame(1, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$keep]));
    }

    public function testUnknownUsersMergeNothing(): void
    {
        $keep = $this->rawUser('keep');
        $this->assertFalse($this->helper->mergeUsers($keep, 999999990));
        $this->assertFalse($this->helper->mergeUsers(999999990, $keep));
        $this->assertSame(1, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$keep]));
    }

    public function testMergeSoftDeletesTheSourceAndRenamesItWithoutTouchingTheTarget(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $keepBefore = $this->conn()->fetchAssociative('SELECT username, email, active, roles FROM user WHERE id = ?', [$keep]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $row = $this->conn()->fetchAssociative('SELECT username, email, active FROM user WHERE id = ?', [$merge]);
        $this->assertSame(User::SOFT_DELETED, (int) $row['active']);
        $this->assertSame('merged_'.$merge.'_into_'.$keep, $row['username']);
        $this->assertSame('merged_'.$merge.'_into_'.$keep.'@invalid.local', $row['email']);
        $this->assertSame($keepBefore, $this->conn()->fetchAssociative('SELECT username, email, active, roles FROM user WHERE id = ?', [$keep]));
    }

    public function testMergeNeverTransfersTheRolesOfTheSourceToTheTarget(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $this->conn()->executeStatement('UPDATE user SET roles = ? WHERE id = ?', ['["ROLE_ADMIN"]', $merge]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame('[]', $this->scalar('SELECT roles FROM user WHERE id = ?', [$keep]));
        // PINNED QUIRK: the merged account keeps its roles and password, only active=-2 disables it.
        $this->assertSame('["ROLE_ADMIN"]', $this->scalar('SELECT roles FROM user WHERE id = ?', [$merge]));
    }

    public function testMergeDoesNotCheckWhoIsCalling(): void
    {
        // PINNED QUIRK (security): no role check and no "target must be the more privileged account" rule, an
        // administrator can be merged away into a student. The callers are the only gate.
        $student = $this->rawUser('student');
        $admin = $this->rawUser('admin');
        $this->conn()->executeStatement('UPDATE user SET roles = ? WHERE id = ?', ['["ROLE_ADMIN"]', $admin]);

        $this->assertTrue($this->helper->mergeUsers($student, $admin));
        $this->assertSame(User::SOFT_DELETED, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$admin]));
    }

    public function testCourseSubscriptionsAreUnionedWithoutDuplicates(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $this->courseUser($keep, 1, 5);
        $this->courseUser($merge, 1, 1);
        $this->courseUser($merge, 2, 1);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame([1, 2], $this->courseIds($keep));
        $this->assertSame([], $this->courseIds($merge));
        // The status already held by the target on a shared course wins.
        $this->assertSame(5, (int) $this->scalar('SELECT status FROM course_rel_user WHERE user_id = ? AND c_id = 1', [$keep]));
        $this->assertSame(1, (int) $this->scalar('SELECT status FROM course_rel_user WHERE user_id = ? AND c_id = 2', [$keep]));
    }

    public function testSessionSubscriptionsMatchOnSessionAndRelationType(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $s1 = $this->rawSession('s1');
        $s2 = $this->rawSession('s2');
        $this->sessionUser($s1, $keep, 0);
        $this->sessionUser($s1, $merge, 0);  // same session and type: dropped
        $this->sessionUser($s1, $merge, 3);  // same session, other type: moved
        $this->sessionUser($s2, $merge, 0);  // other session: moved

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame(
            [[$s1, 0], [$s1, 3], [$s2, 0]],
            array_map(static fn (array $r): array => [(int) $r['session_id'], (int) $r['relation_type']], $this->conn()->fetchAllAssociative('SELECT session_id, relation_type FROM session_rel_user WHERE user_id = ? ORDER BY session_id, relation_type', [$keep]))
        );
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM session_rel_user WHERE user_id = ?', [$merge]));
    }

    public function testSessionCourseSubscriptionsMatchOnSessionAndCourse(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $s = $this->rawSession('s');
        $this->sessionCourseUser($s, 1, $keep);
        $this->sessionCourseUser($s, 1, $merge);
        $this->sessionCourseUser($s, 2, $merge);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame([1, 2], array_map('intval', $this->conn()->fetchFirstColumn('SELECT c_id FROM session_rel_course_rel_user WHERE user_id = ? AND session_id = ? ORDER BY c_id', [$keep, $s])));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM session_rel_course_rel_user WHERE user_id = ?', [$merge]));
    }

    public function testAccessUrlMembershipsAreUnioned(): void
    {
        // The merged account's URLs are added to the target: after the merge the target is reachable from both.
        $keep = $this->rawUser('keep', 5, 1, 1);
        $merge = $this->rawUser('merge', 5, 1, null);
        $other = $this->secondUrl();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$other, $merge]);
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (1, ?)', [$merge]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame([1, $other], array_map('intval', $this->conn()->fetchFirstColumn('SELECT access_url_id FROM access_url_rel_user WHERE user_id = ? ORDER BY access_url_id', [$keep])));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM access_url_rel_user WHERE user_id = ?', [$merge]));
    }

    public function testUsergroupMembershipsMatchOnGroupOnly(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $this->conn()->executeStatement("INSERT INTO usergroup (title, description, created_at, updated_at, group_type, visibility, allow_members_leave_group, author_id) VALUES (?, '', '2020-01-01 00:00:00', '2020-01-01 00:00:00', 0, 1, 0, 1)", ['sv2_g_'.getmypid().'_'.(++$this->rpSeq)]);
        $g1 = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO usergroup_rel_user (user_id, usergroup_id, relation_type) VALUES (?, ?, 1)', [$keep, $g1]);
        $this->conn()->executeStatement('INSERT INTO usergroup_rel_user (user_id, usergroup_id, relation_type) VALUES (?, ?, 2)', [$merge, $g1]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        // PINNED QUIRK: relation_type is not part of the match, the source's other-role membership is lost.
        $this->assertSame([1], array_map('intval', $this->conn()->fetchFirstColumn('SELECT relation_type FROM usergroup_rel_user WHERE user_id = ? AND usergroup_id = ?', [$keep, $g1])));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM usergroup_rel_user WHERE user_id = ?', [$merge]));
    }

    public function testUserRelationsMatchOnFriendAndType(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $friend = $this->rawUser('friend');
        $this->userRel($keep, $friend, 3);
        $this->userRel($merge, $friend, 3); // duplicate: dropped
        $this->userRel($merge, $friend, 1); // other type: moved

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame([1, 3], array_map('intval', $this->conn()->fetchFirstColumn('SELECT relation_type FROM user_rel_user WHERE user_id = ? AND friend_user_id = ? ORDER BY relation_type', [$keep, $friend])));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM user_rel_user WHERE user_id = ?', [$merge]));
    }

    public function testTrackingRowsAreReassignedToTheTarget(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $this->login($merge, '2001-01-01 10:00:00');
        $this->login($merge, '2001-01-02 10:00:00');
        $this->login($keep, '2001-01-03 10:00:00');

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame(3, (int) $this->scalar('SELECT COUNT(*) FROM track_e_login WHERE login_user_id = ?', [$keep]));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM track_e_login WHERE login_user_id = ?', [$merge]));
    }

    public function testRowsOfOtherUsersAreNotReassigned(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $bystander = $this->rawUser('bystander');
        $this->login($bystander, '2001-01-01 10:00:00');
        $this->courseUser($bystander, 1, 5);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame(1, (int) $this->scalar('SELECT COUNT(*) FROM track_e_login WHERE login_user_id = ?', [$bystander]));
        $this->assertSame([1], $this->courseIds($bystander));
    }

    public function testNotificationsAndMessageTagsOfTheSourceAreDeleted(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $this->conn()->executeStatement("INSERT INTO notification (dest_user_id, title, content, send_freq, created_at) VALUES (?, 'sv2', 'x', 1, '2020-01-01 00:00:00')", [$merge]);
        $this->conn()->executeStatement("INSERT INTO message_tag (user_id, tag, color, position, created_at, updated_at) VALUES (?, 'sv2', '#fff', 0, '2020-01-01 00:00:00', '2020-01-01 00:00:00')", [$merge]);
        $this->conn()->executeStatement("INSERT INTO message_tag (user_id, tag, color, position, created_at, updated_at) VALUES (?, 'sv2', '#fff', 0, '2020-01-01 00:00:00', '2020-01-01 00:00:00')", [$keep]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM notification WHERE dest_user_id IN (?, ?)', [$keep, $merge]), 'Deleted, not moved');
        $this->assertSame(1, (int) $this->scalar('SELECT COUNT(*) FROM message_tag WHERE user_id = ?', [$keep]));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM message_tag WHERE user_id = ?', [$merge]));
    }

    public function testExtraFieldValuesOfTheTargetWinAndMissingOnesAreCopied(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $shared = $this->extraField('shared');
        $onlyMerge = $this->extraField('onlymerge');
        $this->extraValue($shared, $keep, 'keep value');
        $this->extraValue($shared, $merge, 'merge value');
        $this->extraValue($onlyMerge, $merge, 'copied');

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame(['keep value'], $this->extraValues($keep, $shared));
        $this->assertSame(['copied'], $this->extraValues($keep, $onlyMerge));
        $this->assertSame([], $this->extraValues($merge, $shared));
        $this->assertSame([], $this->extraValues($merge, $onlyMerge));
    }

    public function testExtraFieldValuesOfOtherItemTypesAreNotMerged(): void
    {
        // A course extra field value whose item_id happens to equal the user id must stay where it is.
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $courseField = $this->extraField('coursefield', 2);
        $this->extraValue($courseField, $merge, 'course value');

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame(['course value'], $this->extraValues($merge, $courseField));
        $this->assertSame([], $this->extraValues($keep, $courseField));
    }

    public function testExtraFieldTagsAreCopiedWithoutDuplicates(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $field = $this->extraField('tags');
        $tags = [];
        foreach (['one', 'two'] as $label) {
            $this->conn()->executeStatement('INSERT INTO tag (field_id, tag, count) VALUES (?, ?, 1)', [$field, 'sv2_'.$label]);
            $tags[] = (int) $this->conn()->lastInsertId();
        }
        [$t1, $t2] = $tags;
        $this->conn()->executeStatement('INSERT INTO extra_field_rel_tag (field_id, tag_id, item_id) VALUES (?, ?, ?)', [$field, $t1, $keep]);
        $this->conn()->executeStatement('INSERT INTO extra_field_rel_tag (field_id, tag_id, item_id) VALUES (?, ?, ?)', [$field, $t1, $merge]);
        $this->conn()->executeStatement('INSERT INTO extra_field_rel_tag (field_id, tag_id, item_id) VALUES (?, ?, ?)', [$field, $t2, $merge]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame([$t1, $t2], array_map('intval', $this->conn()->fetchFirstColumn('SELECT tag_id FROM extra_field_rel_tag WHERE field_id = ? AND item_id = ? ORDER BY tag_id', [$field, $keep])));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM extra_field_rel_tag WHERE field_id = ? AND item_id = ?', [$field, $merge]));
    }

    public function testBatchMergesEverySourceAndReturnsTheCount(): void
    {
        $keep = $this->rawUser('keep');
        $a = $this->rawUser('a');
        $b = $this->rawUser('b');
        $this->courseUser($a, 1, 5);
        $this->courseUser($b, 2, 5);

        $count = $this->helper->mergeUsersBatch($keep, [$a, $b, $a, $keep, 0, -3]);

        $this->assertSame(2, $count, 'Duplicates, the target itself and non positive ids are ignored');
        $this->assertSame([1, 2], $this->courseIds($keep));
        $this->assertSame(User::SOFT_DELETED, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$a]));
        $this->assertSame(User::SOFT_DELETED, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$b]));
        $this->assertSame(1, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$keep]));
    }

    public function testBatchWithNothingToMergeReturnsZero(): void
    {
        $keep = $this->rawUser('keep');
        $this->assertSame(0, $this->helper->mergeUsersBatch($keep, []));
        $this->assertSame(0, $this->helper->mergeUsersBatch($keep, [$keep]));
        $this->assertSame(0, $this->helper->mergeUsersBatch(0, [$keep]));
        $this->assertSame(0, $this->helper->mergeUsersBatch(999999990, [$keep]), 'Unknown target');
        $this->assertSame(1, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$keep]));
    }

    public function testBatchRollsBackEverythingWhenOneSourceIsMissing(): void
    {
        $keep = $this->rawUser('keep');
        $a = $this->rawUser('a');
        $this->courseUser($a, 1, 5);

        try {
            $this->helper->mergeUsersBatch($keep, [$a, 999999990]);
            $this->fail('A missing source must abort the batch');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('user #999999990 not found', $e->getMessage());
        }

        $this->assertSame(1, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$a]), 'The first source was not merged');
        $this->assertSame([], $this->courseIds($keep));
        $this->assertSame([1], $this->courseIds($a));
    }
}
