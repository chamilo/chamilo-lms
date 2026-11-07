<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
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
use Throwable;

/**
 * Characterization tests for the raw-SQL methods of GroupManager (public/main/inc/lib/groupmanager.lib.php,
 * COURSE groups, not user groups).
 *
 * They pin what the legacy queries return TODAY (rows, keys, ordering, NULL/false handling, DB side effects) so
 * a later rewrite to Doctrine/DQL that changes any of it fails here. Where the current behaviour looks like a bug
 * it is pinned as-is and flagged with a "PINNED QUIRK" comment.
 *
 * Fixture rows are inserted with prepared statements (no resource_node: c_group_info.resource_node_id and
 * c_group_category.resource_node_id are nullable), which keeps the test free of the resource_node deadlocks
 * that sibling suites cause on the shared database. Every title/username starts with "gm_".
 */
final class GroupManagerCharacterizationTest extends KernelTestCase
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

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
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

    private function makeSession(): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES ('gm_session', 0, 0, 0, 1, 0)"
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function makeCategory(string $title, int $groupsPerUser = 2, int $maxStudent = 5): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_group_category (title, description, doc_state, calendar_state, work_state, announcements_state,
                forum_state, wiki_state, chat_state, max_student, self_reg_allowed, self_unreg_allowed, groups_per_user)
             VALUES (?, ?, 1, 1, 1, 1, 1, 1, 1, ?, 0, 0, ?)',
            ['gm_'.$title, 'desc '.$title, $maxStudent, $groupsPerUser]
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
    private function linkToCourse(string $table, int $id, int $courseId = 1, ?int $sessionId = null): void
    {
        $typeTitle = 'c_group_info' === $table ? 'groups' : 'group_categories';
        $typeId = (int) $this->conn()->fetchOne('SELECT id FROM resource_type WHERE title = ?', [$typeTitle]);
        $this->assertGreaterThan(0, $typeId);
        $now = '2020-01-01 00:00:00';
        $this->conn()->executeStatement(
            'INSERT INTO resource_node (resource_type_id, creator_id, title, slug, created_at, updated_at, public, uuid)
             VALUES (?, 1, ?, ?, ?, ?, 0, ?)',
            [$typeId, 'gm_node', 'gm-node-'.$table.'-'.$id, $now, $now, random_bytes(16)]
        );
        $nodeId = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            'UPDATE '.$table.' SET resource_node_id = ? WHERE iid = ?',
            [$nodeId, $id]
        );
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group,
                created_at, updated_at) VALUES (?, ?, ?, 2, 0, 0, ?, ?)',
            [$nodeId, $courseId, $sessionId, $now, $now]
        );
    }

    private function member(int $userId, int $groupId, int $cid = 1): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_group_rel_user (user_id, group_id, c_id, status, role) VALUES (?, ?, ?, 0, '')",
            [$userId, $groupId, $cid]
        );
    }

    private function tutor(int $userId, int $groupId, int $cid = 1): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_group_rel_tutor (user_id, group_id, c_id) VALUES (?, ?, ?)',
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
    private function tutorIds(int $groupId): array
    {
        return array_map('intval', $this->conn()->fetchFirstColumn(
            'SELECT user_id FROM c_group_rel_tutor WHERE group_id = ? ORDER BY user_id',
            [$groupId]
        ));
    }

    // ---------------------------------------------------------------- categories

    public function testGetCategoryReturnsFullRowWithNumericAndAssocKeys(): void
    {
        $cat = $this->makeCategory('cat_a', 3, 7);

        $row = GroupManager::get_category($cat);

        $this->assertSame((string) $cat, (string) $row['iid']);
        $this->assertSame('gm_cat_a', $row['title']);
        $this->assertSame('desc cat_a', $row['description']);
        $this->assertSame('7', (string) $row['max_student']);
        $this->assertSame('3', (string) $row['groups_per_user']);
        $this->assertArrayHasKey(0, $row, 'Database::fetch_array returns BOTH numeric and associative keys');
        $this->assertNull($row['resource_node_id']);
    }

    public function testGetCategoryEmptyOrUnknownId(): void
    {
        $this->assertSame([], GroupManager::get_category(0));
        $this->assertSame([], GroupManager::get_category(null));
        $this->assertSame([], GroupManager::get_category(999999999), 'unknown id: fetch_array() on no row gives []');
    }

    public function testGetCategoryByTitleFindsTheCategoryOfTheCourse(): void
    {
        // Fixed: the SQL filtered on c_group_category.c_id (column gone from the schema) and threw for any title.
        // The course now comes from the category's resource link, like every other course-scoped lookup.
        $mine = $this->makeCategory('cat_title');
        $elsewhere = $this->makeCategory('cat_other_course');
        $unlinked = $this->makeCategory('cat_unlinked');
        $this->linkToCourse('c_group_category', $mine, 1);
        $this->linkToCourse('c_group_category', $elsewhere, 2);

        $row = GroupManager::getCategoryByTitle('gm_cat_title');
        $this->assertSame((string) $mine, (string) $row['iid']);
        $this->assertSame('gm_cat_title', $row['title']);
        $this->assertSame((string) $mine, (string) GroupManager::getCategoryByTitle("  gm_cat_title ")['iid'], 'title is trimmed');
        $this->assertSame([], GroupManager::getCategoryByTitle('gm_cat_other_course'), 'linked to another course only');
        $this->assertSame((string) $elsewhere, (string) GroupManager::getCategoryByTitle('gm_cat_other_course', 'USINGCHAMILO')['iid'], 'explicit course code');
        $this->assertSame([], GroupManager::getCategoryByTitle('gm_cat_unlinked'), 'no resource link: in no course');
        $this->assertSame([], GroupManager::getCategoryByTitle("gm_cat_title' OR '1'='1"), 'title is bound, never interpolated');
        $this->assertGreaterThan(0, $unlinked);
    }

    public function testGetCategoryByTitleBlankTitleShortCircuits(): void
    {
        $this->assertSame([], GroupManager::getCategoryByTitle('   '));
        $this->assertSame([], GroupManager::getCategoryByTitle(''));
    }

    public function testGetCategoryFromGroup(): void
    {
        $cat = $this->makeCategory('cat_fg');
        $inCat = $this->makeGroup('g_in', $cat);
        $noCat = $this->makeGroup('g_out');

        $row = GroupManager::get_category_from_group($inCat);
        $this->assertSame('gm_cat_fg', $row['title']);
        $this->assertSame((string) $cat, (string) $row['iid']);

        $this->assertSame([], GroupManager::get_category_from_group($noCat), 'INNER JOIN: group without category => []');
        $this->assertSame([], GroupManager::get_category_from_group(0));
        $this->assertSame([], GroupManager::get_category_from_group(999999999), 'unknown group => []');
    }

    public function testGetCategoryFromGroupWithoutCourseContextReturnsFalse(): void
    {
        $g = $this->makeGroup('g_nocontext');
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('_cid');
        ChamiloSession::erase('_course');

        // By design, not a bug: no course context means nothing to look in, so false (not []) comes back before any
        // SQL. Every caller (group pages, CourseGroupManager, exercise.lib) runs in a course or passes its code.
        $this->assertFalse(GroupManager::get_category_from_group($g));
    }

    public function testDeleteCategoryDetachesItsGroupsAndRemovesTheRow(): void
    {
        $cat = $this->makeCategory('cat_del');
        $g1 = $this->makeGroup('del_1', $cat);
        $g2 = $this->makeGroup('del_2', $cat);
        $other = $this->makeCategory('cat_keep');
        $g3 = $this->makeGroup('del_3', $other);

        $this->assertTrue(GroupManager::delete_category($cat));

        $this->assertFalse($this->conn()->fetchOne('SELECT iid FROM c_group_category WHERE iid = ?', [$cat]));
        $this->assertNotFalse($this->conn()->fetchOne('SELECT iid FROM c_group_category WHERE iid = ?', [$other]));
        foreach ([$g1, $g2] as $g) {
            $this->assertNull(
                $this->conn()->fetchOne('SELECT category_id FROM c_group_info WHERE iid = ?', [$g]) ?: null,
                'groups survive with category_id NULL (they are not deleted)'
            );
            $this->assertNotFalse($this->conn()->fetchOne('SELECT iid FROM c_group_info WHERE iid = ?', [$g]));
        }
        $this->assertSame((string) $other, (string) $this->conn()->fetchOne('SELECT category_id FROM c_group_info WHERE iid = ?', [$g3]));
    }

    public function testDeleteCategoryUnknownIdIsStillTrue(): void
    {
        $this->assertTrue(GroupManager::delete_category(999999999));
    }

    public function testDeleteCategoryWithoutCourseContextReturnsFalseAndKeepsRow(): void
    {
        $cat = $this->makeCategory('cat_nocontext');
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('_cid');
        ChamiloSession::erase('_course');

        // By design, not a bug: no course context means nothing may be deleted. Every caller (group.php,
        // course deletion, CourseGroupManager) has a course or passes its code, so the guard only fails closed.
        $this->assertFalse(GroupManager::delete_category($cat));
        $this->assertNotFalse($this->conn()->fetchOne('SELECT iid FROM c_group_category WHERE iid = ?', [$cat]));
    }

    public function testUpdateCategoryRewritesAllScalarColumns(): void
    {
        $cat = $this->makeCategory('cat_upd', 2, 5);

        GroupManager::update_category($cat, "gm_new 'title'", 'new desc', 2, 0, 1, 2, 0, 1, 2, 1, 1, 9, 4, 1);
        $row = $this->conn()->fetchAssociative('SELECT * FROM c_group_category WHERE iid = ?', [$cat]);

        $this->assertSame("gm_new 'title'", $row['title'], 'quotes are escaped, stored verbatim');
        $this->assertSame('new desc', $row['description']);
        $this->assertSame([2, 0, 1, 2, 0, 1, 2], array_map('intval', [
            $row['doc_state'], $row['work_state'], $row['calendar_state'], $row['announcements_state'],
            $row['forum_state'], $row['wiki_state'], $row['chat_state'],
        ]));
        $this->assertSame([1, 1, 9, 4], array_map('intval', [
            $row['self_reg_allowed'], $row['self_unreg_allowed'], $row['max_student'], $row['groups_per_user'],
        ]));
    }

    public function testUpdateCategoryDocumentAccessDependsOnSetting(): void
    {
        $cat = $this->makeCategory('cat_docacc');
        $this->conn()->executeStatement('UPDATE c_group_category SET document_access = 0 WHERE iid = ?', [$cat]);

        GroupManager::update_category($cat, 'gm_x', 'd', 1, 1, 1, 1, 1, 1, 1, 0, 0, 5, 1, 2);

        $expected = 'true' === api_get_setting('document.group_category_document_access') ? 2 : 0;
        $this->assertSame(
            $expected,
            (int) $this->conn()->fetchOne('SELECT document_access FROM c_group_category WHERE iid = ?', [$cat]),
            'document_access is only written when document.group_category_document_access is "true"'
        );
    }

    public function testGetCurrentMaxGroupsPerUser(): void
    {
        $cat = $this->makeCategory('cat_max');
        $g1 = $this->makeGroup('mx_1', $cat);
        $g2 = $this->makeGroup('mx_2', $cat);
        $g3 = $this->makeGroup('mx_3'); // no category
        $u1 = $this->makeUser('mx1');
        $u2 = $this->makeUser('mx2');
        $this->member($u1, $g1);
        $this->member($u1, $g2);
        $this->member($u2, $g1);
        $this->member($u2, $g2);
        $this->member($u2, $g3);

        $this->assertSame(2, (int) GroupManager::get_current_max_groups_per_user($cat), 'filtered by category: best user has 2');
        $this->assertSame(3, (int) GroupManager::get_current_max_groups_per_user(), 'no category: u2 has 3 groups');
    }

    public function testGetCurrentMaxGroupsPerUserEmptyCategoryIsZero(): void
    {
        $cat = $this->makeCategory('cat_max_empty');
        $this->makeGroup('mxe_1', $cat);

        $this->assertSame(0, GroupManager::get_current_max_groups_per_user($cat));
    }

    // ---------------------------------------------------------------- group properties

    public function testGetGroupProperties(): void
    {
        $g = $this->makeGroup('props', null, 12, ['doc_state' => 2, 'forum_state' => 0, 'self_registration_allowed' => 1]);

        $p = GroupManager::get_group_properties($g);

        $this->assertSame($g, (int) $p['id']);
        $this->assertSame($g, (int) $p['iid']);
        $this->assertSame('gm_props', $p['name']);
        $this->assertSame('gm_props', $p['title']);
        $this->assertSame(12, (int) $p['maximum_number_of_students']);
        $this->assertSame(12, (int) $p['max_student']);
        $this->assertSame(2, (int) $p['doc_state']);
        $this->assertSame(0, (int) $p['forum_state']);
        $this->assertSame(1, (int) $p['self_registration_allowed']);
        $this->assertSame(0, (int) $p['self_unregistration_allowed']);
        $this->assertSame(0, $p['count_users'], 'count_users is hard-coded to 0');
        $this->assertNull($p['count_all']);
        $this->assertSame(0, (int) $p['document_access']);
    }

    public function testGetGroupPropertiesEmptyAndUnknown(): void
    {
        $this->assertNull(GroupManager::get_group_properties(0));
        $this->assertSame([], GroupManager::get_group_properties(999999999));
    }

    public function testSetGroupPropertiesUpdatesRowAndForumVisibility(): void
    {
        $g = $this->makeGroup('setp', null, 3);
        $this->conn()->executeStatement(
            "INSERT INTO c_forum_forum (title, forum_comment, forum_threads, forum_posts, forum_of_group,
                forum_group_public_private, locked, forum_image)
             VALUES ('gm_forum', '', 0, 0, ?, 'public', 0, '')",
            [(string) $g]
        );
        $forumId = (int) $this->conn()->lastInsertId();

        $this->assertTrue(GroupManager::set_group_properties($g, "gm_new 'name'", 'nd', 6, 2, 0, 1, 2, 2, 1, 0, 1, 1, null, 0));
        $r = $this->conn()->fetchAssociative('SELECT * FROM c_group_info WHERE iid = ?', [$g]);

        $this->assertSame("gm_new 'name'", $r['title']);
        $this->assertSame('nd', $r['description']);
        $this->assertSame([6, 2, 0, 1, 2, 2, 1, 0, 1, 1], array_map('intval', [
            $r['max_student'], $r['doc_state'], $r['work_state'], $r['calendar_state'], $r['announcements_state'],
            $r['forum_state'], $r['wiki_state'], $r['chat_state'], $r['self_registration_allowed'], $r['self_unregistration_allowed'],
        ]));
        $this->assertSame(
            'private',
            $this->conn()->fetchOne('SELECT forum_group_public_private FROM c_forum_forum WHERE iid = ?', [$forumId]),
            'forum_state 2 => the group forum becomes private'
        );

        GroupManager::set_group_properties($g, 'gm_n', 'nd', 6, 2, 0, 1, 2, 0, 1, 0, 1, 1);
        $this->assertSame('unavailable', $this->conn()->fetchOne('SELECT forum_group_public_private FROM c_forum_forum WHERE iid = ?', [$forumId]));
        GroupManager::set_group_properties($g, 'gm_n', 'nd', 6, 2, 0, 1, 2, 1, 1, 0, 1, 1);
        $this->assertSame('public', $this->conn()->fetchOne('SELECT forum_group_public_private FROM c_forum_forum WHERE iid = ?', [$forumId]));
    }

    public function testSetGroupPropertiesWithEmptyIdDoesNothing(): void
    {
        $this->assertFalse(GroupManager::set_group_properties(0, 'x', 'x', 1, 1, 1, 1, 1, 1, 1, 1, 0, 0));
    }

    public function testSetGroupPropertiesAppliesTheCategoryWhenCategoriesAreAllowed(): void
    {
        $this->setSetting('group.allow_group_categories', 'true');
        $this->assertSame('true', api_get_setting('allow_group_categories'), 'precondition: setting really on');
        $catA = $this->makeCategory('sp_a');
        $catB = $this->makeCategory('sp_b');
        $g = $this->makeGroup('setp_cat', $catA);

        GroupManager::set_group_properties($g, 'gm_c', 'd', 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, $catB);
        $this->assertSame((string) $catB, (string) $this->conn()->fetchOne('SELECT category_id FROM c_group_info WHERE iid = ?', [$g]));

        GroupManager::set_group_properties($g, 'gm_c', 'd', 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, null);
        $this->assertSame((string) $catB, (string) $this->conn()->fetchOne('SELECT category_id FROM c_group_info WHERE iid = ?', [$g]), 'no category passed: the current one is kept');

        GroupManager::set_group_properties($g, 'gm_c', 'd', 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 999999999);
        $this->assertSame((string) $catB, (string) $this->conn()->fetchOne('SELECT category_id FROM c_group_info WHERE iid = ?', [$g]), 'unknown category ignored: the current one is kept');
    }

    public function testSetGroupPropertiesResetsTheCategoryWhenCategoriesAreDisabled(): void
    {
        // By design, not a bug: with "allow_group_categories" off the UI has no category concept (groups live in
        // the default one), so any category is dropped instead of leaving a group attached to a hidden category.
        $this->setSetting('group.allow_group_categories', 'false');
        $this->assertSame('false', api_get_setting('allow_group_categories'), 'precondition: setting really off');
        $catA = $this->makeCategory('sp_a_off');
        $catB = $this->makeCategory('sp_b_off');
        $g = $this->makeGroup('setp_cat_off', $catA);

        GroupManager::set_group_properties($g, 'gm_c', 'd', 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, $catB);

        $this->assertNull($this->conn()->fetchOne('SELECT category_id FROM c_group_info WHERE iid = ?', [$g]) ?: null);
    }

    public function testGetGroupByNameFindsTheGroupOfTheCourseAndSession(): void
    {
        // Fixed: the SQL filtered on c_group_info.c_id and .name (neither exists: the column is "title") and threw.
        $sessionId = $this->makeSession();
        $mine = $this->makeGroup('byname');
        $inSession = $this->makeGroup('byname_sess');
        $elsewhere = $this->makeGroup('byname_other');
        $this->linkToCourse('c_group_info', $mine, 1);
        $this->linkToCourse('c_group_info', $inSession, 1, $sessionId);
        $this->linkToCourse('c_group_info', $elsewhere, 2);

        $row = GroupManager::getGroupByName('gm_byname');
        $this->assertSame((string) $mine, (string) $row['iid']);
        $this->assertSame('gm_byname', $row['title']);
        $this->assertSame([], GroupManager::getGroupByName('gm_byname_sess'), 'session group is not in the base course');
        $this->assertSame((string) $inSession, (string) GroupManager::getGroupByName('gm_byname_sess', null, $sessionId)['iid']);
        $this->assertSame([], GroupManager::getGroupByName('gm_byname_other'), 'linked to another course only');
        $this->assertSame([], GroupManager::getGroupByName("gm_byname' OR '1'='1"), 'name is bound, never interpolated');
    }

    public function testGetGroupByNameBlankShortCircuits(): void
    {
        $this->assertSame([], GroupManager::getGroupByName('  '));
    }

    // ---------------------------------------------------------------- counts and members

    public function testNumberOfStudentsCountsMembersOfTheCourseOnly(): void
    {
        $g = $this->makeGroup('count');
        $u1 = $this->makeUser('c1');
        $u2 = $this->makeUser('c2');
        $u3 = $this->makeUser('c3');
        $this->member($u1, $g, 1);
        $this->member($u2, $g, 1);
        $this->member($u3, $g, 2);
        $this->tutor($this->makeUser('ctut'), $g);

        $this->assertSame(2, (int) GroupManager::number_of_students($g), 'current course (1): tutors and other-course rows excluded');
        $this->assertSame(1, (int) GroupManager::number_of_students($g, 2), 'explicit course id');
        $this->assertSame(0, (int) GroupManager::number_of_students(999999999));
    }

    public function testMaximumNumberOfStudentsZeroMeansInfinite(): void
    {
        $limited = $this->makeGroup('max_5', null, 5);
        $unlimited = $this->makeGroup('max_0', null, 0);

        $this->assertSame(5, (int) GroupManager::maximum_number_of_students($limited));
        $this->assertSame(GroupManager::INFINITE, GroupManager::maximum_number_of_students($unlimited));
    }

    public function testMaximumNumberOfStudentsUnknownGroupHasNoCapacity(): void
    {
        // Fixed: ->max_student was read on null (PHP warning). An unknown group answers 0, not INFINITE, so the
        // only caller (canUserSubscribe(): count < max) fails closed and nobody can join a missing group.
        $this->assertSame(0, GroupManager::maximum_number_of_students(999999999));
    }

    public function testUserInNumberOfGroups(): void
    {
        $cat = $this->makeCategory('unog');
        $g1 = $this->makeGroup('unog_1', $cat);
        $g2 = $this->makeGroup('unog_2', $cat);
        $g3 = $this->makeGroup('unog_3');
        $u = $this->makeUser('unog');
        $tutorOnly = $this->makeUser('unog_t');
        $this->member($u, $g1);
        $this->member($u, $g2);
        $this->member($u, $g3);
        $this->tutor($tutorOnly, $g1);

        $this->assertSame(3, (int) GroupManager::user_in_number_of_groups($u));
        $this->assertSame(2, (int) GroupManager::user_in_number_of_groups($u, $cat), 'category filter');
        $this->assertSame(0, (int) GroupManager::user_in_number_of_groups($tutorOnly), 'tutorships are not counted');
        $this->assertSame(0, (int) GroupManager::user_in_number_of_groups($u, 999999999));
    }

    public function testGetUsersReturnsIdsOfActiveMembersOnly(): void
    {
        $g = $this->makeGroup('getusers');
        $u1 = $this->makeUser('gu1');
        $u2 = $this->makeUser('gu2');
        $deleted = $this->makeUser('gudel', -2);
        $inactive = $this->makeUser('guinact', 0);
        foreach ([$u1, $u2, $deleted, $inactive] as $u) {
            $this->member($u, $g);
        }
        $this->tutor($this->makeUser('gutut'), $g);

        $ids = array_map('intval', GroupManager::get_users($g));
        sort($ids);

        $this->assertSame([$u1, $u2, $inactive], $ids, 'soft-deleted (-2) excluded, merely inactive (0) kept, tutors excluded');
        $this->assertSame(3, (int) GroupManager::get_users($g, false, null, null, true));
    }

    public function testGetUsersSortingAndPaging(): void
    {
        $g = $this->makeGroup('getusers_sort');
        $a = $this->makeUser('a');
        $b = $this->makeUser('b');
        $c = $this->makeUser('c');
        foreach ([$c, $a, $b] as $u) {
            $this->member($u, $g);
        }

        $asc = array_map('intval', GroupManager::get_users($g, false, null, null, false, null, 'id', 'ASC'));
        $desc = array_map('intval', GroupManager::get_users($g, false, null, null, false, null, 'id', 'DESC'));
        $this->assertSame([$a, $b, $c], $asc);
        $this->assertSame([$c, $b, $a], $desc);

        $this->assertSame(
            [(string) $b, (string) $c],
            array_map('strval', GroupManager::get_users($g, false, 1, 2, false, null, 'id', 'ASC')),
            'LIMIT start,limit'
        );
        // Fixed: a start of 0 (first page) used to disable the LIMIT and return every row.
        $this->assertSame(
            [(string) $a, (string) $b],
            array_map('strval', GroupManager::get_users($g, false, 0, 2, false, null, 'id', 'ASC')),
            'first page (start 0) is limited too'
        );
        // Any direction other than the exact string 'ASC' is DESC.
        $this->assertSame([$c, $b, $a], array_map('intval', GroupManager::get_users($g, false, null, null, false, null, 'id', 'asc')));
    }

    public function testGetUsersWithExtraInfoReturnsUserInfoArrays(): void
    {
        $g = $this->makeGroup('getusers_info');
        $u = $this->makeUser('info');
        $this->member($u, $g);

        $list = GroupManager::get_users($g, true);

        $this->assertCount(1, $list);
        $this->assertSame($u, (int) $list[0]['user_id']);
        $this->assertStringStartsWith('gm_info_', $list[0]['username']);
    }

    public function testGetStudentsAndTutorsMembersFirstThenTutors(): void
    {
        $g = $this->makeGroup('st_and_tut');
        $m1 = $this->makeUser('m1');
        $m2 = $this->makeUser('m2');
        $t = $this->makeUser('t1');
        $this->tutor($t, $g);
        $this->member($m2, $g);
        $this->member($m1, $g);

        $ids = array_map(static fn ($u) => (int) $u['user_id'], GroupManager::getStudentsAndTutors($g));

        $this->assertSame([$m2, $m1, $t], $ids, 'members (insertion order) then tutors; not de-duplicated, not sorted');
    }

    public function testGetTutorsAndStudents(): void
    {
        $g = $this->makeGroup('tutors');
        $t1 = $this->makeUser('t1');
        $t2 = $this->makeUser('t2');
        $s = $this->makeUser('s1');
        $inactive = $this->makeUser('s2', 0);
        $this->tutor($t1, $g);
        $this->tutor($t2, $g);
        $this->member($s, $g);
        $this->member($inactive, $g);

        $tutors = GroupManager::getTutors(['iid' => $g]);
        $this->assertEqualsCanonicalizing([$t1, $t2], array_map(static fn ($u) => (int) $u['user_id'], $tutors));

        $students = array_map(static fn ($u) => (int) $u['user_id'], GroupManager::getStudents($g));
        $this->assertEqualsCanonicalizing([$s, $inactive], $students);
        $active = array_map(static fn ($u) => (int) $u['user_id'], GroupManager::getStudents($g, true));
        $this->assertSame([$s], $active, 'filterOnlyActive keeps active = 1 only');

        $this->assertEquals(2, GroupManager::getStudentsCount($g));
        $this->assertEquals(1, GroupManager::getStudentsCount($g, true));
        $this->assertEquals(0, GroupManager::getStudentsCount(999999999));
    }

    public function testGetGroupsUsersIsDistinctAcrossGroups(): void
    {
        $g1 = $this->makeGroup('gu_1');
        $g2 = $this->makeGroup('gu_2');
        $g3 = $this->makeGroup('gu_3');
        $u1 = $this->makeUser('gg1');
        $u2 = $this->makeUser('gg2');
        $u3 = $this->makeUser('gg3');
        $this->member($u1, $g1);
        $this->member($u1, $g2);
        $this->member($u2, $g2);
        $this->member($u3, $g3);
        $this->tutor($this->makeUser('ggt'), $g1);

        $result = array_map('intval', GroupManager::get_groups_users([$g1, $g2]));
        sort($result);

        $this->assertSame([$u1, $u2], $result);
        $this->assertSame([$u3], array_map('intval', GroupManager::get_groups_users([(string) $g3, 'abc'])), 'non-numeric ids cast to 0');
    }

    public function testGetGroupsUsersEmptyListGivesAnEmptyResult(): void
    {
        // Fixed: an empty list used to build "IN ()" (SQL syntax error); no group means no users.
        $this->assertSame([], GroupManager::get_groups_users([]));
    }

    public function testIsSubscribedIsScopedToTheCourse(): void
    {
        $g = $this->makeGroup('issub');
        $u = $this->makeUser('sub');
        $other = $this->makeUser('nosub');
        $this->member($u, $g, 1);
        $group = $this->entity($g);

        $this->assertTrue(GroupManager::is_subscribed($u, $group));
        $this->assertFalse(GroupManager::is_subscribed($other, $group));
        $this->assertFalse(GroupManager::is_subscribed($u, $group, 2), 'row belongs to course 1, not 2');
        $this->assertFalse(GroupManager::is_subscribed(0, $group));
    }

    public function testIsTutorOfGroupAndIsUserInGroup(): void
    {
        $g = $this->makeGroup('tutorof');
        $t = $this->makeUser('tut');
        $m = $this->makeUser('mem');
        $x = $this->makeUser('nobody');
        $this->tutor($t, $g);
        $this->member($m, $g);
        $group = $this->entity($g);

        $this->assertTrue(GroupManager::isTutorOfGroup($t, $group));
        $this->assertFalse(GroupManager::isTutorOfGroup($m, $group));
        $this->assertFalse(GroupManager::isTutorOfGroup($t, null));

        $this->assertTrue(GroupManager::isUserInGroup($m, $group));
        $this->assertTrue(GroupManager::isUserInGroup($t, $group));
        $this->assertFalse(GroupManager::isUserInGroup($x, $group));
    }

    public function testIsTutorOfGroupLegacyArrayVariantIsCourseScoped(): void
    {
        $g = $this->makeGroup('tutorof_arr');
        $t = $this->makeUser('tut');
        $this->tutor($t, $g, 1);

        $this->assertTrue(GroupManager::is_tutor_of_group($t, ['id' => $g]));
        $this->assertFalse(GroupManager::is_tutor_of_group($t, ['id' => $g], 2), 'c_id is part of this query');
        $this->assertFalse(GroupManager::is_tutor_of_group($t, []));
        $this->assertFalse(GroupManager::is_tutor_of_group($this->makeUser('x'), ['id' => $g]));
    }

    public function testGetGroupIdsMembershipsThenTutorships(): void
    {
        $g1 = $this->makeGroup('gids_1');
        $g2 = $this->makeGroup('gids_2');
        $g3 = $this->makeGroup('gids_3');
        $u = $this->makeUser('gids');
        $this->tutor($u, $g3);
        $this->member($u, $g2);
        $this->member($u, $g1);
        $this->member($u, $g3);

        $ids = array_map('intval', GroupManager::get_group_ids(1, $u));

        // g3 appears twice (member and tutor), member rows come first (insertion order), tutor rows last.
        $this->assertSame([$g2, $g1, $g3, $g3], $ids);
        $this->assertSame([], GroupManager::get_group_ids(1, $this->makeUser('gids_none')));
    }

    public function testGetUserGroupNameListsTitles(): void
    {
        $g1 = $this->makeGroup('ugn_1');
        $g2 = $this->makeGroup('ugn_2');
        $u = $this->makeUser('ugn');
        $this->member($u, $g1);
        $this->member($u, $g2);
        $this->tutor($u, $this->makeGroup('ugn_tutor'));

        $names = GroupManager::get_user_group_name($u);
        sort($names);

        $this->assertSame(['gm_ugn_1', 'gm_ugn_2'], $names, 'only memberships, not tutorships');
    }

    public function testGetAllGroupPerUserSubscriptionIncludesTutorshipAndIsDistinct(): void
    {
        $g1 = $this->makeGroup('agp_1');
        $g2 = $this->makeGroup('agp_2');
        $u = $this->makeUser('agp');
        $this->member($u, $g1);
        $this->tutor($u, $g1);
        $this->tutor($u, $g2);

        $groups = GroupManager::getAllGroupPerUserSubscription($u);

        $this->assertCount(2, $groups, 'DISTINCT g.*: member+tutor of the same group counts once');
        $this->assertEqualsCanonicalizing([$g1, $g2], array_map(static fn ($g) => (int) $g['iid'], $groups));
        $this->assertSame([], GroupManager::getAllGroupPerUserSubscription($this->makeUser('agp_none')));
        $this->assertArrayHasKey('max_student', $groups[0], 'raw c_group_info columns are exposed');
    }

    public function testGetAllGroupsPerUsersSubscriptionBatchVariant(): void
    {
        $g1 = $this->makeGroup('batch_1');
        $g2 = $this->makeGroup('batch_2');
        $u1 = $this->makeUser('b1');
        $u2 = $this->makeUser('b2');
        $this->member($u1, $g1);
        $this->tutor($u1, $g1);
        $this->tutor($u1, $g2);
        $this->member($u2, $g2);

        $this->assertSame([], GroupManager::getAllGroupsPerUsersSubscription([]));
        $this->assertSame([], GroupManager::getAllGroupsPerUsersSubscription([0, 'x']));

        $map = GroupManager::getAllGroupsPerUsersSubscription([$u1, $u2, $u2, $this->makeUser('b3')]);

        $this->assertEqualsCanonicalizing([$u1, $u2], array_keys($map), 'users without groups are absent');
        $this->assertEqualsCanonicalizing([$g1, $g2], array_map(static fn ($g) => (int) $g['iid'], $map[$u1]), 'keyed by group iid: member+tutor merged');
        $this->assertSame([$g2], array_map(static fn ($g) => (int) $g['iid'], $map[$u2]));
        $this->assertArrayNotHasKey('assoc_user_id', $map[$u1][0]);
        $this->assertSame(array_keys($map[$u1]), [0, 1], 'lists are re-indexed (array_values)');
    }

    // ---------------------------------------------------------------- subscribe / unsubscribe

    public function testSubscribeUsersInsertsRowsAndSkipsInvalidAndDuplicates(): void
    {
        $g = $this->makeGroup('subs', null, 0);
        $u1 = $this->makeUser('s1');
        $u2 = $this->makeUser('s2');
        $group = $this->entity($g);

        $this->assertTrue(GroupManager::subscribeUsers([$u1, (string) $u1, 0, $u2], $group));
        $this->assertSame([$u1, $u2], $this->memberIds($g), 'duplicates and 0 are filtered');
        $row = $this->conn()->fetchAssociative('SELECT * FROM c_group_rel_user WHERE group_id = ? AND user_id = ?', [$g, $u1]);
        $this->assertSame(1, (int) $row['c_id']);
        $this->assertSame(0, (int) $row['status']);
        $this->assertSame('', $row['role']);

        $this->assertTrue(GroupManager::subscribeUsers($u1, $group), 're-subscribing is a silent no-op');
        $this->assertSame([$u1, $u2], $this->memberIds($g));
    }

    public function testSubscribeUsersEdgeCases(): void
    {
        $g = $this->makeGroup('subs_edge');
        $u = $this->makeUser('se');
        $group = $this->entity($g);

        $this->assertFalse(GroupManager::subscribeUsers([$u], null));
        $this->assertTrue(GroupManager::subscribeUsers([], $group));
        $this->assertSame([], $this->memberIds($g));

        ChamiloSession::erase('_real_cid');
        $this->assertFalse(GroupManager::subscribeUsers([$u], $group), 'no course context and no course id => false');
        $this->assertSame([], $this->memberIds($g));
        $this->assertTrue(GroupManager::subscribeUsers([$u], $group, 2), 'explicit course id works without context');
        $this->assertSame(2, (int) $this->conn()->fetchOne('SELECT c_id FROM c_group_rel_user WHERE group_id = ?', [$g]));
    }

    public function testSubscribeUsersRespectsMaximumNumberOfStudents(): void
    {
        $g = $this->makeGroup('subs_max', null, 2);
        [$u1, $u2, $u3] = [$this->makeUser('x1'), $this->makeUser('x2'), $this->makeUser('x3')];
        $group = $this->entity($g);

        GroupManager::subscribeUsers([$u1, $u2, $u3], $group);

        $this->assertSame([$u1, $u2], $this->memberIds($g), 'the third user is silently refused: group is full');
    }

    public function testSubscribeUsersRespectsGroupsPerUserOfTheCategory(): void
    {
        $cat = $this->makeCategory('subs_gpu', 1);
        $g1 = $this->makeGroup('gpu_1', $cat);
        $g2 = $this->makeGroup('gpu_2', $cat);
        $u = $this->makeUser('gpu');

        GroupManager::subscribeUsers([$u], $this->entity($g1));
        GroupManager::subscribeUsers([$u], $this->entity($g2));

        $this->assertSame([$u], $this->memberIds($g1));
        $this->assertSame([], $this->memberIds($g2), 'groups_per_user = 1 reached: second subscription refused');
    }

    public function testSubscribeUsersGroupsPerUserZeroMeansNoLimit(): void
    {
        $cat = $this->makeCategory('subs_gpu0', GroupManager::GROUP_PER_MEMBER_NO_LIMIT);
        $g1 = $this->makeGroup('gpu0_1', $cat);
        $g2 = $this->makeGroup('gpu0_2', $cat);
        $u = $this->makeUser('gpu0');

        GroupManager::subscribeUsers([$u], $this->entity($g1));
        GroupManager::subscribeUsers([$u], $this->entity($g2));

        $this->assertSame([$u], $this->memberIds($g1));
        $this->assertSame([$u], $this->memberIds($g2));
    }

    public function testSubscribeUsersRefusesATutorOfTheSameGroup(): void
    {
        $g = $this->makeGroup('subs_tutor');
        $u = $this->makeUser('tt');
        $this->tutor($u, $g);

        GroupManager::subscribeUsers([$u], $this->entity($g));

        $this->assertSame([], $this->memberIds($g), 'a tutor cannot also be a member');
    }

    public function testCanUserSubscribe(): void
    {
        $g = $this->makeGroup('can', null, 1);
        $u1 = $this->makeUser('can1');
        $u2 = $this->makeUser('can2');
        $tut = $this->makeUser('cant');
        $this->member($u1, $g);
        $this->tutor($tut, $g);
        $group = $this->entity($g);

        $this->assertFalse(GroupManager::canUserSubscribe($u2, $group), 'full (1/1)');
        $this->assertTrue(GroupManager::canUserSubscribe($u2, $group, false), 'capacity check skipped');
        $this->assertFalse(GroupManager::canUserSubscribe($u1, $group, false), 'already subscribed');
        $this->assertFalse(GroupManager::canUserSubscribe($tut, $group, false), 'tutor of the group');
    }

    public function testSubscribeTutorsInsertsAndRefusesExistingMembersAndTutors(): void
    {
        $g = $this->makeGroup('subs_tut', null, 1);
        $t1 = $this->makeUser('tt1');
        $t2 = $this->makeUser('tt2');
        $member = $this->makeUser('ttm');
        $this->member($member, $g);
        $group = $this->entity($g);

        // Fixed: used to return the last Database::query() Result object; now a bool like subscribeUsers().
        $this->assertTrue(GroupManager::subscribeTutors([$t1, $member, $t1], $group));

        $this->assertSame([$t1], $this->tutorIds($g), 'capacity ignored (group is full), member refused, duplicate refused');
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT c_id FROM c_group_rel_tutor WHERE group_id = ?', [$g]));

        $this->assertTrue(GroupManager::subscribeTutors($t2, $group, 2));
        $this->assertSame([$t1, $t2], $this->tutorIds($g));
        $this->assertSame(2, (int) $this->conn()->fetchOne('SELECT c_id FROM c_group_rel_tutor WHERE user_id = ?', [$t2]));
        $this->assertFalse(GroupManager::subscribeTutors([$t2], null));
    }

    public function testUnsubscribeUsersOnlyTouchesGivenUsersGroupAndCourse(): void
    {
        $g = $this->makeGroup('unsub');
        $g2 = $this->makeGroup('unsub_other');
        [$u1, $u2, $u3] = [$this->makeUser('un1'), $this->makeUser('un2'), $this->makeUser('un3')];
        $this->member($u1, $g);
        $this->member($u2, $g);
        $this->member($u3, $g, 2);
        $this->member($u1, $g2);
        $group = $this->entity($g);

        $this->assertTrue(GroupManager::unsubscribeUsers([$u1, $u3, 0], $group));

        $this->assertSame([$u2, $u3], $this->memberIds($g), 'u3 is in course 2: untouched by a course-1 unsubscribe');
        $this->assertSame([$u1], $this->memberIds($g2), 'other groups untouched');

        $this->assertTrue(GroupManager::unsubscribeUsers([$u3], $group, 2));
        $this->assertSame([$u2], $this->memberIds($g));
        $this->assertTrue(GroupManager::unsubscribeUsers([], $group));
        $this->assertFalse(GroupManager::unsubscribeUsers([$u2], null));
    }

    public function testUnsubscribeAllUsersIgnoresCourseAndLeavesTutors(): void
    {
        $g = $this->makeGroup('unsub_all');
        $g2 = $this->makeGroup('unsub_all_other');
        [$u1, $u2, $u3] = [$this->makeUser('ua1'), $this->makeUser('ua2'), $this->makeUser('ua3')];
        $this->member($u1, $g, 1);
        $this->member($u2, $g, 2);
        $this->member($u3, $g2);
        $this->tutor($u1, $g);

        $this->assertNotFalse(GroupManager::unsubscribeAllUsers($g));

        $this->assertSame([], $this->memberIds($g), 'WHERE group_id only: rows of every course go');
        $this->assertSame([$u3], $this->memberIds($g2));
        $this->assertSame([$u1], $this->tutorIds($g));
        $this->assertFalse(GroupManager::unsubscribeAllUsers(0));
    }

    public function testUnsubscribeAllTutors(): void
    {
        $g = $this->makeGroup('unsub_tut');
        $g2 = $this->makeGroup('unsub_tut_other');
        [$t1, $t2] = [$this->makeUser('ut1'), $this->makeUser('ut2')];
        $this->tutor($t1, $g);
        $this->tutor($t2, $g, 2);
        $this->tutor($t1, $g2);
        $this->member($t2, $g);

        $this->assertTrue(GroupManager::unsubscribe_all_tutors($g));

        $this->assertSame([], $this->tutorIds($g));
        $this->assertSame([$t1], $this->tutorIds($g2));
        $this->assertSame([$t2], $this->memberIds($g), 'members untouched');
        $this->assertFalse(GroupManager::unsubscribe_all_tutors(0));

        ChamiloSession::erase('_real_cid');
        $this->assertFalse(GroupManager::unsubscribe_all_tutors($g2), 'needs a course context');
        $this->assertSame([$t1], $this->tutorIds($g2));
    }

    public function testGetSubscribedUsersOrderingAndShape(): void
    {
        $g = $this->makeGroup('subscribed');
        $u1 = $this->makeUser('zed');
        $u2 = $this->makeUser('abe');
        $this->member($u1, $g);
        $this->member($u2, $g);
        $this->tutor($this->makeUser('tutx'), $g);

        $list = GroupManager::get_subscribed_users($this->entity($g));

        $this->assertEqualsCanonicalizing([$u1, $u2], array_keys($list), 'keyed by user id, tutors excluded');
        $this->assertSame(['user_id', 'firstname', 'lastname', 'email', 'username'], array_keys($list[$u1]));
        $this->assertSame('Fzed', $list[$u1]['firstname']);
        $this->assertSame('Lzed', $list[$u1]['lastname']);
        // api_sort_by_first_name() cannot be driven from a test: it is not a setting, and its per-language lookup
        // (_api_get_person_name_convention) ends up reading the LAST language of LanguageFixtures whatever the locale
        // (the $iso parameter is overwritten by its own loop), so only one of the two orders can ever be exercised
        // here. Assert the order the function currently selects; the official-code order below is fully
        // deterministic through its setting.
        $this->assertSame(
            api_sort_by_first_name() ? [$u2, $u1] : [$u1, $u2],
            array_keys($list),
            'first name ASC when sorting by first name; lastname DESC in the last-name-first mode'
        );
    }

    public function testGetSubscribedUsersOrdersByOfficialCodeWhenTheSettingIsOn(): void
    {
        $g = $this->makeGroup('subscribed_oc');
        // Official codes are the reverse of both the first-name and the last-name orders.
        $u1 = $this->makeUser('abe');
        $u2 = $this->makeUser('zed');
        $this->member($u1, $g);
        $this->member($u2, $g);
        $this->conn()->executeStatement('UPDATE user SET official_code = ? WHERE id = ?', ['gm_oc_2', $u1]);
        $this->conn()->executeStatement('UPDATE user SET official_code = ? WHERE id = ?', ['gm_oc_1', $u2]);

        $this->setSetting('display.order_user_list_by_official_code', 'true');
        $this->assertSame([$u2, $u1], array_keys(GroupManager::get_subscribed_users($this->entity($g))), 'official code ASC wins');

        $this->setSetting('display.order_user_list_by_official_code', 'false');
        $this->assertSame(
            api_sort_by_first_name() ? [$u1, $u2] : [$u2, $u1],
            array_keys(GroupManager::get_subscribed_users($this->entity($g))),
            'setting off: back to the name order'
        );
    }

    public function testIsSelfRegistrationAndUnregistrationAllowed(): void
    {
        $open = $this->makeGroup('selfreg_open', null, 0, ['self_registration_allowed' => 1, 'self_unregistration_allowed' => 1]);
        $closed = $this->makeGroup('selfreg_closed');
        $disabled = $this->makeGroup('selfreg_disabled', null, 0, ['status' => 0, 'self_registration_allowed' => 1, 'self_unregistration_allowed' => 1]);
        $u = $this->makeUser('sr');
        $this->member($u, $open);
        $this->member($u, $disabled);
        $this->member($u, $closed);

        $this->assertFalse(GroupManager::is_self_registration_allowed($u, $this->entity($open)), 'already subscribed');
        $this->assertFalse(GroupManager::is_self_registration_allowed($u, $this->entity($closed)));
        $this->assertFalse(GroupManager::is_self_registration_allowed($u, $this->entity($disabled)), 'status 0');
        $this->assertFalse(GroupManager::is_self_registration_allowed(0, $this->entity($open)));
        $newcomer = $this->makeUser('sr2');
        $this->assertTrue(GroupManager::is_self_registration_allowed($newcomer, $this->entity($open)));

        $this->assertTrue(GroupManager::is_self_unregistration_allowed($u, $this->entity($open)));
        $this->assertFalse(GroupManager::is_self_unregistration_allowed($newcomer, $this->entity($open)), 'not a member');
        $this->assertFalse(GroupManager::is_self_unregistration_allowed($u, $this->entity($closed)));
        $this->assertFalse(GroupManager::is_self_unregistration_allowed($u, $this->entity($disabled)));
    }

    public function testIsUserInGroupLegacyArrayVariant(): void
    {
        $g = $this->makeGroup('isuig');
        $m = $this->makeUser('m');
        $t = $this->makeUser('t');
        $x = $this->makeUser('x');
        $this->member($m, $g);
        $this->tutor($t, $g);
        $this->fresh();

        $this->assertTrue(GroupManager::is_user_in_group($m, ['id' => $g]));
        $this->assertTrue(GroupManager::is_user_in_group($t, ['id' => $g]));
        $this->assertFalse(GroupManager::is_user_in_group($x, ['id' => $g]));
        $this->assertFalse(GroupManager::is_user_in_group($m, []));
    }
}
