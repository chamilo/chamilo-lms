<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

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
 * Tier 3 characterization tests for the UserGroupModel methods that UserGroupCharacterizationTest
 * leaves uncovered (multi-URL branches, popularity/age listings, tag search, topic deletion, ...).
 * They pin today's behaviour before the raw SQL is rewritten to DQL. All data is prefixed "ug3_".
 */
final class UserGroupTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private UserGroupModel $model;

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

        $this->createUser('ug3_creator');
        $this->model = new UserGroupModel();
    }

    public function testAStrangerIsNeitherRelatedNorMemberOfAPopulatedGroup(): void
    {
        $group = $this->group('ug3_social', Usergroup::SOCIAL_CLASS);
        $reader = $this->createUser('ug3_reader');
        $stranger = $this->createUser('ug3_stranger');
        $this->addUser($group, $reader, 0);

        $this->assertTrue($this->model->hasRelation($group->getId(), $reader->getId()));
        $this->assertFalse($this->model->hasRelation($group->getId(), $stranger->getId()));
        $this->assertTrue($this->model->is_group_member($group->getId(), $reader->getId()));
        $this->assertFalse($this->model->is_group_member($group->getId(), $stranger->getId()));
        // Adding the stranger as a reader must not be skipped because somebody else already is one.
        $this->assertSame([$group->getId() => [$stranger->getId() => 1]], $this->model->add_users_to_groups([$stranger->getId()], [$group->getId()]));
    }

    public function testGetUserGroupUsersInMultiUrlModeKeepsOnlyUsersOfTheCurrentUrl(): void
    {
        $alice = $this->createUser('ug3_alice');
        $bob = $this->createUser('ug3_bob');
        $group = $this->group('ug3_group');
        $this->addUser($group, $alice);
        $this->addUser($group, $bob);
        $this->conn()->executeStatement('DELETE FROM access_url_rel_user WHERE user_id = ?', [$bob->getId()]);

        $this->model->useMultipleUrl = true;

        $this->assertEquals(1, $this->model->getUserGroupUsers($group->getId(), true));
        $list = $this->model->getUserGroupUsers($group->getId());
        $this->assertCount(1, $list);
        $this->assertSame($alice->getId(), (int) $list[0]['user_id']);
    }

    public function testGetUserGroupByCourseWithDataCountInMultiUrlModeIgnoresTheTypeAndOtherUrls(): void
    {
        $course = $this->createCourse('ug3_course');
        $mine = $this->group('ug3_mine', Usergroup::NORMAL_CLASS);
        $social = $this->group('ug3_social', Usergroup::SOCIAL_CLASS);
        $elsewhere = $this->group('ug3_elsewhere');
        foreach ([$mine, $social, $elsewhere] as $group) {
            $this->addCourse($group, $course);
        }
        $this->conn()->executeStatement('DELETE FROM access_url_rel_usergroup WHERE usergroup_id = ?', [$elsewhere->getId()]);

        $this->model->useMultipleUrl = true;

        // The type argument is not applied in multi-URL mode.
        $this->assertEquals(2, $this->model->getUserGroupByCourseWithDataCount($course->getId(), 0));
        $this->assertEquals(2, $this->model->getUserGroupByCourseWithDataCount($course->getId()));
        $this->assertEquals(0, $this->model->getUserGroupByCourseWithDataCount($course->getId() + 100000));
    }

    public function testGroupsByPopularityAreSocialOrderedByMemberCountAndExcludePendingRoles(): void
    {
        $users = [];
        foreach (['a', 'b', 'c', 'd'] as $n) {
            $users[$n] = $this->createUser('ug3_u'.$n);
        }
        $big = $this->group('ug3_big', Usergroup::SOCIAL_CLASS);
        $small = $this->group('ug3_small', Usergroup::SOCIAL_CLASS);
        $class = $this->group('ug3_class', Usergroup::NORMAL_CLASS);
        $this->addUser($big, $users['a'], 1);
        $this->addUser($big, $users['b'], 0);
        $this->addUser($big, $users['c'], 3);
        $this->addUser($small, $users['a'], 2);
        $this->addUser($class, $users['a'], 0);

        $rows = $this->model->get_groups_by_popularity(100, false);

        $this->assertArrayNotHasKey($class->getId(), $rows);
        $this->assertSame(['count', 'picture', 'title', 'description', 'id'], array_keys($rows[$big->getId()]));
        // Roles 1 and 0 are counted; 3 (pending invitation) and 2 are not (so the small group is absent).
        $this->assertEquals(2, $rows[$big->getId()]['count']);
        $this->assertEquals(0, $this->memberCountOf($rows, $small->getId()));
        $mine = array_values(array_filter(array_keys($rows), fn ($id) => in_array($id, [$big->getId(), $small->getId()], true)));
        $this->assertSame([$big->getId()], $mine);
        $this->assertCount(1, $this->model->get_groups_by_popularity(1, false));
    }

    public function testGroupsByAgeAreSocialNewestFirstAndCountTheirMembers(): void
    {
        $alice = $this->createUser('ug3_alice');
        $bob = $this->createUser('ug3_bob');
        $old = $this->group('ug3_old', Usergroup::SOCIAL_CLASS);
        $new = $this->group('ug3_new', Usergroup::SOCIAL_CLASS);
        $this->conn()->executeStatement("UPDATE usergroup SET created_at = '2020-01-01 00:00:00' WHERE id = ?", [$old->getId()]);
        $this->conn()->executeStatement("UPDATE usergroup SET created_at = '2030-01-01 00:00:00' WHERE id = ?", [$new->getId()]);
        $this->addUser($old, $alice, 1);
        $this->addUser($old, $bob, 5);
        $this->addUser($new, $alice, 1);

        $rows = $this->model->get_groups_by_age(2, false);

        $this->assertSame([$new->getId(), $old->getId()], array_keys($rows));
        $this->assertSame(['count', 'picture', 'title', 'description', 'id'], array_keys($rows[$old->getId()]));
        $this->assertEquals(2, $rows[$old->getId()]['count']);
        $this->assertEquals(1, $rows[$new->getId()]['count']);
        $this->assertCount(1, $this->model->get_groups_by_age(1, false));
    }

    public function testGetAllGroupTagsSearchesTitleDescriptionAndUrlWithinTheCurrentUrl(): void
    {
        $byTitle = $this->group('ug3_needle_title');
        $byDescription = $this->group('ug3_plain');
        $byUrl = $this->group('ug3_plain_url');
        $none = $this->group('ug3_unrelated');
        $elsewhere = $this->group('ug3_needle_elsewhere');
        $this->conn()->executeStatement("UPDATE usergroup SET description = 'about the needle' WHERE id = ?", [$byDescription->getId()]);
        $this->conn()->executeStatement("UPDATE usergroup SET url = 'http://x.test/needle' WHERE id = ?", [$byUrl->getId()]);
        $this->conn()->executeStatement('DELETE FROM access_url_rel_usergroup WHERE usergroup_id = ?', [$elsewhere->getId()]);

        $rows = $this->model->get_all_group_tags('needle');
        $this->assertEqualsCanonicalizing(
            [$byTitle->getId(), $byDescription->getId(), $byUrl->getId(), $elsewhere->getId()],
            array_keys($rows)
        );
        $this->assertSame(['id', 'title', 'description', 'url', 'picture'], array_keys($rows[$byTitle->getId()]));

        $this->model->useMultipleUrl = true;
        $this->assertEqualsCanonicalizing(
            [$byTitle->getId(), $byDescription->getId(), $byUrl->getId()],
            array_keys($this->model->get_all_group_tags('needle'))
        );
        $this->assertCount(1, $this->model->get_all_group_tags('needle', 1, 1));
        $this->assertSame([], $this->model->get_all_group_tags('ug3_no_such_text'));
        $this->assertArrayNotHasKey($none->getId(), $this->model->get_all_group_tags('needle'));
    }

    public function testSearchUserGroupAjaxListsTenTitlesByPrefixAndEllipsisForMore(): void
    {
        require_once api_get_path(SYS_CODE_PATH).'inc/lib/xajax/xajax.inc.php';
        for ($i = 10; $i < 22; $i++) {
            $this->group('ug3_ajax_'.$i);
        }
        $this->group('ug3_other');

        $xml = (string) UserGroupModel::searchUserGroupAjax('ug3_ajax_')->getXML();

        $this->assertSame(10, substr_count($xml, 'add_user_to_url'));
        $this->assertStringContainsString('ug3_ajax_10', $xml);
        $this->assertStringContainsString('ug3_ajax_19', $xml);
        $this->assertStringNotContainsString('ug3_ajax_20', $xml);
        $this->assertStringContainsString('...', $xml);
        $this->assertStringNotContainsString('ug3_other', $xml);
        $small = (string) UserGroupModel::searchUserGroupAjax('ug3_oth')->getXML();
        $this->assertSame(1, substr_count($small, 'add_user_to_url'));
        $this->assertStringNotContainsString('...', $small);
    }

    public function testDeleteTopicMarksTheTopicAndItsRepliesOfThatGroupOnly(): void
    {
        $user = $this->createUser('ug3_poster');
        $group = $this->group('ug3_forum', Usergroup::SOCIAL_CLASS);
        $other = $this->group('ug3_forum_other', Usergroup::SOCIAL_CLASS);
        $topic = $this->message($user, $group, null);
        $reply = $this->message($user, $group, $topic);
        $unrelated = $this->message($user, $group, null);
        $foreignReply = $this->message($user, $other, $topic);

        $this->model->delete_topic($group->getId(), $topic);

        $this->assertSame(3, $this->msgType($topic));
        $this->assertSame(3, $this->msgType($reply));
        $this->assertSame(1, $this->msgType($unrelated));
        $this->assertSame(1, $this->msgType($foreignReply));
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function memberCountOf(array $rows, int $id): int
    {
        return isset($rows[$id]) ? (int) $rows[$id]['count'] : 0;
    }

    private function message($user, Usergroup $group, ?int $parent): int
    {
        $this->conn()->insert('message', [
            'user_sender_id' => $user->getId(),
            'group_id' => $group->getId(),
            'parent_id' => $parent,
            'msg_type' => 1,
            'status' => 0,
            'send_date' => '2026-01-01 00:00:00',
            'title' => 't',
            'content' => 'c',
        ]);

        return (int) $this->conn()->lastInsertId();
    }

    private function msgType(int $id): int
    {
        return (int) $this->conn()->fetchOne('SELECT msg_type FROM message WHERE id = ?', [$id]);
    }

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
            ->setCreator($this->getUser('ug3_creator'))
            ->setGroupType($type)
            ->addAccessUrl($this->getAccessUrl())
        ;
        $em->persist($group);
        $em->flush();

        return $group;
    }

    private function addUser(Usergroup $group, $user, int $relation = 0): void
    {
        $this->conn()->insert('usergroup_rel_user', [
            'usergroup_id' => $group->getId(),
            'user_id' => $user->getId(),
            'relation_type' => $relation,
        ]);
    }

    private function addCourse(Usergroup $group, $course): void
    {
        $this->conn()->insert('usergroup_rel_course', [
            'usergroup_id' => $group->getId(),
            'course_id' => $course->getId(),
        ]);
    }
}
