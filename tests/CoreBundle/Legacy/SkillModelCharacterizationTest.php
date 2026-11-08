<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use SkillModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization of the raw-SQL half of SkillModel (public/main/inc/lib/SkillModel.php): the skill
 * listing, which users acquired which skills (per course/session), the gradebook <-> skill links and
 * the access predicates (tool setting, who may see a student's skills).
 *
 * LIVE (callers under public/): getAllSkills, getGradebooksBySkill, getUserSkills, getUserSkillRanking,
 * getCoursesBySkill, getSessionsBySkill, userHasSkill, listAchievedByCourse, listUsersWhoAchieved,
 * isToolAvailable, isAllowed, hasAccessToUserSkill, userCanAddFeedbackToUser, sanitizeInternalUrl.
 * DEAD (no caller, marked @deprecated in the source): getUserListSkillRanking,
 * getUserListSkillRankingCount, getCountSkillsByCourse, isSearched. The remaining methods (tree
 * building, add/edit/delete, saveSkills*, getItemInfo, ...) go through Doctrine entities, not raw SQL.
 *
 * Fixtures: skills (title sk_...) and their relations, gradebook categories, sessions and acquired
 * skills are raw INSERTs; users 1 (admin) and 2 (anonymous), courses 1 (AIACT) and 2 (USINGCHAMILO).
 */
final class SkillModelCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;
    /** @var array<string, string> */
    private array $originalSettings = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/skills/skill_list.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        \ChamiloSession::erase('_user');
    }

    protected function tearDown(): void
    {
        foreach ($this->originalSettings as $name => $value) {
            Container::getSettingsManager()->updateSetting($name, $value);
        }
        \ChamiloSession::erase('_user');
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- fixtures

    private function skill(string $name, ?int $parent = null, bool $link = true): int
    {
        ++$this->seq;
        $this->conn->executeStatement(
            "INSERT INTO skill (title, short_code, description, access_url_id, icon, status, acquirable, updated_at)
             VALUES (?, ?, 'd', 1, '', 1, 1, '2001-01-01 00:00:00')",
            ['sk_'.$name.'_'.$this->seq, 'sk'.$this->seq]
        );
        $id = (int) $this->conn->lastInsertId();
        if ($link) {
            $this->conn->executeStatement(
                'INSERT INTO skill_rel_skill (skill_id, parent_id, relation_type, level) VALUES (?, ?, 0, 0)',
                [$id, $parent ?? 1]
            );
        }

        return $id;
    }

    private function gradebook(int $courseId, ?int $sessionId = null): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_category (c_id, session_id, title, weight, visible, locked, generate_certificates,
                is_requirement, calculation_mode)
             VALUES (?, ?, ?, 100, 1, 0, 0, 0, 'weighted')",
            [$courseId, $sessionId, 'sk_gb_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $skillId, int $gradebookId): void
    {
        $this->conn->executeStatement(
            "INSERT INTO skill_rel_gradebook (skill_id, gradebook_id, type) VALUES (?, ?, 'all')",
            [$skillId, $gradebookId]
        );
    }

    private function session(string $title): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position)
             VALUES (?, 0, 0, 0, 1, 0, 0)',
            ['sk_'.$title.'_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function acquire(int $userId, int $skillId, ?int $courseId = null, ?int $sessionId = null, int $status = 1, string $at = '2010-05-05 10:00:00'): int
    {
        $this->conn->executeStatement(
            "INSERT INTO skill_rel_user (user_id, skill_id, course_id, session_id, status, acquired_skill_at,
                validation_status, argumentation, argumentation_author_id)
             VALUES (?, ?, ?, ?, ?, ?, 0, '', 0)",
            [$userId, $skillId, $courseId, $sessionId, $status, $at]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function setSetting(string $name, string $value): void
    {
        $this->originalSettings[$name] ??= (string) api_get_setting($name);
        Container::getSettingsManager()->updateSetting($name, $value);
    }

    // ---------------------------------------------------------------- getAllSkills

    public function testGetAllSkillsListsLinkedSkillsKeyedByIdWithTheirLevelAndGradebooks(): void
    {
        $a = $this->skill('a');
        $b = $this->skill('b', $a);
        $gb = $this->gradebook(1);
        $this->link($b, $gb);

        $all = (new SkillModel())->getAllSkills();

        $this->assertArrayHasKey($a, $all);
        $this->assertSame('sk_a_1', $all[$a]['title']);
        $this->assertSame($a, (int) $all[$b]['parent_id']);
        // level = number of ancestors that have a relation row, minus one: A hangs from the root only.
        $this->assertSame(0, $all[$a]['level']);
        $this->assertSame(1, $all[$b]['level']);
        $this->assertSame([], $all[$a]['gradebooks']);
        $this->assertSame($gb, (int) $all[$b]['gradebooks'][0]['id']);
        $this->assertSame('', $all[$a]['asset']);
        $this->assertSame('sk1', $all[$a]['short_code']);
        $this->assertSame('sk2', $all[$b]['short_code']);
    }

    public function testGetAllSkillsDoesNotListASkillWithoutARelationRow(): void
    {
        $orphan = $this->skill('orphan', null, false);

        // PINNED: the listing INNER JOINs skill_rel_skill, so a skill with no relation row (the installed
        // "Root" skill included) is invisible.
        $this->assertArrayNotHasKey($orphan, (new SkillModel())->getAllSkills());
        $this->assertArrayNotHasKey(1, (new SkillModel())->getAllSkills());
    }

    public function testGetAllSkillsWithAnIdReturnsOnlyThatSkill(): void
    {
        $a = $this->skill('a');
        $b = $this->skill('b');

        $one = (new SkillModel())->getAllSkills(false, false, $b);

        $this->assertSame([$b], array_keys($one));
        $this->assertNotContains($a, array_keys($one));
    }

    public function testGetAllSkillsIsOrderedByTheRelationRowId(): void
    {
        $first = $this->skill('first');
        $second = $this->skill('second');
        $third = $this->skill('third');

        $ids = array_keys((new SkillModel())->getAllSkills());

        $this->assertSame([$first, $second, $third], array_values(array_intersect($ids, [$first, $second, $third])));
    }

    public function testGetAllSkillsWithAParentIdLoadsAllTheDescendants(): void
    {
        $a = $this->skill('a');
        $b = $this->skill('b', $a);
        $c = $this->skill('c', $a);
        $d = $this->skill('d', $b);
        $other = $this->skill('other');

        $rows = (new SkillModel())->getAllSkills(false, false, null, $a);

        // Direct children first, then the children of each of them (the recursion used to call a
        // method with another signature and throw a TypeError as soon as a child existed).
        $this->assertSame([$b, $c, $d], array_keys($rows));
        $this->assertArrayNotHasKey($other, $rows);
        $this->assertSame(2, $rows[$d]['level']);
    }

    public function testGetAllSkillsWithIdAndParentKeepsBothFilters(): void
    {
        $a = $this->skill('a');
        $b = $this->skill('b', $a);
        $this->skill('c', $a);

        $rows = (new SkillModel())->getAllSkills(false, false, $b, $a);

        $this->assertSame([$b], array_keys($rows));
    }

    public function testGetAllSkillsWithAParentIdAndNoChildrenIsEmpty(): void
    {
        $a = $this->skill('a');

        $this->assertSame([], (new SkillModel())->getAllSkills(false, false, null, $a));
    }

    // ---------------------------------------------------------------- gradebooks / courses / sessions of a skill

    public function testGetGradebooksBySkillReturnsTheLinkedGradebookCategories(): void
    {
        $s = $this->skill('s');
        $other = $this->skill('o');
        $g1 = $this->gradebook(1);
        $g2 = $this->gradebook(2, null);
        $this->link($s, $g1);
        $this->link($s, $g2);
        $this->link($other, $this->gradebook(1));

        $rows = (new SkillModel())->getGradebooksBySkill($s);

        $this->assertEqualsCanonicalizing([$g1, $g2], array_map(static fn (array $r): int => (int) $r['id'], $rows));
        $this->assertSame([], (new SkillModel())->getGradebooksBySkill(999999));
    }

    public function testGetCoursesBySkillJoinsOnAGradebookColumnThatNoLongerExists(): void
    {
        $s = $this->skill('s');
        $this->link($s, $this->gradebook(1));

        // PINNED QUIRK: gradebook_category has no course_code column any more (it has c_id), so the
        // query fails; the skill pages that list "courses where this skill can be achieved" break.
        $this->expectException(\Throwable::class);

        (new SkillModel())->getCoursesBySkill($s);
    }

    public function testGetSessionsBySkillListsSessionGradebooksOnly(): void
    {
        $s = $this->skill('s');
        $session = $this->session('sess');
        $this->link($s, $this->gradebook(1, $session));
        $this->link($s, $this->gradebook(1, null));

        $rows = (new SkillModel())->getSessionsBySkill($s);

        $this->assertCount(1, $rows);
        $this->assertSame($session, (int) $rows[0]['id']);
        $this->assertStringStartsWith('sk_sess_', $rows[0]['title']);
    }

    // ---------------------------------------------------------------- user skills

    public function testGetUserSkillsReturnsOnlyTheAcquiredSkillsOfThatUser(): void
    {
        $a = $this->skill('a');
        $b = $this->skill('b');
        $removed = $this->skill('removed');
        $this->acquire(1, $a);
        $this->acquire(1, $b);
        $this->acquire(1, $removed, null, null, SkillRelUser::STATUS_REMOVED);
        $this->acquire(2, $this->skill('theirs'));

        $skills = (new SkillModel())->getUserSkills(1);

        $this->assertSame([$a => $a, $b => $b], array_intersect_key($skills, [$a => 1, $b => 1, $removed => 1]));
        $this->assertArrayNotHasKey($removed, $skills);
    }

    public function testGetUserSkillsFiltersByCourseAndSession(): void
    {
        $inC1 = $this->skill('c1');
        $inC2 = $this->skill('c2');
        $inSession = $this->skill('sess');
        $session = $this->session('s');
        $this->acquire(1, $inC1, 1);
        $this->acquire(1, $inC2, 2);
        $this->acquire(1, $inSession, 1, $session);
        $mine = [$inC1 => 1, $inC2 => 1, $inSession => 1];

        $this->assertEqualsCanonicalizing(
            [$inC1, $inSession],
            array_keys(array_intersect_key((new SkillModel())->getUserSkills(1, false, 1), $mine))
        );
        $this->assertSame(
            [$inSession],
            array_keys(array_intersect_key((new SkillModel())->getUserSkills(1, false, 1, $session), $mine))
        );
        // A session id without course id still filters on the session.
        $this->assertSame(
            [$inSession],
            array_keys(array_intersect_key((new SkillModel())->getUserSkills(1, false, 0, $session), $mine))
        );
    }

    public function testGetUserSkillsDedupesTheSameSkillAcquiredTwice(): void
    {
        $a = $this->skill('a');
        $this->acquire(1, $a, 1, null, 1, '2010-01-01 00:00:00');
        $this->acquire(1, $a, 2, null, 1, '2011-01-01 00:00:00');

        $skills = (new SkillModel())->getUserSkills(1);

        // The result is keyed by skill id, so only one entry exists whatever the number of issues.
        $this->assertSame($a, $skills[$a]);
        $this->assertSame(1, \count(array_keys($skills, $a, true)));
    }

    public function testGetUserSkillRankingCountsTheAcquiredSkills(): void
    {
        $a = $this->skill('a');
        $b = $this->skill('b');
        $before = (int) (new SkillModel())->getUserSkillRanking(1);
        $this->acquire(1, $a);
        $this->acquire(1, $b);
        $this->acquire(1, $this->skill('r'), null, null, SkillRelUser::STATUS_REMOVED);
        $this->acquire(2, $this->skill('o'));

        $this->assertSame($before + 2, (int) (new SkillModel())->getUserSkillRanking(1));
    }

    public function testUserHasSkillWithoutCourseLooksAtAnyIssue(): void
    {
        $a = $this->skill('a');
        $b = $this->skill('b');
        $this->acquire(1, $a, 1);
        $this->acquire(1, $b, null, null, SkillRelUser::STATUS_REMOVED);

        $model = new SkillModel();
        $this->assertTrue($model->userHasSkill(1, $a));
        $this->assertFalse($model->userHasSkill(1, $b));
        $this->assertFalse($model->userHasSkill(2, $a));
    }

    public function testUserHasSkillWithACourseWithoutSessionRequiresASessionlessIssue(): void
    {
        $a = $this->skill('a');
        $b = $this->skill('b');
        $session = $this->session('s');
        $this->acquire(1, $a, 1, null);
        $this->acquire(1, $b, 1, $session);

        $model = new SkillModel();
        $this->assertTrue($model->userHasSkill(1, $a, 1));
        $this->assertFalse($model->userHasSkill(1, $a, 2));
        // Acquired inside a session: not "in the course" (session id NULL required).
        $this->assertFalse($model->userHasSkill(1, $b, 1));
        $this->assertTrue($model->userHasSkill(1, $b, 1, $session));
        $this->assertFalse($model->userHasSkill(1, $a, 1, $session));
    }

    // ---------------------------------------------------------------- achieved lists

    public function testListAchievedByCourseListsTheUsersWhoAcquiredSkillsInThatCourse(): void
    {
        $a = $this->skill('a');
        $this->acquire(1, $a, 1, null, 1, '2010-05-05 10:00:00');
        $this->acquire(2, $a, 2);
        $this->acquire(1, $this->skill('removed'), 1, null, SkillRelUser::STATUS_REMOVED);

        $rows = (new SkillModel())->listAchievedByCourse(1);

        $mine = array_values(array_filter($rows, static fn (array $r): bool => (int) $r['skill_id'] === $a));
        $this->assertCount(1, $mine);
        $this->assertSame('admin', $mine[0]['username']);
        $this->assertSame('AIACT', $this->conn->fetchOne('SELECT code FROM course WHERE id = ?', [(int) $mine[0]['c_id']]));
        $this->assertSame('sk_a_1', $mine[0]['skill_title']);
        $this->assertSame('2010-05-05 10:00:00', $mine[0]['acquired_skill_at']);
        foreach ($rows as $row) {
            $this->assertSame(1, (int) $row['c_id']);
        }
        $this->assertSame([], (new SkillModel())->listAchievedByCourse(0));
    }

    public function testListUsersWhoAchievedAGivenSkillAcrossCourses(): void
    {
        $a = $this->skill('a');
        $b = $this->skill('b');
        $this->acquire(1, $a, 1);
        $this->acquire(2, $a, 2);
        $this->acquire(1, $b, 1);
        $this->acquire(1, $a, 1, null, SkillRelUser::STATUS_REMOVED);

        $rows = (new SkillModel())->listUsersWhoAchieved($a);

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(['admin', 'anon'], array_column($rows, 'username'));
        $this->assertSame([], (new SkillModel())->listUsersWhoAchieved(0));
    }

    public function testListAchievedSkipsIssuesWithoutACourse(): void
    {
        $a = $this->skill('a');
        $this->acquire(1, $a, null);

        // INNER JOIN on course: an issue with no course (e.g. a badge assigned by hand) is not listed.
        $this->assertSame([], (new SkillModel())->listUsersWhoAchieved($a));
    }

    // ---------------------------------------------------------------- dead helpers

    public function testGetCountSkillsByCourseCountsTheSkillGradebookLinksOfTheCourse(): void
    {
        $s = $this->skill('s');
        $t = $this->skill('t');
        $this->link($s, $this->gradebook(1));
        $this->link($t, $this->gradebook(1));
        $this->link($s, $this->gradebook(2));
        $model = new SkillModel();
        $c1 = (int) $model->getCountSkillsByCourse('AIACT');

        $this->assertGreaterThanOrEqual(2, $c1);
        $this->assertSame(0, (int) $model->getCountSkillsByCourse('SK_NO_SUCH_COURSE'));
    }

    public function testGetUserListSkillRankingOrdersUsersByAcquiredSkills(): void
    {
        $this->acquire(1, $this->skill('a'));
        $this->acquire(1, $this->skill('b'));
        $this->acquire(2, $this->skill('c'));

        $rows = (new SkillModel())->getUserListSkillRanking(0, 10, 'user_id', 'desc', '');

        $this->assertSame(1, (int) $rows[0]['user_id']);
        $this->assertSame(2, (int) $rows[0]['skills_acquired']);
        $this->assertSame(1, (int) $rows[0]['rank']);
        $this->assertGreaterThanOrEqual(2, (int) (new SkillModel())->getUserListSkillRankingCount());
    }

    public function testIsSearchedLooksAtTheSkillProfiles(): void
    {
        $a = $this->skill('a');
        $b = $this->skill('b');
        $this->conn->executeStatement("INSERT INTO skill_profile (title, description) VALUES ('sk_profile', '')");
        $profile = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement('INSERT INTO skill_rel_profile (skill_id, profile_id) VALUES (?, ?)', [$a, $profile]);

        $this->assertTrue(SkillModel::isSearched($a));
        $this->assertFalse(SkillModel::isSearched($b));
        $this->assertFalse(SkillModel::isSearched(0));
    }

    // ---------------------------------------------------------------- access control

    public function testIsToolAvailableFollowsTheAllowSkillsToolSetting(): void
    {
        $this->setSetting('skill.allow_skills_tool', 'false');
        $this->assertFalse(SkillModel::isToolAvailable());

        $this->setSetting('skill.allow_skills_tool', 'true');
        $this->assertTrue(SkillModel::isToolAvailable());
    }

    public function testHasAccessToUserSkillLetsAUserSeeTheirOwnSkillsOnlyWhileTheToolIsOn(): void
    {
        $this->setSetting('skill.allow_skills_tool', 'true');
        $this->assertTrue(SkillModel::hasAccessToUserSkill(5, 5));
        $this->assertFalse(SkillModel::hasAccessToUserSkill(5, 6));

        $this->setSetting('skill.allow_skills_tool', 'false');
        $this->assertFalse(SkillModel::hasAccessToUserSkill(5, 5));
    }

    public function testIsAllowedForAStudentIsLimitedToOwnSkillsAndNeverBlocksWhenAskedNotTo(): void
    {
        $this->setSetting('skill.allow_skills_tool', 'true');
        \ChamiloSession::write('_user', ['id' => 5]);

        $this->assertTrue(SkillModel::isAllowed(5, false));
        $this->assertFalse(SkillModel::isAllowed(6, false));
        // No student id and not an administrator: nothing to show.
        $this->assertFalse(SkillModel::isAllowed(0, false));

        $this->setSetting('skill.allow_skills_tool', 'false');
        $this->assertFalse(SkillModel::isAllowed(5, false));
    }

    public function testUserCanAddFeedbackToUserIsRefusedToPlainUsers(): void
    {
        $from = $this->getUser('anon');
        $to = $this->getAdmin();

        // anon is neither admin, session admin, student boss nor DRH.
        $this->assertFalse(SkillModel::userCanAddFeedbackToUser($from, $to));
    }

    public function testSanitizeInternalUrlOnlyKeepsRelativePaths(): void
    {
        $this->assertNull(SkillModel::sanitizeInternalUrl(null));
        $this->assertNull(SkillModel::sanitizeInternalUrl(''));
        $this->assertNull(SkillModel::sanitizeInternalUrl('https://evil.test/x'));
        $this->assertNull(SkillModel::sanitizeInternalUrl('//evil.test/x'));
        $this->assertNull(SkillModel::sanitizeInternalUrl('javascript:alert(1)'));
        $this->assertNull(SkillModel::sanitizeInternalUrl("/ok\r\nLocation: x"));
        $this->assertSame('/main/skills/issued.php?id=1', SkillModel::sanitizeInternalUrl('/main/skills/issued.php?id=1'));
        $this->assertSame('/main/x', SkillModel::sanitizeInternalUrl(' main/x '));
    }
}
