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
 * The helper has no caller authorization (the admin gate lives in AdminStatisticsMaintenanceActionService and the
 * admin pages) but refuses by itself to lose an administrative role. Users are raw rows
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
        return array_map('intval', $this->conn()->fetchFirstColumn('SELECT c_id FROM course_rel_user WHERE user_id = ? ORDER BY c_id', [$userId]));
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
        $this->conn()->executeStatement('UPDATE user SET roles = ? WHERE id = ?', ['["ROLE_TEACHER"]', $merge]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame('[]', $this->scalar('SELECT roles FROM user WHERE id = ?', [$keep]));
        // PINNED QUIRK: the merged account keeps its roles and password, only active=-2 disables it (administrative
        // roles never get here: see testMergeNeverDropsAdministrativePrivilege).
        $this->assertSame('["ROLE_TEACHER"]', $this->scalar('SELECT roles FROM user WHERE id = ?', [$merge]));
    }

    private function setRoles(int $userId, array $roles): void
    {
        $this->conn()->executeStatement('UPDATE user SET roles = ? WHERE id = ?', [json_encode($roles), $userId]);
        $this->fresh();
    }

    /**
     * @dataProvider privilegeProvider
     */
    public function testMergeNeverDropsAdministrativePrivilege(array $sourceRoles, array $targetRoles, bool $allowed): void
    {
        // The helper has no caller check, so it refuses by itself to make an administrative account vanish into
        // an account that is not at least as privileged (the admin would be soft-deleted, its roles not transferred).
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $this->setRoles($keep, $targetRoles);
        $this->setRoles($merge, $sourceRoles);
        $this->courseUser($merge, 1, 1);

        $this->assertSame($allowed, $this->helper->mergeUsers($keep, $merge));

        $this->assertSame($allowed ? User::SOFT_DELETED : 1, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$merge]));
        $this->assertSame($allowed ? [1] : [], $this->courseIds($keep), 'A refused merge must not move anything');
        $this->assertSame($allowed ? [] : [1], $this->courseIds($merge));
    }

    public static function privilegeProvider(): iterable
    {
        // [source roles, target roles, merge allowed]
        yield 'admin into student' => [['ROLE_ADMIN'], [], false];

        yield 'global admin into student' => [['ROLE_GLOBAL_ADMIN'], [], false];

        yield 'global admin into admin' => [['ROLE_GLOBAL_ADMIN'], ['ROLE_ADMIN'], false];

        yield 'session manager into student' => [['ROLE_SESSION_MANAGER'], ['ROLE_STUDENT'], false];

        yield 'admin into teacher' => [['ROLE_ADMIN'], ['ROLE_TEACHER'], false];

        yield 'admin into admin' => [['ROLE_ADMIN'], ['ROLE_ADMIN'], true];

        yield 'admin into global admin' => [['ROLE_ADMIN'], ['ROLE_GLOBAL_ADMIN'], true];

        yield 'session manager into admin' => [['ROLE_SESSION_MANAGER'], ['ROLE_ADMIN'], true];

        yield 'session manager into session manager' => [['ROLE_SESSION_MANAGER'], ['ROLE_SESSION_MANAGER'], true];

        yield 'student into admin' => [[], ['ROLE_ADMIN'], true];

        yield 'student into student' => [[], [], true];

        yield 'teacher into student' => [['ROLE_TEACHER'], [], true];
    }

    public function testBatchRefusesAPrivilegedSourceAndRollsBackTheOthers(): void
    {
        $keep = $this->rawUser('keep');
        $plain = $this->rawUser('plain');
        $admin = $this->rawUser('admin');
        $this->courseUser($plain, 1, 5);
        $this->setRoles($admin, ['ROLE_ADMIN']);

        try {
            $this->helper->mergeUsersBatch($keep, [$plain, $admin]);
            $this->fail('A privileged source must abort the batch');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('#'.$admin, $e->getMessage());
        }

        $this->assertSame(1, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$plain]));
        $this->assertSame(1, (int) $this->scalar('SELECT active FROM user WHERE id = ?', [$admin]));
        $this->assertSame([], $this->courseIds($keep));
    }

    public function testBatchMergesAdminsIntoAnAdmin(): void
    {
        $keep = $this->rawUser('keep');
        $admin = $this->rawUser('admin');
        $this->setRoles($keep, ['ROLE_ADMIN']);
        $this->setRoles($admin, ['ROLE_ADMIN']);

        $this->assertSame(1, $this->helper->mergeUsersBatch($keep, [$admin]));
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

    public function testUsergroupMembershipsMatchOnGroupAndRelationType(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $this->conn()->executeStatement("INSERT INTO usergroup (title, description, created_at, updated_at, group_type, visibility, allow_members_leave_group, author_id) VALUES (?, '', '2020-01-01 00:00:00', '2020-01-01 00:00:00', 0, 1, 0, 1)", ['sv3_g_'.getmypid().'_'.(++$this->rpSeq)]);
        $g1 = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO usergroup_rel_user (user_id, usergroup_id, relation_type) VALUES (?, ?, 1)', [$keep, $g1]);
        $this->conn()->executeStatement('INSERT INTO usergroup_rel_user (user_id, usergroup_id, relation_type) VALUES (?, ?, 1)', [$merge, $g1]); // same: dropped
        $this->conn()->executeStatement('INSERT INTO usergroup_rel_user (user_id, usergroup_id, relation_type) VALUES (?, ?, 2)', [$merge, $g1]); // other role: kept

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame([1, 2], array_map('intval', $this->conn()->fetchFirstColumn('SELECT relation_type FROM usergroup_rel_user WHERE user_id = ? AND usergroup_id = ? ORDER BY relation_type', [$keep, $g1])));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM usergroup_rel_user WHERE user_id = ?', [$merge]));
    }

    public function testFriendSideOfUserRelationsIsRepointedToTheTarget(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $a = $this->rawUser('a');
        $b = $this->rawUser('b');
        $this->userRel($a, $merge, 3); // a -> merge: moves to a -> keep
        $this->userRel($b, $merge, 3); // b -> merge but b -> keep (same type) exists: dropped
        $this->userRel($b, $merge, 2); // moves: b -> keep has no type 2
        $this->userRel($b, $keep, 3);
        $this->userRel($b, $keep, 1); // same friend, other type than the merged relation: must not hide it
        $this->userRel($a, $merge, 1); // other type: moves

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame([1, 3], array_map('intval', $this->conn()->fetchFirstColumn('SELECT relation_type FROM user_rel_user WHERE user_id = ? AND friend_user_id = ? ORDER BY relation_type', [$a, $keep])));
        $this->assertSame([1, 2, 3], array_map('intval', $this->conn()->fetchFirstColumn('SELECT relation_type FROM user_rel_user WHERE user_id = ? AND friend_user_id = ? ORDER BY relation_type', [$b, $keep])));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM user_rel_user WHERE friend_user_id = ? OR user_id = ?', [$merge, $merge]), 'Nothing may dangle to the soft-deleted account');
    }

    public function testMergingTwoFriendsDoesNotLeaveASelfRelation(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $this->userRel($keep, $merge, 3);
        $this->userRel($merge, $keep, 3);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM user_rel_user WHERE user_id = friend_user_id AND user_id IN (?, ?)', [$keep, $merge]));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM user_rel_user WHERE user_id IN (?, ?) OR friend_user_id IN (?, ?)', [$keep, $merge, $keep, $merge]));
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

    private function gradebookCategory(): int
    {
        $this->conn()->executeStatement("INSERT INTO gradebook_category (title, weight, visible, locked, generate_certificates, is_requirement, calculation_mode, c_id) VALUES ('sv3_cat', 1, 1, 0, 0, 0, 'weighted', 1)");

        return (int) $this->conn()->lastInsertId();
    }

    private function gradebookEvaluation(int $categoryId): int
    {
        $this->conn()->executeStatement("INSERT INTO gradebook_evaluation (title, created_at, weight, max, visible, type, locked, category_id, c_id) VALUES ('sv3_eval', '2020-01-01 00:00:00', 1, 20, 1, 'evalcourse', 0, ?, 1)", [$categoryId]);

        return (int) $this->conn()->lastInsertId();
    }

    private function gradebookResult(int $evaluationId, int $userId, float $score): int
    {
        $this->conn()->executeStatement("INSERT INTO gradebook_result (evaluation_id, user_id, score, created_at) VALUES (?, ?, ?, '2020-01-01 00:00:00')", [$evaluationId, $userId, $score]);

        return (int) $this->conn()->lastInsertId();
    }

    public function testGradebookResultsMoveToTheTargetAndTheTargetResultWins(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $cat = $this->gradebookCategory();
        $shared = $this->gradebookEvaluation($cat);
        $onlyMerge = $this->gradebookEvaluation($cat);
        $this->gradebookResult($shared, $keep, 15.0);
        $this->gradebookResult($shared, $merge, 3.0);
        $moved = $this->gradebookResult($onlyMerge, $merge, 12.5);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame(
            [[$shared, 15.0], [$onlyMerge, 12.5]],
            array_map(static fn (array $r): array => [(int) $r['evaluation_id'], (float) $r['score']], $this->conn()->fetchAllAssociative('SELECT evaluation_id, score FROM gradebook_result WHERE user_id = ? ORDER BY evaluation_id', [$keep]))
        );
        $this->assertSame($keep, (int) $this->scalar('SELECT user_id FROM gradebook_result WHERE id = ?', [$moved]), 'The row keeps its id');
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM gradebook_result WHERE user_id = ?', [$merge]));
    }

    public function testGradebookScoreLogsAndCommentsAreReassigned(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $bystander = $this->rawUser('bystander');
        $cat = $this->gradebookCategory();
        foreach ([$merge, $bystander] as $u) {
            $this->conn()->executeStatement("INSERT INTO gradebook_score_log (category_id, user_id, score, registered_at) VALUES (?, ?, 7, '2020-01-01 00:00:00')", [$cat, $u]);
            $this->conn()->executeStatement("INSERT INTO gradebook_comment (user_id, gradebook_id, comment, created_at, updated_at) VALUES (?, ?, 'sv3', '2020-01-01 00:00:00', '2020-01-01 00:00:00')", [$u, $cat]);
        }

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        foreach (['gradebook_score_log', 'gradebook_comment'] as $table) {
            $this->assertSame(1, (int) $this->scalar("SELECT COUNT(*) FROM {$table} WHERE user_id = ?", [$keep]), $table);
            $this->assertSame(0, (int) $this->scalar("SELECT COUNT(*) FROM {$table} WHERE user_id = ?", [$merge]), $table);
            $this->assertSame(1, (int) $this->scalar("SELECT COUNT(*) FROM {$table} WHERE user_id = ?", [$bystander]), $table);
        }
    }

    public function testCompleteAccessTrackingIsReassigned(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $this->conn()->executeStatement("INSERT INTO track_e_access_complete (user_id, date_reg, tool, tool_id, tool_id_detail, action, action_details, current_id, ip_user, user_agent, session_id, c_id, ch_sid, login_as, info, url) VALUES (?, '2020-01-01 00:00:00', 'sv3', 0, 0, 'a', '', 0, '', '', 0, 0, '', 0, '', '')", [$merge]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame(1, (int) $this->scalar('SELECT COUNT(*) FROM track_e_access_complete WHERE user_id = ? AND tool = ?', [$keep, 'sv3']));
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM track_e_access_complete WHERE user_id = ?', [$merge]));
    }
}
