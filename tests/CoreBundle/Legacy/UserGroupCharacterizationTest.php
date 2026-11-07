<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use UserGroupModel;

/**
 * Characterization tests for the raw-SQL methods of UserGroupModel (usergroup.lib.php).
 *
 * They pin what the legacy class returns TODAY (rows, ordering, role normalisation, side effects),
 * so that rewriting its SQL to DQL cannot silently change what the admin class pages, the course
 * "classes" tab and the social groups receive. Where the current behaviour looks wrong it is pinned
 * anyway and flagged "CURRENT BEHAVIOUR" in a comment: do not "fix" the test, decide on the source.
 *
 * Legacy constants: GROUP_USER_PERMISSION_READER = 0 (legacy define), while the entity constant for
 * a reader is 2; the library treats 0, 2 and '' as "reader".
 *
 * All data is prefixed "ug_" to stay clear of sibling tests sharing the database.
 */
final class UserGroupCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private UserGroupModel $model;
    private User $creator;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new HttpSession(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/admin/usergroups.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->creator = $this->createUser('ug_creator');
        $this->model = new UserGroupModel();
        self::assertFalse($this->model->getUseMultipleUrl(), 'These tests pin the single-URL branch.');
    }

    // ---------------------------------------------------------------- listing / searching groups

    public function testGetIdByNameReturnsTheIdOrZero(): void
    {
        $group = $this->group('ug_group_a');

        $this->assertSame($group->getId(), $this->model->getIdByName('ug_group_a'));
        // Zero (not false/null) is the "not found" contract callers test with empty().
        $this->assertSame(0, $this->model->getIdByName('ug_no_such_group'));
    }

    public function testUsergroupExistsMatchesTheTitle(): void
    {
        $this->group('ug_group_a');

        $this->assertTrue($this->model->usergroup_exists('ug_group_a'));
        $this->assertFalse($this->model->usergroup_exists('ug_group_zzz'));
        // The title column is case-insensitive in MySQL: a differently-cased title is a duplicate.
        $this->assertTrue($this->model->usergroup_exists('UG_GROUP_A'));
    }

    public function testGetAllUserGroupsIsOrderedByTitle(): void
    {
        $this->group('ug_group_c');
        $this->group('ug_group_a');
        $this->group('ug_group_b');

        $mine = array_values(array_filter(
            $this->model->getAllUserGroups(),
            static fn (array $row): bool => str_starts_with($row['title'], 'ug_group_')
        ));

        $this->assertSame(['ug_group_a', 'ug_group_b', 'ug_group_c'], array_column($mine, 'title'));
        $this->assertSame(['id', 'title'], array_keys($mine[0]));
    }

    public function testGetTotalCountGrowsWithEachGroup(): void
    {
        $before = (int) $this->model->getTotalCount();
        $this->group('ug_group_a');
        $this->group('ug_group_b');

        $this->assertSame($before + 2, (int) $this->model->getTotalCount());
    }

    public function testGetCountAppliesTheExtraConditionAndCountsAllTypes(): void
    {
        $this->group('ug_count_class', Usergroup::NORMAL_CLASS);
        $this->group('ug_count_social', Usergroup::SOCIAL_CLASS);
        $this->group('ug_other', Usergroup::NORMAL_CLASS);

        $this->assertEquals(2, $this->model->get_count("title LIKE 'ug\\_count\\_%'"));
        $this->assertEquals(1, $this->model->get_count("title = 'ug_other'"));
        $this->assertEquals(0, $this->model->get_count("title = 'ug_never'"));
    }

    public function testGetCountWithoutExtraConditionCountsEveryGroup(): void
    {
        $before = (int) $this->model->get_count();
        $this->group('ug_count_a');
        $this->group('ug_count_b', Usergroup::SOCIAL_CLASS);

        // Fixed: the default '' used to leave a dangling "AND " in the query and throw.
        $this->assertSame($before + 2, (int) $this->model->get_count());
    }

    public function testFilterByFirstLetterListsGroupsStartingWithTheLetterInSingleUrlMode(): void
    {
        $this->group('ug_fl_b');
        $this->group('ug_fl_a');
        $this->group('ug_other');

        // Fixed: the single-URL branch used `$sql = " FROM ..."` and lost its SELECT, so it always threw.
        $rows = $this->model->filterByFirstLetter('ug_fl');

        $this->assertSame(['ug_fl_b', 'ug_fl_a'], array_column($rows, 'title'));
        $this->assertSame(['id', 'title'], array_keys($rows[0]));
        $this->assertCount(1, $this->model->filterByFirstLetter('ug_fl', 1));
    }

    public function testGetUserGroupNotInListExcludesTheListedIdsAndOrdersByTitle(): void
    {
        $b = $this->group('ug_nil_b');
        $a = $this->group('ug_nil_a');
        $c = $this->group('ug_nil_c');

        $rows = $this->model->getUserGroupNotInList([$b->getId()], 1);
        $titles = array_values(array_filter(
            array_column($rows, 'title'),
            static fn (string $t): bool => str_starts_with($t, 'ug_nil_')
        ));

        $this->assertSame(['ug_nil_a', 'ug_nil_c'], $titles);
        // Full group rows (g.*) come back, not just ids.
        $this->assertArrayHasKey('group_type', $rows[0]);

        $all = array_column($this->model->getUserGroupNotInList([], 1), 'id');
        $this->assertContains($b->getId(), $all);
        $this->assertContains($a->getId(), $all);
        $this->assertContains($c->getId(), $all);
    }

    public function testGetUsergroupsPaginationCountsRelationsAndSorts(): void
    {
        $course = $this->createCourse('ug_course_p');
        $sessionEntity = $this->createSession('ug_session_p');
        $alice = $this->createUser('ug_alice');
        $bob = $this->createUser('ug_bob');

        $g2 = $this->group('ug_pg_2');
        $g1 = $this->group('ug_pg_1');
        $this->addUser($g1, $alice);
        $this->addUser($g1, $bob);
        $this->addCourse($g1, $course);
        $this->addSession($g1, $sessionEntity);

        $where = "u.title LIKE 'ug\\_pg\\_%'";
        $rows = $this->model->getUsergroupsPagination('title', 'asc', 0, 10, $where);

        $this->assertCount(2, $rows);
        // Fixed: msort() sorts 'desc' as lowest-first, so the direction was applied inverted ('asc'
        // returned the highest title first); it is now honoured as asked.
        $this->assertSame(['ug_pg_1', 'ug_pg_2'], array_column($rows, 'title'));
        $byTitle = array_column($rows, null, 'title');
        $this->assertSame(1, $byTitle['ug_pg_1']['courses']);
        $this->assertSame(1, $byTitle['ug_pg_1']['sessions']);
        $this->assertSame(0, $byTitle['ug_pg_2']['courses']);
        // 'users' is an HTML link wrapping the member count.
        $this->assertStringContainsString('/admin/usergroup-users/'.$g1->getId(), $byTitle['ug_pg_1']['users']);
        $this->assertMatchesRegularExpression('#>2</a>#', $byTitle['ug_pg_1']['users']);

        $desc = $this->model->getUsergroupsPagination('title', 'desc', 0, 10, $where);
        $this->assertSame(['ug_pg_2', 'ug_pg_1'], array_column($desc, 'title'));

        // LIMIT $start, $limit is applied to the ASC title order before the PHP-side sort.
        $page = $this->model->getUsergroupsPagination('title', 'asc', 1, 1, $where);
        $this->assertSame(['ug_pg_2'], array_column($page, 'title'));
        $this->assertNotNull($g2->getId());
    }

    public function testGetDataToExportListsMembersUsernamesSkippingSoftDeleted(): void
    {
        $alice = $this->createUser('ug_alice');
        $bob = $this->createUser('ug_bob');
        $gone = $this->createUser('ug_gone');
        $group = $this->group('ug_export');
        $this->addUser($group, $alice);
        $this->addUser($group, $bob);
        $this->addUser($group, $gone);
        $gone->setActive(User::SOFT_DELETED);
        $this->getEntityManager()->flush();
        $empty = $this->group('ug_export_empty');

        $rows = [];
        foreach ($this->model->getDataToExport() as $row) {
            $rows[$row['title']] = $row;
        }

        $users = explode(',', (string) $rows['ug_export']['users']);
        sort($users);
        $this->assertSame(['ug_alice', 'ug_bob'], $users);
        $this->assertNull($rows['ug_export_empty']['users']);
        $this->assertSame('desc', $rows['ug_export']['description']);
        $this->assertSame($group->getId(), (int) $rows['ug_export']['id']);
        $this->assertNotNull($empty->getId());
    }

    // ---------------------------------------------------------------- user <-> group

    public function testGetUsersByUsergroupFiltersByRelationType(): void
    {
        $group = $this->group('ug_group_a', Usergroup::SOCIAL_CLASS);
        $u0 = $this->createUser('ug_u0');
        $u1 = $this->createUser('ug_u1');
        $u2 = $this->createUser('ug_u2');
        $u5 = $this->createUser('ug_u5');
        $this->addUser($group, $u0, 0);
        $this->addUser($group, $u1, 1);
        $this->addUser($group, $u2, 2);
        $this->addUser($group, $u5, 5);

        $ids = fn (array $roles) => $this->sorted($this->model->get_users_by_usergroup($group->getId(), $roles));

        $this->assertSame($this->sorted([$u0->getId(), $u1->getId(), $u2->getId(), $u5->getId()]), $ids([]));
        $this->assertSame([$u1->getId()], $ids([1]));
        // Role 0 means "reader" and also pulls in the legacy reader value 2.
        $this->assertSame($this->sorted([$u0->getId(), $u2->getId()]), $ids([0]));
        $this->assertSame($this->sorted([$u0->getId(), $u2->getId(), $u5->getId()]), $ids([0, 5]));
        // Without an id the condition is dropped: every row of the table comes back.
        $this->assertContains($u5->getId(), $this->model->get_users_by_usergroup());
        $this->assertSame([], $this->model->get_users_by_usergroup($this->group('ug_empty')->getId()));
    }

    public function testGetUsersByUsergroupAndRelationTreatsReaderAsZeroOrTwo(): void
    {
        $group = $this->group('ug_group_a', Usergroup::SOCIAL_CLASS);
        $u0 = $this->createUser('ug_u0');
        $u2 = $this->createUser('ug_u2');
        $u1 = $this->createUser('ug_u1');
        $this->addUser($group, $u0, 0);
        $this->addUser($group, $u2, 2);
        $this->addUser($group, $u1, 1);

        $this->assertSame(
            $this->sorted([$u0->getId(), $u2->getId()]),
            $this->sorted($this->model->getUsersByUsergroupAndRelation($group->getId()))
        );
        $this->assertSame([$u1->getId()], $this->model->getUsersByUsergroupAndRelation($group->getId(), 1));
        $this->assertSame([], $this->model->getUsersByUsergroupAndRelation($group->getId(), 5));
    }

    public function testGetUserGroupListByUserReturnsGroupRowsAndFiltersByType(): void
    {
        $alice = $this->createUser('ug_alice');
        $class = $this->group('ug_class', Usergroup::NORMAL_CLASS);
        $social = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $other = $this->group('ug_other');
        $this->addUser($class, $alice);
        $this->addUser($social, $alice);

        $all = $this->model->getUserGroupListByUser($alice->getId());
        $this->assertEqualsCanonicalizing(['ug_class', 'ug_social'], array_column($all, 'title'));
        $this->assertArrayHasKey('group_type', $all[0]);

        $classes = $this->model->getUserGroupListByUser($alice->getId(), Usergroup::NORMAL_CLASS);
        $this->assertSame(['ug_class'], array_column($classes, 'title'));
        $this->assertSame(['ug_social'], $this->model->getNameListByUser($alice->getId(), Usergroup::SOCIAL_CLASS));
        $this->assertSame([], $this->model->getUserGroupListByUser($this->createUser('ug_nobody')->getId()));
        $this->assertNotNull($other->getId());
    }

    public function testGetUsergroupByUserReturnsGroupIds(): void
    {
        $alice = $this->createUser('ug_alice');
        $a = $this->group('ug_group_a');
        $b = $this->group('ug_group_b');
        $this->group('ug_group_c');
        $this->addUser($a, $alice);
        $this->addUser($b, $alice);

        $this->assertEqualsCanonicalizing(
            [$a->getId(), $b->getId()],
            array_map('intval', $this->model->get_usergroup_by_user($alice->getId()))
        );
    }

    public function testGetUserGroupUsersCountsAndLists(): void
    {
        $alice = $this->createUser('ug_alice');
        $bob = $this->createUser('ug_bob');
        $group = $this->group('ug_group_a');
        $this->addUser($group, $alice);
        $this->addUser($group, $bob);

        $this->assertEquals(2, $this->model->getUserGroupUsers($group->getId(), true));
        $this->assertEquals(0, $this->model->getUserGroupUsers($this->group('ug_empty')->getId(), true));

        $list = $this->model->getUserGroupUsers($group->getId());
        $this->assertCount(2, $list);
        $this->assertEqualsCanonicalizing([$alice->getId(), $bob->getId()], array_map('intval', array_column($list, 'id')));
        // Each row is the usergroup_rel_user row plus a display title that includes the username.
        $titles = implode('|', array_column($list, 'title'));
        $this->assertStringContainsString('ug_alice', $titles);
        $this->assertArrayHasKey('relation_type', $list[0]);

        // CURRENT BEHAVIOUR: the LIMIT is only applied when BOTH start and limit are non-zero,
        // so (start=0, limit=1) is NOT limited.
        $this->assertCount(2, $this->model->getUserGroupUsers($group->getId(), false, 0, 1));
        $this->assertCount(1, $this->model->getUserGroupUsers($group->getId(), false, 1, 1));
    }

    public function testGetUserListByUserGroupSkipsSoftDeletedAndHonoursOrderBy(): void
    {
        $zed = $this->createUser('ug_zed');
        $amy = $this->createUser('ug_amy');
        $gone = $this->createUser('ug_gone');
        $group = $this->group('ug_group_a');
        $this->addUser($group, $zed);
        $this->addUser($group, $amy);
        $this->addUser($group, $gone);
        $gone->setActive(User::SOFT_DELETED);
        $this->getEntityManager()->flush();

        $rows = $this->model->getUserListByUserGroup($group->getId(), 'username DESC');
        $this->assertSame(['ug_zed', 'ug_amy'], array_column($rows, 'username'));

        $rows = $this->model->getUserListByUserGroup($group->getId(), 'username ASC');
        $this->assertSame(['ug_amy', 'ug_zed'], array_column($rows, 'username'));
    }

    public function testGetUsersByGroupOrdersByRelationThenFirstnameAndSkipsSoftDeleted(): void
    {
        $group = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $admin = $this->createUser('ug_zadmin');
        $readerB = $this->createUser('ug_b_reader');
        $readerA = $this->createUser('ug_a_reader');
        $gone = $this->createUser('ug_gone');
        $this->addUser($group, $readerB, 2);
        $this->addUser($group, $admin, 1);
        $this->addUser($group, $readerA, 2);
        $this->addUser($group, $gone, 1);
        $gone->setActive(User::SOFT_DELETED);
        $this->getEntityManager()->flush();

        $rows = $this->model->get_users_by_group($group->getId());
        // Keyed by user id, ordered by relation_type then firstname.
        $this->assertSame([$admin->getId(), $readerA->getId(), $readerB->getId()], array_keys($rows));
        $this->assertSame('ug_zadmin ug_zadmin', $rows[$admin->getId()]['fullname']);
        $this->assertEquals($admin->getId(), $rows[$admin->getId()]['user_id']);

        $onlyReaders = $this->model->get_users_by_group($group->getId(), false, [2]);
        $this->assertSame([$readerA->getId(), $readerB->getId()], array_keys($onlyReaders));

        $paged = $this->model->get_users_by_group($group->getId(), false, [], 1, 1);
        $this->assertSame([$readerA->getId()], array_keys($paged));

        $this->assertSame([], $this->model->get_users_by_group(0));

        $all = $this->model->get_all_users_by_group($group->getId());
        $this->assertSame([$admin->getId(), $readerA->getId(), $readerB->getId()], array_keys($all));
        $this->assertSame(['id', 'firstname', 'lastname', 'relation_type'], array_keys($all[$admin->getId()]));
        $this->assertSame([], $this->model->get_all_users_by_group(0));
    }

    public function testGetGroupsByUserOnlyReturnsSocialGroupsKeyedById(): void
    {
        $alice = $this->createUser('ug_alice');
        $social = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $socialMod = $this->group('ug_social_mod', Usergroup::SOCIAL_CLASS);
        $class = $this->group('ug_class', Usergroup::NORMAL_CLASS);
        $this->addUser($social, $alice, 1);
        $this->addUser($socialMod, $alice, 5);
        $this->addUser($class, $alice, 0);

        // Relation 0 means "no relation filter" here (unlike get_users_by_usergroup).
        $all = $this->model->get_groups_by_user($alice->getId(), 0);
        $this->assertEqualsCanonicalizing([$social->getId(), $socialMod->getId()], array_keys($all));
        $this->assertSame(['picture', 'title', 'description', 'id', 'relation_type'], array_keys($all[$social->getId()]));

        $admins = $this->model->get_groups_by_user($alice->getId(), 1);
        $this->assertSame([$social->getId()], array_keys($admins));

        $several = $this->model->get_groups_by_user($alice->getId(), [1, 5]);
        $this->assertEqualsCanonicalizing([$social->getId(), $socialMod->getId()], array_keys($several));

        // The default relation (GROUP_USER_PERMISSION_READER = 0) therefore also lists everything.
        $this->assertCount(2, $this->model->get_groups_by_user($alice->getId()));
    }

    public function testGetGroupsByUserCountIgnoresGroupType(): void
    {
        $alice = $this->createUser('ug_alice');
        $this->addUser($this->group('ug_social', Usergroup::SOCIAL_CLASS), $alice, 1);
        $this->addUser($this->group('ug_class', Usergroup::NORMAL_CLASS), $alice, 1);
        $this->addUser($this->group('ug_class2', Usergroup::NORMAL_CLASS), $alice, 5);

        // CURRENT BEHAVIOUR: unlike get_groups_by_user(), the count does not filter on social groups.
        $this->assertEquals(3, $this->model->get_groups_by_user_count($alice->getId(), 0));
        $this->assertEquals(2, $this->model->get_groups_by_user_count($alice->getId(), 1));
        $this->assertEquals(1, $this->model->get_groups_by_user_count($alice->getId(), 5));
        $this->assertEquals(0, $this->model->get_groups_by_user_count($this->createUser('ug_nobody')->getId(), 0));
    }

    // ---------------------------------------------------------------- roles

    public function testGetUserGroupRoleNormalisesLegacyReaderAndDefaultsToZero(): void
    {
        $group = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $admin = $this->createUser('ug_admin');
        $legacyReader = $this->createUser('ug_legacy');
        $mod = $this->createUser('ug_mod');
        $stranger = $this->createUser('ug_stranger');
        $this->addUser($group, $admin, 1);
        $this->addUser($group, $legacyReader, 2);
        $this->addUser($group, $mod, 5);

        $this->assertSame(1, $this->model->get_user_group_role($admin->getId(), $group->getId()));
        // Stored 2 (legacy reader) is returned as GROUP_USER_PERMISSION_READER (0).
        $this->assertSame(0, $this->model->get_user_group_role($legacyReader->getId(), $group->getId()));
        $this->assertSame(5, $this->model->get_user_group_role($mod->getId(), $group->getId()));
        // CURRENT BEHAVIOUR: "no relation" and "reader" are indistinguishable (both 0).
        $this->assertSame(0, $this->model->get_user_group_role($stranger->getId(), $group->getId()));
        $this->assertSame(0, $this->model->get_user_group_role(0, $group->getId()));

        $this->assertTrue($this->model->is_group_admin($group->getId(), $admin->getId()));
        $this->assertFalse($this->model->is_group_admin($group->getId(), $mod->getId()));
        $this->assertTrue($this->model->isGroupModerator($group->getId(), $mod->getId()));
        $this->assertTrue($this->model->isGroupModerator($group->getId(), $admin->getId()));
        $this->assertFalse($this->model->isGroupModerator($group->getId(), $stranger->getId()));
        // Fixed: a user without any relation row is NOT a member (role 0 means both "reader" and
        // "no relation"; membership now requires the row), otherwise closed groups were readable.
        $this->assertFalse($this->model->is_group_member($group->getId(), $stranger->getId()));
        $this->assertFalse($this->model->is_group_member($group->getId(), 0));
        $this->assertTrue($this->model->is_group_member($group->getId(), $legacyReader->getId()));
        $this->assertTrue($this->model->is_group_member($group->getId(), $admin->getId()));
        $this->assertTrue($this->model->is_group_member($group->getId(), $mod->getId()));
    }

    public function testIsGroupMemberRequiresARelationAndRejectsPendingInvitations(): void
    {
        $group = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $other = $this->group('ug_social_other', Usergroup::SOCIAL_CLASS);
        $reader = $this->createUser('ug_reader');
        $hrm = $this->createUser('ug_hrm');
        $invited = $this->createUser('ug_invited');
        $requested = $this->createUser('ug_requested');
        $this->addUser($group, $reader, 0);
        $this->addUser($group, $hrm, 7);
        $this->addUser($group, $invited, 3);
        $this->addUser($group, $requested, 4);

        $this->assertTrue($this->model->is_group_member($group->getId(), $reader->getId()));
        $this->assertTrue($this->model->is_group_member($group->getId(), $hrm->getId()));
        $this->assertFalse($this->model->is_group_member($group->getId(), $invited->getId()));
        $this->assertFalse($this->model->is_group_member($group->getId(), $requested->getId()));
        // A member of one group is not a member of another.
        $this->assertFalse($this->model->is_group_member($other->getId(), $reader->getId()));
    }

    public function testCanInviteToGroupIsLimitedToGroupAdminsAndModerators(): void
    {
        $group = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $other = $this->group('ug_social_other', Usergroup::SOCIAL_CLASS);
        $admin = $this->createUser('ug_admin');
        $mod = $this->createUser('ug_mod');
        $reader = $this->createUser('ug_reader');
        $hrm = $this->createUser('ug_hrm');
        $invited = $this->createUser('ug_invited');
        $stranger = $this->createUser('ug_stranger');
        $this->addUser($group, $admin, 1);
        $this->addUser($group, $mod, 5);
        $this->addUser($group, $reader, 0);
        $this->addUser($group, $hrm, 7);
        $this->addUser($group, $invited, 3);

        $this->assertTrue($this->model->canInviteToGroup($group->getId(), $admin->getId()));
        $this->assertTrue($this->model->canInviteToGroup($group->getId(), $mod->getId()));
        // Plain members used to pass the page check (is_group_member) although only admins and
        // moderators are documented to invite.
        $this->assertFalse($this->model->canInviteToGroup($group->getId(), $reader->getId()));
        $this->assertFalse($this->model->canInviteToGroup($group->getId(), $hrm->getId()));
        $this->assertFalse($this->model->canInviteToGroup($group->getId(), $invited->getId()));
        $this->assertFalse($this->model->canInviteToGroup($group->getId(), $stranger->getId()));
        // Being admin of group A gives no right on group B.
        $this->assertFalse($this->model->canInviteToGroup($other->getId(), $admin->getId()));
    }

    public function testUpdateUserRoleAndDeleteUserRelGroup(): void
    {
        $group = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $alice = $this->createUser('ug_alice');
        $this->addUser($group, $alice, 2);

        $this->model->update_user_role($alice->getId(), $group->getId(), 5);
        $this->assertSame(5, $this->relationOf($group, $alice));

        $this->assertFalse($this->model->delete_user_rel_group(0, $group->getId()));
        $this->assertFalse($this->model->delete_user_rel_group($alice->getId(), 0));
        $this->model->delete_user_rel_group($alice->getId(), $group->getId());
        $this->assertNull($this->relationOf($group, $alice));
    }

    public function testAddUserToGroupNormalisesReaderForClassesAndUpgradesInvitations(): void
    {
        $class = $this->group('ug_class', Usergroup::NORMAL_CLASS);
        $social = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $alice = $this->createUser('ug_alice');
        $bob = $this->createUser('ug_bob');

        $this->assertFalse($this->model->add_user_to_group(0, $class->getId()));
        $this->assertFalse($this->model->add_user_to_group($alice->getId(), 0));

        // Class + reader -> canonical 0.
        $this->assertTrue($this->model->add_user_to_group($alice->getId(), $class->getId()));
        $this->assertSame(0, $this->relationOf($class, $alice));
        // Social + explicit moderator is stored as-is.
        $this->model->add_user_to_group($alice->getId(), $social->getId(), 5);
        $this->assertSame(5, $this->relationOf($social, $alice));

        // Adding again never duplicates the row nor overrides an existing real role.
        $this->model->add_user_to_group($alice->getId(), $social->getId(), 1);
        $this->assertSame(5, $this->relationOf($social, $alice));

        // A pending invitation (3) is upgraded to the requested role.
        $this->addUser($social, $bob, 3);
        $this->model->add_user_to_group($bob->getId(), $social->getId(), 1);
        $this->assertSame(1, $this->relationOf($social, $bob));
        $this->assertSame(1, $this->countRows('usergroup_rel_user', ['usergroup_id' => $social->getId(), 'user_id' => $bob->getId()]));
    }

    public function testAddUsersToGroupsReportsInsertedPairsOnly(): void
    {
        $g1 = $this->group('ug_group_a');
        $g2 = $this->group('ug_group_b');
        $alice = $this->createUser('ug_alice');
        $bob = $this->createUser('ug_bob');
        $this->addUser($g1, $alice, 5);

        $result = $this->model->add_users_to_groups([$alice->getId(), $bob->getId()], [$g1->getId(), $g2->getId()], 2);

        // alice already has a role (5) in g1: skipped.
        $this->assertSame([
            $g1->getId() => [$bob->getId() => 1],
            $g2->getId() => [$alice->getId() => 1, $bob->getId() => 1],
        ], $result);
        $this->assertSame(5, $this->relationOf($g1, $alice));
        $this->assertSame(2, $this->relationOf($g2, $bob));
        $this->assertSame([], $this->model->add_users_to_groups('x', [1]));
    }

    public function testGetUserRoleToStringAndGetRoleName(): void
    {
        $group = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $alice = $this->createUser('ug_alice');
        $this->addUser($group, $alice, 1);

        $this->assertSame(UserGroupModel::getRoleName(1), $this->model->getUserRoleToString($alice->getId(), $group->getId()));
        $this->assertSame(UserGroupModel::getRoleName(0), $this->model->getUserRoleToString($this->createUser('ug_x')->getId(), $group->getId()));
        $this->assertNotSame(UserGroupModel::getRoleName(1), UserGroupModel::getRoleName(5));
        $this->assertSame(UserGroupModel::getRoleName(42), UserGroupModel::getRoleName(6));
    }

    // ---------------------------------------------------------------- subscribe users

    public function testSubscribeUsersToUsergroupAddsAndRemovesMembersOfAClass(): void
    {
        $group = $this->group('ug_class', Usergroup::NORMAL_CLASS);
        $alice = $this->createUser('ug_alice');
        $bob = $this->createUser('ug_bob');
        $carl = $this->createUser('ug_carl');

        $this->model->subscribe_users_to_usergroup($group->getId(), [$alice->getId(), $bob->getId()]);
        $this->assertEqualsCanonicalizing(
            [$alice->getId(), $bob->getId()],
            array_map('intval', $this->model->get_users_by_usergroup($group->getId()))
        );
        $this->assertSame(0, $this->relationOf($group, $alice));

        // Replace the list: bob leaves, carl joins, alice untouched.
        $this->model->subscribe_users_to_usergroup($group->getId(), [$alice->getId(), $carl->getId()]);
        $this->assertEqualsCanonicalizing(
            [$alice->getId(), $carl->getId()],
            array_map('intval', $this->model->get_users_by_usergroup($group->getId()))
        );

        // With $delete_users_not_present_in_list = false nobody is removed.
        $this->model->subscribe_users_to_usergroup($group->getId(), [$bob->getId()], false);
        $this->assertCount(3, $this->model->get_users_by_usergroup($group->getId()));
    }

    public function testSubscribeUsersToUsergroupOfASocialGroupIsScopedPerRole(): void
    {
        $group = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $alice = $this->createUser('ug_alice');
        $bob = $this->createUser('ug_bob');
        $this->addUser($group, $alice, 1);
        $this->addUser($group, $bob, 5);

        // Replacing the moderators must not touch the admin row.
        $this->model->subscribe_users_to_usergroup($group->getId(), [], true, 5);

        $this->assertSame(1, $this->relationOf($group, $alice));
        $this->assertNull($this->relationOf($group, $bob));
    }

    public function testSubscribeUsersToClassCascadesToItsCourses(): void
    {
        $course = $this->createCourse('ug_course_u');
        $group = $this->group('ug_class');
        $alice = $this->createUser('ug_alice');
        $this->addCourse($group, $course);

        $this->model->subscribe_users_to_usergroup($group->getId(), [$alice->getId()]);
        $this->assertSame(1, $this->countRows('course_rel_user', ['c_id' => $course->getId(), 'user_id' => $alice->getId()]));

        $this->model->subscribe_users_to_usergroup($group->getId(), []);
        $this->assertSame(0, $this->countRows('course_rel_user', ['c_id' => $course->getId(), 'user_id' => $alice->getId()]));
        $this->assertSame(0, $this->countRows('usergroup_rel_user', ['usergroup_id' => $group->getId()]));
    }

    // ---------------------------------------------------------------- course <-> group

    public function testCourseRelationReaders(): void
    {
        $c1 = $this->createCourse('ug_course_1');
        $c2 = $this->createCourse('ug_course_2');
        $g1 = $this->group('ug_group_a');
        $g2 = $this->group('ug_group_b');
        $this->addCourse($g1, $c1);
        $this->addCourse($g1, $c2);
        $this->addCourse($g2, $c1);

        $this->assertEqualsCanonicalizing(
            [$c1->getId(), $c2->getId()],
            array_map('intval', $this->model->get_courses_by_usergroup($g1->getId()))
        );
        $this->assertSame([], $this->model->get_courses_by_usergroup($this->group('ug_empty')->getId()));

        $withData = $this->model->get_courses_by_usergroup($g1->getId(), true);
        $this->assertEqualsCanonicalizing([$c1->getId(), $c2->getId()], array_keys($withData));
        $this->assertSame('ug_course_1', $withData[$c1->getId()]['title']);

        $this->assertEqualsCanonicalizing(
            [$g1->getId(), $g2->getId()],
            array_map('intval', $this->model->get_usergroup_by_course($c1->getId()))
        );
        $this->assertEquals(2, $this->model->getUserGroupByCourseWithDataCount($c1->getId()));
        $this->assertEquals(1, $this->model->getUserGroupByCourseWithDataCount($c2->getId()));
    }

    public function testGetUserGroupByCourseWithDataCountFiltersByGroupType(): void
    {
        $course = $this->createCourse('ug_course_t');
        $class = $this->group('ug_class', Usergroup::NORMAL_CLASS);
        $social = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $this->addCourse($class, $course);
        $this->addCourse($social, $course);

        $this->assertEquals(2, $this->model->getUserGroupByCourseWithDataCount($course->getId(), -1));
        $this->assertEquals(1, $this->model->getUserGroupByCourseWithDataCount($course->getId(), 0));
        $this->assertEquals(1, $this->model->getUserGroupByCourseWithDataCount($course->getId(), 1));
    }

    public function testUsergroupWasAddedInCourseChecksCourseOrSession(): void
    {
        $course = $this->createCourse('ug_course_w');
        $sessionEntity = $this->createSession('ug_session_w');
        $group = $this->group('ug_group_a');
        $this->addCourse($group, $course);

        $this->assertTrue($this->model->usergroup_was_added_in_course($group->getId(), $course->getId()));
        $this->assertFalse($this->model->usergroup_was_added_in_course($group->getId(), $course->getId() + 100000));
        // With a session id, ONLY the session relation matters (course relation is ignored).
        $this->assertFalse($this->model->usergroup_was_added_in_course($group->getId(), $course->getId(), $sessionEntity->getId()));
        $this->addSession($group, $sessionEntity);
        $this->assertTrue($this->model->usergroup_was_added_in_course($group->getId(), 0, $sessionEntity->getId()));
    }

    public function testGetUserGroupInCourseListsAndCountsWithWhereOptions(): void
    {
        $course = $this->createCourse('ug_course_i');
        $class = $this->group('ug_class', Usergroup::NORMAL_CLASS);
        $social = $this->group('ug_social', Usergroup::SOCIAL_CLASS);
        $this->group('ug_not_in');
        $this->addCourse($class, $course);
        $this->addCourse($social, $course);
        $options = ['where' => [' usergroup.course_id = ? ' => $course->getId()]];

        $rows = $this->model->getUserGroupInCourse($options);
        $this->assertEqualsCanonicalizing(['ug_class', 'ug_social'], array_column($rows, 'title'));
        $this->assertArrayHasKey('group_type', $rows[0]);

        $this->assertEquals(2, $this->model->getUserGroupInCourse($options, -1, true));
        $this->assertSame(['ug_class'], array_column($this->model->getUserGroupInCourse($options, 0), 'title'));
        $this->assertSame(['ug_social'], array_column($this->model->getUserGroupInCourse($options, 1), 'title'));
        $this->assertEquals(1, $this->model->getUserGroupInCourse($options, 1, true));
    }

    public function testGetUserGroupInCourseWithClassesUsesSessionRelations(): void
    {
        $course = $this->createCourse('ug_course_s');
        $sessionEntity = $this->createSession('ug_session_s');
        $group = $this->group('ug_class');
        $this->addSession($group, $sessionEntity);
        $this->getEntityManager()->getConnection()->insert('session_rel_course', [
            'session_id' => $sessionEntity->getId(),
            'c_id' => $course->getId(),
            'nbr_users' => 0,
            'position' => 0,
        ]);

        $options = ['course_id' => $course->getId(), 'session_id' => $sessionEntity->getId()];
        $rows = $this->model->getUserGroupInCourse($options, -1, false, true);
        $this->assertSame(['ug_class'], array_column($rows, 'title'));
        $this->assertEquals(1, $this->model->getUserGroupInCourse($options, -1, true, true));

        // Without a session id, withClasses silently falls back to the course relation query.
        $this->assertSame([], $this->model->getUserGroupInCourse(
            ['where' => [' usergroup.course_id = ? ' => $course->getId()]],
            -1,
            false,
            true
        ));
    }

    public function testGetUserGroupNotInCourseReturnsGroupsWithoutTheCourseRelation(): void
    {
        $course = $this->createCourse('ug_course_n');
        $in = $this->group('ug_in');
        $out = $this->group('ug_out');
        $this->addCourse($in, $course);

        // CURRENT BEHAVIOUR: without course_id the method returns false (not []).
        $this->assertFalse($this->model->getUserGroupNotInCourse([]));

        $options = [
            'course_id' => $course->getId(),
            'where' => [' (course_id IS NULL OR course_id != ?) ' => $course->getId()],
        ];
        $titles = array_column($this->model->getUserGroupNotInCourse($options), 'title');
        $this->assertContains('ug_out', $titles);
        $this->assertNotContains('ug_in', $titles);
        $this->assertNotNull($out->getId());

        $count = $this->model->getUserGroupNotInCourse($options, -1, true);
        $this->assertGreaterThanOrEqual(1, (int) $count);
    }

    public function testSubscribeCoursesToUsergroupSubscribesMembersAndRemovesDroppedCourses(): void
    {
        $c1 = $this->createCourse('ug_course_1');
        $c2 = $this->createCourse('ug_course_2');
        $group = $this->group('ug_class');
        $alice = $this->createUser('ug_alice');
        $this->addUser($group, $alice, 0);

        $this->model->subscribe_courses_to_usergroup($group->getId(), [$c1->getId()]);
        $this->assertSame([$c1->getId()], array_map('intval', $this->model->get_courses_by_usergroup($group->getId())));
        $this->assertSame(1, $this->countRows('course_rel_user', ['c_id' => $c1->getId(), 'user_id' => $alice->getId()]));

        // Replace: c1 dropped (members leave it too), c2 added.
        $this->model->subscribe_courses_to_usergroup($group->getId(), [$c2->getId()]);
        $this->assertSame([$c2->getId()], array_map('intval', $this->model->get_courses_by_usergroup($group->getId())));
        $this->assertSame(0, $this->countRows('course_rel_user', ['c_id' => $c1->getId(), 'user_id' => $alice->getId()]));
        $this->assertSame(1, $this->countRows('course_rel_user', ['c_id' => $c2->getId(), 'user_id' => $alice->getId()]));

        // $delete_groups=false keeps the dropped course relation.
        $this->model->subscribe_courses_to_usergroup($group->getId(), [], false);
        $this->assertCount(1, $this->model->get_courses_by_usergroup($group->getId()));
    }

    public function testUnsubscribeCoursesKeepsUsersThatBelongToAnotherClassOfTheCourse(): void
    {
        $course = $this->createCourse('ug_course_k');
        $g1 = $this->group('ug_group_a');
        $g2 = $this->group('ug_group_b');
        $shared = $this->createUser('ug_shared');
        $solo = $this->createUser('ug_solo');
        $this->addUser($g1, $shared, 0);
        $this->addUser($g1, $solo, 0);
        $this->addUser($g2, $shared, 0);
        $this->addCourse($g1, $course);
        $this->addCourse($g2, $course);
        \CourseManager::subscribeUser($shared->getId(), $course->getId());
        \CourseManager::subscribeUser($solo->getId(), $course->getId());
        $this->assertSame(1, $this->countRows('course_rel_user', ['c_id' => $course->getId(), 'user_id' => $solo->getId()]));

        $this->model->unsubscribe_courses_from_usergroup($g1->getId(), [$course->getId()]);

        $this->assertSame([], $this->model->get_courses_by_usergroup($g1->getId()));
        $this->assertSame(0, $this->countRows('course_rel_user', ['c_id' => $course->getId(), 'user_id' => $solo->getId()]));
        $this->assertSame(1, $this->countRows('course_rel_user', ['c_id' => $course->getId(), 'user_id' => $shared->getId()]));
        $this->assertCount(1, $this->model->get_courses_by_usergroup($g2->getId()));
    }

    public function testUnsubscribeOnlyCoursesRemovesTheRelationButNotTheUsers(): void
    {
        $course = $this->createCourse('ug_course_o');
        $group = $this->group('ug_group_a');
        $alice = $this->createUser('ug_alice');
        $this->addUser($group, $alice, 0);
        $this->model->subscribe_courses_to_usergroup($group->getId(), [$course->getId()]);

        $this->model->unsubscribeOnlyCoursesFromUsergroup($group->getId(), [$course->getId(), 999999999]);

        $this->assertSame([], $this->model->get_courses_by_usergroup($group->getId()));
        $this->assertSame(1, $this->countRows('course_rel_user', ['c_id' => $course->getId(), 'user_id' => $alice->getId()]));
    }

    // ---------------------------------------------------------------- session <-> group

    public function testSubscribeSessionsToUsergroupAddsAndReplaces(): void
    {
        $s1 = $this->createSession('ug_session_1');
        $s2 = $this->createSession('ug_session_2');
        $group = $this->group('ug_group_a');

        $this->model->subscribe_sessions_to_usergroup($group->getId(), [$s1->getId()]);
        $this->assertSame([$s1->getId()], array_map('intval', $this->model->get_sessions_by_usergroup($group->getId())));

        $this->model->subscribe_sessions_to_usergroup($group->getId(), [$s2->getId()]);
        $this->assertSame([$s2->getId()], array_map('intval', $this->model->get_sessions_by_usergroup($group->getId())));

        $this->model->subscribe_sessions_to_usergroup($group->getId(), [], false);
        $this->assertCount(1, $this->model->get_sessions_by_usergroup($group->getId()));
        $this->assertSame([], $this->model->get_sessions_by_usergroup($this->group('ug_empty')->getId()));
    }

    // ---------------------------------------------------------------- group hierarchy

    public function testParentGroupHierarchy(): void
    {
        $root = $this->group('ug_root');
        $mid = $this->group('ug_mid');
        $leaf = $this->group('ug_leaf');

        $this->model->setParentGroup($mid->getId(), $root->getId());
        $this->model->setParentGroup($leaf->getId(), $mid->getId());

        // Fixed: fetch_array() ignores its 'NUM' mode and returned each id twice (assoc + numeric).
        $this->assertSame([$root->getId()], UserGroupModel::get_parent_groups($mid->getId()));
        $this->assertSame(
            [$mid->getId(), $root->getId()],
            UserGroupModel::get_parent_groups($leaf->getId())
        );
        $this->assertSame([], UserGroupModel::get_parent_groups($root->getId()));

        $this->assertEqualsCanonicalizing([$mid->getId(), $leaf->getId()], UserGroupModel::getGroupsByDepthLevel($root->getId()));
        $this->assertSame([$mid->getId()], UserGroupModel::getGroupsByDepthLevel($root->getId(), 1));

        // Re-parenting updates the single row instead of inserting a second one.
        $this->model->setParentGroup($leaf->getId(), $root->getId());
        $this->assertSame(1, $this->countRows('usergroup_rel_usergroup', ['subgroup_id' => $leaf->getId()]));
        $this->assertSame([$root->getId()], UserGroupModel::get_parent_groups($leaf->getId()));

        // Parent 0 deletes the association.
        $this->model->setParentGroup($leaf->getId(), 0);
        $this->assertSame([], UserGroupModel::get_parent_groups($leaf->getId()));
    }

    public function testGetGroupUsersByUserIncludesSubgroupMembersButNotTheUser(): void
    {
        $boss = $this->createUser('ug_boss');
        $alice = $this->createUser('ug_alice');
        $bob = $this->createUser('ug_bob');
        $parent = $this->group('ug_parent', Usergroup::SOCIAL_CLASS);
        $child = $this->group('ug_child', Usergroup::SOCIAL_CLASS);
        $this->addUser($parent, $boss, 1);
        $this->addUser($parent, $alice, 2);
        $this->addUser($child, $bob, 2);
        $this->model->setParentGroup($child->getId(), $parent->getId());

        $with = $this->model->getGroupUsersByUser($boss->getId(), 1);
        $this->assertEqualsCanonicalizing([$alice->getId(), $bob->getId()], array_values($with));
        $without = $this->model->getGroupUsersByUser($boss->getId(), 1, false);
        $this->assertSame([$alice->getId()], array_values($without));
        $this->assertSame([], $this->model->getGroupUsersByUser($alice->getId(), 1));
    }

    // ---------------------------------------------------------------- summaries / lifecycle

    public function testGetUsersAndCoursesSubscribedToAUserGroupWarnings(): void
    {
        $course = $this->createCourse('ug_course_m');
        $alice = $this->createUser('ug_alice');
        $empty = $this->group('ug_empty');
        $onlyUsers = $this->group('ug_only_users');
        $onlyCourses = $this->group('ug_only_courses');
        $this->addUser($onlyUsers, $alice, 0);
        $this->addCourse($onlyCourses, $course);

        $none = $this->model->getUsersAndCoursesSubscribedToAUserGroup($empty->getId());
        $this->assertSame(get_lang('No user and no course are subscribed to this class'), $none['warning']);

        $users = $this->model->getUsersAndCoursesSubscribedToAUserGroup($onlyUsers->getId());
        $this->assertSame(get_lang('No course are subscribed to this class'), $users['warning']);
        $this->assertCount(1, $users['usersSubscribedToUsergroup']);
        $this->assertSame([], $users['coursesSubscribedToUsergroup']);

        $courses = $this->model->getUsersAndCoursesSubscribedToAUserGroup($onlyCourses->getId());
        $this->assertSame(get_lang('No user are subscribed to this class'), $courses['warning']);
        $this->assertCount(1, $courses['coursesSubscribedToUsergroup']);
    }

    public function testGetUsersInAndOutOfCourseSplitsMembersBySubscription(): void
    {
        $course = $this->createCourse('ug_course_x');
        $in = $this->createUser('ug_in');
        $out = $this->createUser('ug_out');
        $group = $this->group('ug_group_a');
        $this->addUser($group, $in, 0);
        $this->addUser($group, $out, 0);
        \CourseManager::subscribeUser($in->getId(), $course->getId());

        $data = $this->model->getUsersInAndOutOfCourse($group->getId(), $course->getId());
        // Entries are standardised {identity, email} pairs, not User entities.
        $this->assertSame(['ug_in@example.com'], array_column($data['usersSubscribedToCourse'], 'email'));
        $this->assertSame(['ug_out@example.com'], array_column($data['usersNotSubscribedToCourse'], 'email'));
        $this->assertSame(['identity', 'email'], array_keys($data['usersSubscribedToCourse'][0]));
        $this->assertNull($data['warning']);

        $emptyData = $this->model->getUsersInAndOutOfCourse($this->group('ug_empty')->getId(), $course->getId());
        $this->assertSame(get_lang('No user is subscribed to this class'), $emptyData['warning']);
    }

    public function testDeleteRemovesTheGroupAndAllItsRelations(): void
    {
        $course = $this->createCourse('ug_course_d');
        $sessionEntity = $this->createSession('ug_session_d');
        $alice = $this->createUser('ug_alice');
        $group = $this->group('ug_doomed');
        $keep = $this->group('ug_keep');
        $id = $group->getId();
        $this->addUser($group, $alice);
        $this->addUser($keep, $alice);
        $this->addCourse($group, $course);
        $this->addSession($group, $sessionEntity);

        $this->model->delete($id);

        $this->assertSame(0, $this->countRows('usergroup', ['id' => $id]));
        $this->assertSame(0, $this->countRows('usergroup_rel_user', ['usergroup_id' => $id]));
        $this->assertSame(0, $this->countRows('usergroup_rel_course', ['usergroup_id' => $id]));
        $this->assertSame(0, $this->countRows('usergroup_rel_session', ['usergroup_id' => $id]));
        // Unrelated groups and memberships survive.
        $this->assertSame(1, $this->countRows('usergroup_rel_user', ['usergroup_id' => $keep->getId()]));
    }

    public function testGetGroupsByUsergroupReadsCourseGroupLinks(): void
    {
        // No course group is linked to a fresh class: DQL-backed method returns an empty list.
        $group = $this->group('ug_group_a');
        $this->assertSame([], $this->model->getGroupsByUsergroup($group->getId()));
        $this->assertSame([], $this->model->getGroupsByUsergroup($group->getId(), 123456, 1, 5));
    }

    // ---------------------------------------------------------------- helpers

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function group(string $title, int $type = Usergroup::NORMAL_CLASS): Usergroup
    {
        $em = $this->getEntityManager();
        $group = (new Usergroup())
            ->setTitle($title)
            ->setDescription('desc')
            ->setCreator($this->creator)
            ->setGroupType($type)
            ->addAccessUrl($this->getAccessUrl())
        ;
        $em->persist($group);
        $em->flush();

        return $group;
    }

    private function addUser(Usergroup $group, User $user, int $relation = 0): void
    {
        $this->conn()->insert('usergroup_rel_user', [
            'usergroup_id' => $group->getId(),
            'user_id' => $user->getId(),
            'relation_type' => $relation,
        ]);
    }

    private function addCourse(Usergroup $group, Course $course): void
    {
        $this->conn()->insert('usergroup_rel_course', [
            'usergroup_id' => $group->getId(),
            'course_id' => $course->getId(),
        ]);
    }

    private function addSession(Usergroup $group, Session $session): void
    {
        $this->conn()->insert('usergroup_rel_session', [
            'usergroup_id' => $group->getId(),
            'session_id' => $session->getId(),
        ]);
    }

    private function relationOf(Usergroup $group, User $user): ?int
    {
        $value = $this->conn()->fetchOne(
            'SELECT relation_type FROM usergroup_rel_user WHERE usergroup_id = ? AND user_id = ?',
            [$group->getId(), $user->getId()]
        );

        return false === $value ? null : (int) $value;
    }

    /**
     * @param array<string, int> $criteria
     */
    private function countRows(string $table, array $criteria): int
    {
        $where = [];
        foreach (array_keys($criteria) as $column) {
            $where[] = $column.' = ?';
        }

        return (int) $this->conn()->fetchOne(
            'SELECT COUNT(*) FROM '.$table.' WHERE '.implode(' AND ', $where),
            array_values($criteria)
        );
    }

    /**
     * @return list<int>
     */
    private function sorted(array $ids): array
    {
        $ids = array_map('intval', $ids);
        sort($ids);

        return $ids;
    }
}
