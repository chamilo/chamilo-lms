<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use GroupManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests for the GroupManager methods whose SQL the main characterization test never reached:
 * getGroupUserIds and insertGroupUsers (private, exercised through the public synchronizeGroupUsers).
 * Every title/username starts with "gm_".
 */
final class GroupManagerTier2CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private int $userSeq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/groupmanager.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());

        ChamiloSession::write('_user', ['id' => 1]);
        ChamiloSession::write('_real_cid', 1);
        ChamiloSession::write('_cid', 'AIACT');
        ChamiloSession::write('_course', api_get_course_info('AIACT'));
        ChamiloSession::write('sid', 0);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function fresh(): void
    {
        Database::getManager()->clear();
    }

    private function makeUser(string $name, int $active = 1): int
    {
        ++$this->userSeq;
        $username = 'gm_'.$name.'_'.getmypid().'_'.$this->userSeq;
        $now = '2020-01-01 00:00:00';
        $this->conn()->executeStatement(
            "INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale, password,
                timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 'en_US', 'x', 'UTC', 0, 0, 0, '', '[]', 5, ?, ?, ?, ?)",
            [$username, $username, $username.'@example.com', $username.'@example.com', 'F'.$name, 'L'.$name, $active, random_bytes(16), $now, $now]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function makeGroup(string $title, ?int $categoryId = null, int $max = 0, array $over = []): int
    {
        $row = array_merge([
            'category_id' => $categoryId,
            'title' => 'gm_'.$title,
            'status' => 1,
            'description' => 'd '.$title,
            'max_student' => $max,
            'doc_state' => 1,
            'calendar_state' => 1,
            'work_state' => 1,
            'announcements_state' => 1,
            'forum_state' => 1,
            'wiki_state' => 1,
            'chat_state' => 1,
            'self_registration_allowed' => 0,
            'self_unregistration_allowed' => 0,
        ], $over);
        $this->conn()->insert('c_group_info', $row);

        return (int) $this->conn()->lastInsertId();
    }

    /**
     * Gives a fixture group ("c_group_info") or category ("c_group_category") a resource node and a published
     * resource link to the given course (and session), which is what scopes them to a course in this schema.
     * Prepared statements only; rows are rolled back with the test transaction.
     */
    private function member(int $userId, int $groupId, int $cid = 1): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_group_rel_user (user_id, group_id, c_id, status, role) VALUES (?, ?, ?, 0, '')",
            [$userId, $groupId, $cid]
        );
    }

    private function entity(int $groupId): CGroup
    {
        $this->fresh();
        $group = Database::getManager()->find(CGroup::class, $groupId);
        $this->assertInstanceOf(CGroup::class, $group);

        return $group;
    }

    /**
     * @return list<int>
     */
    private function memberIds(int $groupId): array
    {
        return array_map('intval', $this->conn()->fetchFirstColumn(
            'SELECT user_id FROM c_group_rel_user WHERE group_id = ? ORDER BY user_id',
            [$groupId]
        ));
    }

    /**
     * @return list<int>
     */
    // ---------------------------------------------------------------- tests

    public function testSynchronizeGroupUsersMakesMembershipMatchTheTargetList(): void
    {
        $g = $this->makeGroup('sync');
        [$keep, $drop, $add, $other] = [$this->makeUser('s1'), $this->makeUser('s2'), $this->makeUser('s3'), $this->makeUser('s4')];
        $this->member($keep, $g);
        $this->member($drop, $g);
        // Same user in the same group but in ANOTHER course: not a member of course 1, must survive untouched.
        $this->member($other, $g, 2);
        $group = $this->entity($g);

        GroupManager::synchronizeGroupUsers($group, 1, [$keep, $add, $add, 0, '0']);

        $this->assertSame([$keep, $add], array_values(array_diff($this->memberIds($g), [$other])));
        $rows = $this->conn()->fetchAllAssociative(
            'SELECT c_id, status, role FROM c_group_rel_user WHERE group_id = ? AND user_id = ?',
            [$g, $add]
        );
        $this->assertCount(1, $rows, 'A duplicated id in the target list is inserted once.');
        $this->assertEquals([1, 0, ''], array_values($rows[0]), 'New rows: course 1, status 0, empty role.');
        $this->assertContains($other, $this->memberIds($g), 'Memberships of another course are left alone.');
    }

    public function testSynchronizeGroupUsersIsANoOpWhenAlreadyInSync(): void
    {
        $g = $this->makeGroup('sync2');
        $a = $this->makeUser('t1');
        $this->member($a, $g);
        $group = $this->entity($g);

        GroupManager::synchronizeGroupUsers($group, 1, [$a]);
        $this->assertSame([$a], $this->memberIds($g));

        GroupManager::synchronizeGroupUsers($group, 1, []);
        $this->assertSame([], $this->memberIds($g), 'An empty target empties the group in that course.');

        GroupManager::synchronizeGroupUsers($group, 0, [$a]);
        $this->assertSame([], $this->memberIds($g), 'Course 0 means nothing to do.');
    }

    public function testCreateSubgroupsDistributesTheBaseGroupUsersRoundRobinAndSetsTheirCapacity(): void
    {
        // create_group() persists resources, whose listener needs a logged-in creator.
        $admin = $this->getAdmin();
        self::getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($admin, 'main', $admin->getRoles()))
        ;
        ChamiloSession::write('_user', ['id' => $admin->getId()]);

        $baseId = GroupManager::create_group('gm_base_'.getmypid(), 0, 0, 0);
        $this->assertGreaterThan(0, $baseId);
        $users = [];
        for ($i = 1; $i <= 7; ++$i) {
            $users[] = $this->makeUser('sub'.$i);
        }
        $base = $this->entity($baseId);
        GroupManager::subscribeUsers($users, $base);
        $baseMembers = array_map('intval', GroupManager::get_users($baseId));
        $this->assertCount(7, $baseMembers, 'Fixture: the base group holds the 7 users.');

        $before = array_map('intval', $this->conn()->fetchFirstColumn('SELECT iid FROM c_group_info'));

        GroupManager::create_subgroups($baseId, 3);

        $after = array_map('intval', $this->conn()->fetchFirstColumn('SELECT iid FROM c_group_info ORDER BY iid'));
        $subIds = array_values(array_diff($after, $before));
        $this->assertCount(3, $subIds, 'Three subgroups are created.');

        $expected = [[], [], []];
        foreach ($baseMembers as $index => $userId) {
            $expected[$index % 3][] = $userId;
        }
        $seen = [];
        foreach ($subIds as $n => $subId) {
            $members = array_map('intval', $this->memberIds($subId));
            sort($members);
            $want = $expected[$n];
            sort($want);
            $this->assertSame($want, $members, 'Round-robin distribution of the base group users.');
            $seen = array_merge($seen, $members);
            $this->assertSame(
                \count($members),
                (int) $this->conn()->fetchOne('SELECT max_student FROM c_group_info WHERE iid = ?', [$subId]),
                'max_student of a subgroup equals its member count.'
            );
        }
        sort($seen);
        $all = $baseMembers;
        sort($all);
        $this->assertSame($all, $seen, 'Each user is in exactly one subgroup.');
    }

    public function testGroupRowsKeepTheRawTableRowValues(): void
    {
        $g = $this->makeGroup('shape');
        $u = $this->makeUser('shape');
        $this->member($u, $g);
        $this->fresh();

        $raw = $this->conn()->fetchAssociative('SELECT * FROM c_group_info WHERE iid = ?', [$g]);
        ksort($raw);

        $rows = GroupManager::getAllGroupPerUserSubscription($u);
        $this->assertCount(1, $rows);
        ksort($rows[0]);
        $this->assertSame($raw, $rows[0], 'Every column, same names, same PHP types as the raw row.');

        $batch = GroupManager::getAllGroupsPerUsersSubscription([$u]);
        ksort($batch[$u][0]);
        $this->assertSame($raw, $batch[$u][0]);
    }

    public function testCreateSubgroupsWithANonPositiveCountCreatesNothingInsteadOfDividingByZero(): void
    {
        $admin = $this->getAdmin();
        self::getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($admin, 'main', $admin->getRoles()))
        ;
        ChamiloSession::write('_user', ['id' => $admin->getId()]);

        $baseId = GroupManager::create_group('gm_zero_'.getmypid(), 0, 0, 0);
        GroupManager::subscribeUsers([$this->makeUser('zero1')], $this->entity($baseId));
        $groups = (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_group_info');
        $categories = (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_group_category');

        // The legacy form posts the count as a raw string: 0, '0', '' and negatives must all be refused quietly.
        foreach ([0, '0', '', -2] as $count) {
            GroupManager::create_subgroups($baseId, $count);
        }

        $this->assertSame($groups, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_group_info'));
        $this->assertSame($categories, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_group_category'), 'no empty Subgroups category is left behind');
    }
}
