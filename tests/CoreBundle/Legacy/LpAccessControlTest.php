<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use learnpath;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

/**
 * The legacy learning path pages (lp_controller.php, lp_add_category.php, lp_stats.php, lp.ajax.php) take learning
 * path, item and category ids from the request. The guard helpers of the learnpath class must prove the id belongs to
 * the CURRENT course (behavioural tests below); the procedural page guards (roles, tokens) are source-level pins,
 * weak by nature but they fail when a guard is removed. Fixtures: prepared inserts, tag "lc2_".
 */
final class LpAccessControlTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE_ID = 1;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/lp/lp_controller.php');
        $request->setSession($session);
        Container::setRequest($request);
        $session->set('_real_cid', self::COURSE_ID);
        $session->set('_cid', 'TEMP');
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function node(string $title, int $type): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO resource_node (resource_type_id, title, slug, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, NOW(), NOW(), 0, ?)',
            [$type, $title, $title.'-'.uniqid(), Uuid::v4()->toBinary()]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function link(int $nodeId, int $courseId, int $type): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, NULL, 2, 0, ?, NOW(), NOW())',
            [$nodeId, $courseId, $type]
        );
    }

    private function otherCourse(): int
    {
        return (int) $this->conn()->fetchOne('SELECT MIN(id) FROM course WHERE id <> ?', [self::COURSE_ID]);
    }

    /**
     * @return array{int, int} lp id, root item id
     */
    private function lp(int $courseId): array
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on)
             VALUES (1, 'lc2_lp', '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, '2030-01-01 00:00:00', '2030-01-01 00:00:00')"
        );
        $lpId = (int) $this->conn()->lastInsertId();
        $node = $this->node('lc2_lp', 40);
        $this->conn()->executeStatement('UPDATE c_lp SET resource_node_id = ? WHERE iid = ?', [$node, $lpId]);
        $this->link($node, $courseId, 40);

        $em = $this->getEntityManager();
        $root = (new CLpItem())
            ->setTitle('root')
            ->setPath('root')
            ->setLp($em->find(CLp::class, $lpId))
            ->setItemType('root')
        ;
        $em->persist($root);
        $em->flush();
        $rootId = (int) $root->getIid();
        $em->clear();

        return [$lpId, $rootId];
    }

    private function category(int $courseId): int
    {
        $node = $this->node('lc2_cat', 41);
        $this->conn()->executeStatement('INSERT INTO c_lp_category (resource_node_id, title) VALUES (?, ?)', [$node, 'lc2_cat']);
        $id = (int) $this->conn()->lastInsertId();
        $this->link($node, $courseId, 41);

        return $id;
    }

    private int $seq = 0;

    private function user(): int
    {
        $name = 'lc2_user_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn()->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname, official_code)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', 5, 1, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', 'F', 'L', ?)",
            [$name, $name, $name.'@example.com', $name.'@example.com', UuidV4::v4()->toBinary(), 'LC'.$this->seq]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function subscribe(int $userId, int $courseId, int $status): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, status, relation_type, sort, user_course_cat, is_tutor, progress)
             VALUES (?, ?, ?, 0, 0, 0, 0, 0)',
            [$userId, $courseId, $status]
        );
    }

    private function relation(int $userId, int $friendId, int $type): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO user_rel_user (user_id, friend_user_id, relation_type, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())',
            [$userId, $friendId, $type]
        );
    }

    /**
     * Session with course 1 where $studentId is a student.
     */
    private function sessionWithStudent(int $studentId): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status)
             VALUES ('lc2_session_".uniqid()."', 1, 1, 0, 1, 0)"
        );
        $sid = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, 0, 1)', [$sid, self::COURSE_ID]);
        $this->sessionUser($sid, $studentId, SessionEntity::STUDENT);
        $this->sessionCourseUser($sid, $studentId, SessionEntity::STUDENT);

        return $sid;
    }

    private function sessionUser(int $sid, int $userId, int $type): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at)
             VALUES (?, ?, ?, 0, '2001-01-01 00:00:00')",
            [$sid, $userId, $type]
        );
    }

    private function sessionCourseUser(int $sid, int $userId, int $status): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, user_id, c_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$sid, $userId, self::COURSE_ID, $status]
        );
    }

    private function source(string $path): string
    {
        $code = file_get_contents(__DIR__.'/../../../'.$path);
        $this->assertNotFalse($code);

        return $code;
    }

    /**
     * The source of one `case 'name':` block of lp_controller.php (up to the next case at the same indentation).
     */
    private function caseBlock(string $case): string
    {
        $code = $this->source('public/main/lp/lp_controller.php');
        $code = substr($code, (int) strpos($code, "\$redirectTo = '';"));
        $start = strpos($code, "\n    case '".$case."':");
        $this->assertNotFalse($start, "case $case exists");
        $end = strpos($code, "\n    case '", $start + 5);
        $end = false === $end ? strpos($code, "\n    default:", $start) : $end;

        return substr($code, $start, $end - $start);
    }

    // ------------------------------------------------------------- behavioural guards

    // ------------------------------------------------------------- who may read the stats of another user

    public function testStatsViewerRules(): void
    {
        $student = $this->user();
        $classmate = $this->user();
        $this->subscribe($student, self::COURSE_ID, 5);
        $this->subscribe($classmate, self::COURSE_ID, 5);

        $this->assertTrue(learnpath::mayViewStatsOfUser($student, $student, self::COURSE_ID), 'own statistics');
        $this->assertFalse(learnpath::mayViewStatsOfUser($classmate, $student, self::COURSE_ID), 'a plain course member');
        $this->assertFalse(learnpath::mayViewStatsOfUser(0, $student, self::COURSE_ID));
        $this->assertFalse(learnpath::mayViewStatsOfUser($classmate, 0, self::COURSE_ID));
    }

    public function testCourseTeacherMayViewButOnlyInHisCourse(): void
    {
        $student = $this->user();
        $teacher = $this->user();
        $this->subscribe($student, self::COURSE_ID, 5);
        $this->subscribe($teacher, self::COURSE_ID, 1);

        $this->assertTrue(learnpath::mayViewStatsOfUser($teacher, $student, self::COURSE_ID));
        $this->assertFalse(learnpath::mayViewStatsOfUser($teacher, $student, $this->otherCourse()), 'not a teacher there');
        $stranger = $this->user();
        $this->assertFalse(learnpath::mayViewStatsOfUser($teacher, $stranger, self::COURSE_ID), 'a user who is not in the course');
    }

    public function testStudentBossMayView(): void
    {
        $student = $this->user();
        $boss = $this->user();
        $other = $this->user();
        $this->subscribe($student, self::COURSE_ID, 5);
        $this->relation($student, $boss, UserRelUser::USER_RELATION_TYPE_BOSS);

        $this->assertTrue(learnpath::mayViewStatsOfUser($boss, $student, self::COURSE_ID));
        $this->assertFalse(learnpath::mayViewStatsOfUser($other, $student, self::COURSE_ID));
        $this->assertFalse(learnpath::mayViewStatsOfUser($boss, $student, $this->otherCourse()), 'the user does not follow that course');
        $this->assertFalse(learnpath::mayViewStatsOfUser($student, $boss, self::COURSE_ID), 'the relation is not symmetric');
    }

    public function testHrManagerFollowingTheUserMayViewAndAnotherHrManagerMayNot(): void
    {
        $student = $this->user();
        $hrm = $this->user();
        $otherHrm = $this->user();
        $this->subscribe($student, self::COURSE_ID, 5);
        $this->relation($student, $hrm, UserRelUser::USER_RELATION_TYPE_RRHH);

        $this->assertTrue(learnpath::mayViewStatsOfUser($hrm, $student, self::COURSE_ID));
        $this->assertFalse(learnpath::mayViewStatsOfUser($otherHrm, $student, self::COURSE_ID));
        $this->assertFalse(learnpath::mayViewStatsOfUser($hrm, $student, $this->otherCourse()), 'a course the user is not in');
    }

    public function testSessionCourseCoachMayViewTheStudentsOfThatSession(): void
    {
        $student = $this->user();
        $coach = $this->user();
        $outsider = $this->user();
        $sid = $this->sessionWithStudent($student);
        $this->sessionCourseUser($sid, $coach, SessionEntity::COURSE_COACH);

        $this->assertTrue(learnpath::mayViewStatsOfUser($coach, $student, self::COURSE_ID, $sid));
        $this->assertTrue(learnpath::mayViewStatsOfUser($coach, $student, self::COURSE_ID));
        $this->assertFalse(learnpath::mayViewStatsOfUser($outsider, $student, self::COURSE_ID));
        $this->assertFalse(learnpath::mayViewStatsOfUser($coach, $student, self::COURSE_ID, $sid + 1000000), 'another session');
    }

    public function testCoachOfAnotherCourseOfTheSameSessionDoesNotQualify(): void
    {
        $student = $this->user();
        $coach = $this->user();
        $sid = $this->sessionWithStudent($student);
        $otherCourse = $this->otherCourse();
        $this->conn()->executeStatement('INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, 0, 1)', [$sid, $otherCourse]);
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, user_id, c_id, status, visibility, legal_agreement, progress) VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$sid, $coach, $otherCourse, SessionEntity::COURSE_COACH]
        );

        $this->assertFalse(learnpath::isCoachOfLearnerInCourse($coach, $student, self::COURSE_ID), 'coach of course B only');
        $this->assertFalse(learnpath::mayViewStatsOfUser($coach, $student, self::COURSE_ID));
    }

    public function testCoachOfAnotherSessionDoesNotQualifyWhenASessionIsRequested(): void
    {
        $student = $this->user();
        $courseCoach = $this->user();
        $generalCoach = $this->user();
        $sidA = $this->sessionWithStudent($this->user());
        $sidB = $this->sessionWithStudent($student);
        foreach ([$sidA, $sidB] as $sid) {
            $this->sessionCourseUser($sid, $courseCoach, SessionEntity::COURSE_COACH);
            $this->sessionUser($sid, $generalCoach, SessionEntity::GENERAL_COACH);
        }

        $this->assertTrue(learnpath::isCoachOfLearnerInCourse($courseCoach, $student, self::COURSE_ID, $sidB));
        $this->assertFalse(learnpath::isCoachOfLearnerInCourse($courseCoach, $student, self::COURSE_ID, $sidA), 'the student is not in session A');
        $this->assertTrue(learnpath::isCoachOfLearnerInCourse($generalCoach, $student, self::COURSE_ID, $sidB));
        $this->assertFalse(learnpath::isCoachOfLearnerInCourse($generalCoach, $student, self::COURSE_ID, $sidA));
    }

    public function testUserIsLearnerOfCourseRules(): void
    {
        $direct = $this->user();
        $inSession = $this->user();
        $teacher = $this->user();
        $this->subscribe($direct, self::COURSE_ID, 5);
        $sid = $this->sessionWithStudent($inSession);
        $this->subscribe($teacher, self::COURSE_ID, 1);

        $this->assertTrue(learnpath::userIsLearnerOfCourse($direct, self::COURSE_ID));
        $this->assertFalse(learnpath::userIsLearnerOfCourse($direct, self::COURSE_ID, $sid), 'not in that session');
        $this->assertTrue(learnpath::userIsLearnerOfCourse($inSession, self::COURSE_ID, $sid));
        $this->assertTrue(learnpath::userIsLearnerOfCourse($inSession, self::COURSE_ID));
        $this->assertFalse(learnpath::userIsLearnerOfCourse($inSession, $this->otherCourse()));
        $this->assertFalse(learnpath::userIsLearnerOfCourse(0, self::COURSE_ID));
    }

    public function testSessionGeneralCoachMayViewTheStudentsOfThatSession(): void
    {
        $student = $this->user();
        $coach = $this->user();
        $sid = $this->sessionWithStudent($student);
        $this->sessionUser($sid, $coach, SessionEntity::GENERAL_COACH);

        $this->assertTrue(learnpath::mayViewStatsOfUser($coach, $student, self::COURSE_ID));
        $this->assertFalse(learnpath::mayViewStatsOfUser($student, $coach, self::COURSE_ID), 'a student does not follow the coach');
    }

    public function testStatsPageAdmitsOnlyRealAdminsTheSessionAdminOfTheSessionAndTheFollowers(): void
    {
        $code = $this->source('public/main/lp/lp_stats.php');
        $this->assertStringContainsString('!api_is_platform_admin()', $code, 'session admins and HR managers do not pass as admins');
        $this->assertStringNotContainsString('api_is_platform_admin(true', $code);
        $this->assertStringNotContainsString('api_is_allowed_to_edit', $code, 'teachers go through the learner check');
        $this->assertStringContainsString('learnpath::mayViewStatsOfUser(api_get_user_id(), $userId, $statsCourseId, $statsSessionId)', $code);
        $this->assertStringContainsString('SessionManager::canManageSessionId($statsSessionId)', $code);
        $this->assertStringContainsString('learnpath::userIsLearnerOfCourse($userId, $statsCourseId, $statsSessionId)', $code);
    }

    public function testLpOfTheCurrentCourseIsAccepted(): void
    {
        [$lpId] = $this->lp(self::COURSE_ID);

        $this->assertTrue(learnpath::lpBelongsToCurrentCourse($lpId));
    }

    public function testLpOfAnotherCourseUnknownOrEmptyIdIsRejected(): void
    {
        [$foreign] = $this->lp($this->otherCourse());

        $this->assertFalse(learnpath::lpBelongsToCurrentCourse($foreign), 'linked to another course only');
        $this->assertFalse(learnpath::lpBelongsToCurrentCourse(999999999));
        $this->assertFalse(learnpath::lpBelongsToCurrentCourse(0));
        $this->assertFalse(learnpath::lpBelongsToCurrentCourse(-5));
    }

    public function testCategoryGuardFollowsTheCourseLink(): void
    {
        $own = $this->category(self::COURSE_ID);
        $foreign = $this->category($this->otherCourse());

        $this->assertTrue(learnpath::categoryBelongsToCurrentCourse($own));
        $this->assertFalse(learnpath::categoryBelongsToCurrentCourse($foreign));
        $this->assertFalse(learnpath::categoryBelongsToCurrentCourse(0));
        $this->assertFalse(learnpath::categoryBelongsToCurrentCourse(999999999));
    }

    public function testRenamingACategoryOfAnotherCourseChangesNothing(): void
    {
        $foreign = $this->category($this->otherCourse());
        $own = $this->category(self::COURSE_ID);

        learnpath::updateCategory(['id' => $foreign, 'name' => 'lc2_hijacked']);
        learnpath::updateCategory(['id' => $own, 'name' => 'lc2_renamed']);

        $this->assertSame('lc2_cat', $this->conn()->fetchOne('SELECT title FROM c_lp_category WHERE iid = ?', [$foreign]));
        $this->assertSame('lc2_renamed', $this->conn()->fetchOne('SELECT title FROM c_lp_category WHERE iid = ?', [$own]));
    }

    public function testItemGuardRequiresTheItemToBelongToThatPath(): void
    {
        [$lpA, $rootA] = $this->lp(self::COURSE_ID);
        [$lpB] = $this->lp(self::COURSE_ID);

        $this->assertTrue(learnpath::lpItemBelongsToLp($rootA, $lpA));
        $this->assertFalse(learnpath::lpItemBelongsToLp($rootA, $lpB), 'an item id of another path of the same course');
        $this->assertFalse(learnpath::lpItemBelongsToLp(0, $lpA));
        $this->assertFalse(learnpath::lpItemBelongsToLp($rootA, 0));
        $this->assertFalse(learnpath::lpItemBelongsToLp(999999999, $lpA));
    }

    /**
     * Legacy pages are outside CsrfProtectionListener and these actions are plain GET links: they must carry the
     * session token (checked, never regenerated, so the player and the other tabs keep working).
     *
     * @dataProvider stateChangingActions
     */
    public function testStateChangingControllerActionChecksTheToken(string $case): void
    {
        $this->assertStringContainsString('$requireToken()', $this->caseBlock($case), "$case must check the token");
    }

    /**
     * @dataProvider stateChangingActions
     */
    public function testStateChangingControllerActionIsTeacherOnly(string $case): void
    {
        $this->assertStringContainsString('$is_allowed_to_edit', $this->caseBlock($case), "$case must be teacher only");
    }

    // ------------------------------------------------------------- source-level pins of the page guards

    /**
     * @return array<string, array{string}>
     */
    public static function stateChangingActions(): array
    {
        $names = [
            'delete', 'delete_lp_category', 'move_up_category', 'move_down_category', 'toggle_category_visibility',
            'toggle_visible', 'toggle_category_publish', 'toggle_publish', 'move_lp_up', 'move_lp_down', 'auto_launch',
            'copy', 'toggle_seriousgame', 'switch_force_commit', 'switch_attempt_mode', 'switch_scorm_debug',
            'switch_view_mode', 'set_previous_step_as_prerequisite', 'clear_prerequisites', 'dissociate_forum',
        ];

        return array_combine($names, array_map(static fn (string $n): array => [$n], $names));
    }

    public function testThePlayerActionsStayTokenFree(): void
    {
        // Learners use these through the player: a token requirement would break them.
        foreach (['view', 'content', 'save', 'next', 'previous', 'first', 'last', 'restart', 'stats'] as $case) {
            $this->assertStringNotContainsString('$requireToken()', $this->caseBlock($case), $case);
        }
    }

    public function testTheTokenCheckDoesNotRegenerateTheToken(): void
    {
        $code = $this->source('public/main/lp/lp_controller.php');
        $this->assertStringContainsString('Security::check_token(\'request\')', $code);
        $this->assertStringNotContainsString('Security::get_token(', $code, 'get_token() rotates the token on every request');
    }

    public function testPrerequisiteLinksCarryTheToken(): void
    {
        $code = $this->source('public/main/lp/learnpath.class.php');
        $this->assertSame(2, substr_count($code, "'sec_token' => Security::get_existing_token()"));
    }

    public function testAudioActionChecksTheItemBelongsToThePath(): void
    {
        $this->assertStringContainsString('lpItemBelongsToLp', $this->caseBlock('add_audio'));
    }

    public function testStatsOfAnotherUserAreForTeachersOnly(): void
    {
        $code = $this->source('public/main/lp/lp_stats.php');
        $this->assertStringContainsString('mayViewStatsOfUser', $code);
        $this->assertStringContainsString('api_not_allowed', $code);
    }

    public function testCategoryFormOnlyReadsCategoriesOfTheCurrentCourse(): void
    {
        $this->assertStringContainsString('categoryBelongsToCurrentCourse', $this->source('public/main/lp/lp_add_category.php'));
    }

    public function testAiCreationEndpointIsTeacherOnlyAndBoundToTheCurrentCourse(): void
    {
        $code = $this->source('public/main/inc/ajax/lp.ajax.php');
        $block = substr($code, (int) strpos($code, "case 'add_lp_ai':"), 900);
        $this->assertStringContainsString('api_is_allowed_to_edit', $block);
        $this->assertStringContainsString('getCode()', $block, 'course_code must be the current course');
    }

    public function testMoveOfAnotherCoursePathsIsRefusedByTheControllerLookup(): void
    {
        // Pre-existing guard (89b1e7aeb04): the controller ignores a lp_id not linked to the current course.
        $code = $this->source('public/main/lp/lp_controller.php');
        $this->assertStringContainsString('getFirstResourceLinkFromCourseSession($course)', $code);
    }

    public function testRequestValuesAreNotEchoedRawInTheSearchLinks(): void
    {
        $code = $this->source('public/main/lp/lp_list_search.php');
        $this->assertStringNotContainsString("\$_REQUEST['query'], \$get_params", $code);
    }
}
