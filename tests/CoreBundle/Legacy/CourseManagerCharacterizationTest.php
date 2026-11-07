<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\Tests\ChamiloTestTrait;
use CourseManager;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Throwable;

/**
 * Characterization tests for the raw-SQL methods of CourseManager (public/main/inc/lib/course.lib.php).
 *
 * They pin what the legacy queries return TODAY (rows, keys, ordering, NULL/false handling, DB side effects) so
 * a later rewrite to Doctrine/DQL that changes any of it fails here. Where the current behaviour looks like a bug
 * it is pinned as-is and flagged with a "PINNED QUIRK" comment: do not "fix" the expectation without deciding
 * that the behaviour change is intended.
 *
 * Every fixture name starts with "cm_" (sibling test files run against the same database).
 */
final class CourseManagerCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // Legacy code reads its services, session and request through this static bridge.
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/course.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    /**
     * Inserts a course_rel_user row directly (no event, no mail, no limits), independent of subscribeUser().
     */
    private function sub(User $user, Course $course, int $status = STUDENT, int $relationType = 0, int $sort = 0, int $cat = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 0, ?, ?, 0, 0)',
            [$user->getId(), $course->getId(), $relationType, $status, $sort, $cat]
        );
    }

    private function sessionCourse(int $sessionId, Course $course, int $position = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, ?, 0)',
            [$sessionId, $course->getId(), $position]
        );
    }

    private function sessionCourseUser(int $sessionId, Course $course, User $user, int $status): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$sessionId, $course->getId(), $user->getId(), $status]
        );
    }

    private function loginAs(User $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    private function setCourseExtraValue(Course $course, string $variable, string $value): void
    {
        (new \ExtraFieldValue('course'))->save(['item_id' => $course->getId(), 'variable' => $variable, 'field_value' => $value]);
    }

    private function setVisibility(Course $course, int $visibility): void
    {
        $this->conn()->executeStatement('UPDATE course SET visibility = ? WHERE id = ?', [$visibility, $course->getId()]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return list<mixed>
     */
    private function col(array $rows, string $key): array
    {
        return array_values(array_map(static fn ($r) => $r[$key], $rows));
    }

    // ---------------------------------------------------------------- course lookup

    public function testGetCourseInformationJoinsLanguageAndExposesRealId(): void
    {
        $course = $this->createCourse('cm_info_a');

        $info = CourseManager::get_course_information($course->getCode());

        $this->assertSame((int) $course->getId(), (int) $info['id']);
        $this->assertSame((int) $course->getId(), (int) $info['real_id'], 'real_id aliases course.id');
        $this->assertSame('cm_info_a', $info['title']);
        $this->assertSame($course->getCode(), $info['code']);
        $this->assertSame('en_US', $info['course_language'], 'INNER JOIN language exposes isocode as course_language');
        $this->assertArrayHasKey(0, $info, 'fetch_array(ASSOC) is still called but legacy result carries numeric keys too');
    }

    public function testGetCourseInformationUnknownCodeIsEmpty(): void
    {
        $this->assertEmpty(CourseManager::get_course_information('CM_NO_SUCH_CODE'));
    }

    public function testCourseCodeLookups(): void
    {
        $course = $this->createCourse('cm_lookup_a');
        $id = (int) $course->getId();

        $this->assertSame($course->getCode(), CourseManager::get_course_code_from_course_id($id));
        $this->assertNull(CourseManager::get_course_code_from_course_id(0), 'unknown id gives null');
        $this->assertSame('cm_lookup_a', CourseManager::getCourseNameFromCode($course->getCode()));
        $this->assertNull(CourseManager::getCourseNameFromCode('CM_NO_SUCH_CODE'), 'unknown code gives null (no return)');
        $this->assertSame(1, CourseManager::course_exists($course->getCode()), 'course_exists returns the row count, not a bool');
        $this->assertSame(0, CourseManager::course_exists('CM_NO_SUCH_CODE'));
    }

    public function testCourseCodeExistsMatchesCodeOrVisualCode(): void
    {
        $course = $this->createCourse('cm_exists_a');

        $this->assertTrue(CourseManager::course_code_exists($course->getCode()), 'matches code');
        $this->assertTrue(CourseManager::course_code_exists('cm_exists_a'), 'matches visual_code too');
        $this->assertFalse(CourseManager::course_code_exists('CM_NO_SUCH_CODE'));
    }

    public function testGetCoursesInfoFromVisualCodeReturnsAllMatches(): void
    {
        $a = $this->createCourse('cm_visual_a');
        $b = $this->createCourse('cm_visual_b');
        $this->conn()->executeStatement('UPDATE course SET visual_code = ? WHERE id IN (?, ?)', ['CM_VISUAL_SHARED', $a->getId(), $b->getId()]);

        $rows = CourseManager::get_courses_info_from_visual_code('CM_VISUAL_SHARED');

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing([$a->getCode(), $b->getCode()], $this->col($rows, 'code'));
        $this->assertSame('en_US', $rows[0]['course_language']);
        $this->assertSame([], CourseManager::get_courses_info_from_visual_code('CM_NO_SUCH_VISUAL'));
    }

    public function testGetCourseCodeFromDirectoryNeedsExactlyOneBinaryMatch(): void
    {
        $a = $this->createCourse('cm_dir_a');
        $b = $this->createCourse('cm_dir_b');
        $this->conn()->executeStatement('UPDATE course SET directory = ? WHERE id = ?', ['CM_DIR_ONE', $a->getId()]);
        $this->conn()->executeStatement('UPDATE course SET directory = ? WHERE id = ?', ['CM_DIR_TWO', $b->getId()]);

        $this->assertSame($a->getCode(), CourseManager::getCourseCodeFromDirectory('CM_DIR_ONE'));
        $this->assertFalse(CourseManager::getCourseCodeFromDirectory('cm_dir_one'), 'LIKE BINARY is case sensitive');
        $this->assertSame($a->getCode(), CourseManager::getCourseCodeFromDirectory('/CM_DIR_ONE./'), 'slashes and dots are stripped first');
        $this->assertFalse(CourseManager::getCourseCodeFromDirectory('CM_DIR_NOPE'));

        // Two courses with the same directory: ambiguous, so false.
        $this->conn()->executeStatement('UPDATE course SET directory = ? WHERE id = ?', ['CM_DIR_ONE', $b->getId()]);
        $this->assertFalse(CourseManager::getCourseCodeFromDirectory('CM_DIR_ONE'));
    }

    // ---------------------------------------------------------------- course listing

    public function testGetCoursesListOrdersByTitleAndPaginates(): void
    {
        $b = $this->createCourse('cm_list_b');
        $a = $this->createCourse('cm_list_a');
        $c = $this->createCourse('cm_list_c');
        $only = [$a->getId(), $b->getId(), $c->getId()];

        $asc = CourseManager::get_courses_list(0, 0, 'title', 'ASC', -1, '', null, false, [], $only);
        $this->assertSame(['cm_list_a', 'cm_list_b', 'cm_list_c'], $this->col($asc, 'title'));
        $this->assertSame('en_US', $asc[0]['course_language']);
        $this->assertSame((int) $a->getId(), (int) $asc[0]['real_id']);

        $desc = CourseManager::get_courses_list(0, 0, 'title', 'DESC', -1, '', null, false, [], $only);
        $this->assertSame(['cm_list_c', 'cm_list_b', 'cm_list_a'], $this->col($desc, 'title'));

        $page = CourseManager::get_courses_list(1, 1, 'title', 'ASC', -1, '', null, false, [], $only);
        $this->assertSame(['cm_list_b'], $this->col($page, 'title'), 'from=1 howmany=1 gives the second row');

        // An orderby other than "title" silently falls back to title.
        $fallback = CourseManager::get_courses_list(0, 0, 'code', 'ASC', -1, '', null, false, [], $only);
        $this->assertSame(['cm_list_a', 'cm_list_b', 'cm_list_c'], $this->col($fallback, 'title'));

        // An invalid direction yields "ASC" (not an error).
        $bad = CourseManager::get_courses_list(0, 0, 'title', 'sideways', -1, '', null, false, [], $only);
        $this->assertSame(['cm_list_a', 'cm_list_b', 'cm_list_c'], $this->col($bad, 'title'));
    }

    public function testGetCoursesListFiltersByVisibilityPrefixCodeAndLike(): void
    {
        $a = $this->createCourse('cm_filt_alpha');
        $b = $this->createCourse('cm_filt_beta');
        $this->setVisibility($a, Course::CLOSED);
        $this->setVisibility($b, Course::OPEN_WORLD);
        $only = [$a->getId(), $b->getId()];

        $closed = CourseManager::get_courses_list(0, 0, 'title', 'ASC', Course::CLOSED, '', null, false, [], $only);
        $this->assertSame(['cm_filt_alpha'], $this->col($closed, 'title'));

        $world = CourseManager::get_courses_list(0, 0, 'title', 'ASC', Course::OPEN_WORLD, '', null, false, [], $only);
        $this->assertSame(['cm_filt_beta'], $this->col($world, 'title'));

        $prefix = CourseManager::get_courses_list(0, 0, 'title', 'ASC', -1, 'cm_filt_be', null, false, [], $only);
        $this->assertSame(['cm_filt_beta'], $this->col($prefix, 'title'), 'startwith is a title prefix');

        $notPrefix = CourseManager::get_courses_list(0, 0, 'title', 'ASC', -1, 'lpha', null, false, [], $only);
        $this->assertSame([], $notPrefix, 'startwith is NOT a substring match');

        $this->assertSame([], CourseManager::get_courses_list(0, 0, 'title', 'ASC', -1, $b->getCode(), null, false, [], $only), 'a code is not a title prefix');
        $byCode = CourseManager::get_courses_list(0, 0, 'title', 'ASC', -1, $b->getCode(), null, true, [], $only);
        $this->assertSame(['cm_filt_beta'], $this->col($byCode, 'title'), 'alsoSearchCode ORs a code prefix');

        $like = CourseManager::get_courses_list(0, 0, 'title', 'ASC', -1, '', null, false, ['title' => 'filt_al'], $only);
        $this->assertSame(['cm_filt_alpha'], $this->col($like, 'title'), 'conditionsLike is a %substring% match');
    }

    public function testGetCoursesListByAccessUrlJoinsUrlRelation(): void
    {
        $course = $this->createCourse('cm_url_a');
        $urlId = $this->getAccessUrl()->getId();

        $rows = CourseManager::get_courses_list(0, 0, 'title', 'ASC', -1, '', $urlId, false, ['title' => 'cm_url_a']);
        $this->assertSame([$course->getCode()], $this->col($rows, 'code'));

        $none = CourseManager::get_courses_list(0, 0, 'title', 'ASC', -1, '', 999999, false, ['title' => 'cm_url_a']);
        $this->assertSame([], $none);
    }

    public function testGetCoursesListWithOnlyUnknownLikeFieldIgnoresTheCondition(): void
    {
        // Fixed: a conditionsLike holding only non-allowed fields used to leave a dangling " AND " (SQL error);
        // such fields are skipped, so the condition is simply not applied.
        $course = $this->createCourse('cm_unk_like_a');

        $rows = CourseManager::get_courses_list(0, 0, 'title', 'ASC', -1, 'cm_unk_like_a', null, false, ['description' => 'x']);

        $this->assertSame([$course->getCode()], $this->col($rows, 'code'));
    }

    public function testGetCourseListReturnsEveryCourseWithLanguage(): void
    {
        $course = $this->createCourse('cm_all_a');

        $rows = CourseManager::get_course_list();

        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['real_id']] = $row;
        }
        $this->assertArrayHasKey((int) $course->getId(), $byId);
        $this->assertSame('en_US', $byId[(int) $course->getId()]['course_language']);
        $this->assertSame('cm_all_a', $byId[(int) $course->getId()]['title']);
    }

    // ---------------------------------------------------------------- counts

    public function testCountCoursesTracksCreationAndVisibilityFilter(): void
    {
        $total = (int) CourseManager::count_courses();
        $open = (int) CourseManager::count_courses(null, Course::OPEN_WORLD);
        $urlTotal = (int) CourseManager::count_courses($this->getAccessUrl()->getId());

        $course = $this->createCourse('cm_count_a');
        $this->setVisibility($course, Course::OPEN_WORLD);

        $this->assertSame($total + 1, (int) CourseManager::count_courses());
        $this->assertSame($open + 1, (int) CourseManager::count_courses(null, Course::OPEN_WORLD));
        $this->assertSame($urlTotal + 1, (int) CourseManager::count_courses($this->getAccessUrl()->getId()));

        // Fixed: visibility 0 (CLOSED) used to count as "empty" and silently disabled the filter.
        $closed = $this->createCourse('cm_count_closed');
        $this->setVisibility($closed, Course::CLOSED);
        $expectedClosed = (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course WHERE visibility = ?', [Course::CLOSED]);
        $this->assertGreaterThanOrEqual(1, $expectedClosed);
        $this->assertSame($expectedClosed, (int) CourseManager::count_courses(null, Course::CLOSED));
        $this->assertLessThan((int) CourseManager::count_courses(), (int) CourseManager::count_courses(null, Course::CLOSED));
        $this->assertSame(
            (int) $this->conn()->fetchOne(
                'SELECT COUNT(*) FROM course c INNER JOIN access_url_rel_course u ON u.c_id = c.id WHERE u.access_url_id = ? AND c.visibility = ?',
                [$this->getAccessUrl()->getId(), Course::CLOSED]
            ),
            (int) CourseManager::count_courses($this->getAccessUrl()->getId(), Course::CLOSED)
        );
    }

    public function testCountActiveCoursesExcludesHiddenOnly(): void
    {
        $before = (int) CourseManager::countActiveCourses();
        $beforeUrl = (int) CourseManager::countActiveCourses($this->getAccessUrl()->getId());

        $visible = $this->createCourse('cm_active_a');
        $hidden = $this->createCourse('cm_active_b');
        $this->setVisibility($hidden, Course::HIDDEN);
        $closed = $this->createCourse('cm_active_c');
        $this->setVisibility($closed, Course::CLOSED);

        $this->assertSame($before + 2, (int) CourseManager::countActiveCourses(), 'closed counts as active, hidden does not');
        $this->assertSame($beforeUrl + 2, (int) CourseManager::countActiveCourses($this->getAccessUrl()->getId()));
        $this->assertNotNull($visible);
    }

    public function testGetCountOpenCoursesCountsRegisteredOpenPlatformAndOpenWorld(): void
    {
        $before = CourseManager::getCountOpenCourses();
        foreach ([Course::CLOSED, Course::HIDDEN, Course::REGISTERED, Course::OPEN_PLATFORM, Course::OPEN_WORLD] as $i => $vis) {
            $course = $this->createCourse('cm_open_'.$i);
            $this->setVisibility($course, $vis);
        }

        $this->assertSame($before + 3, CourseManager::getCountOpenCourses());
    }

    public function testGetCountExercisesFromOpenCourseCountsLinkedQuizzesOfOpenCoursesOnly(): void
    {
        // Fixed: c_quiz.active and c_quiz.c_id are gone; quizzes now reach their course through resource_link.
        $before = CourseManager::getCountExercisesFromOpenCourse();

        $teacher = $this->createUser('cm_quiz_teacher');
        $open = $this->createCourse('cm_quiz_open');
        $this->setVisibility($open, Course::OPEN_WORLD);
        $closed = $this->createCourse('cm_quiz_closed');
        $this->setVisibility($closed, Course::CLOSED);

        $em = $this->getEntityManager();
        foreach ([[$open, 'cm_quiz_1'], [$open, 'cm_quiz_2'], [$closed, 'cm_quiz_3']] as [$course, $title]) {
            $quiz = (new CQuiz())->setTitle($title)->setParent($course)->setCreator($teacher)->addCourseLink($course);
            $em->persist($quiz);
        }
        $em->flush();

        $this->assertSame($before + 2, CourseManager::getCountExercisesFromOpenCourse());
    }

    // ---------------------------------------------------------------- subscription / membership

    public function testStatusLookupsForStudentTeacherAndStranger(): void
    {
        $course = $this->createCourse('cm_status_a');
        $student = $this->createUser('cm_status_student');
        $teacher = $this->createUser('cm_status_teacher');
        $stranger = $this->createUser('cm_status_stranger');
        $this->sub($student, $course, STUDENT);
        $this->sub($teacher, $course, COURSEMANAGER);
        $cid = $course->getId();

        $this->assertSame(STUDENT, (int) CourseManager::getUserInCourseStatus($student->getId(), $cid));
        $this->assertSame(COURSEMANAGER, (int) CourseManager::getUserInCourseStatus($teacher->getId(), $cid));
        $this->assertFalse(CourseManager::getUserInCourseStatus($stranger->getId(), $cid));
        $this->assertFalse(CourseManager::getUserInCourseStatus($student->getId(), 0));

        $this->assertTrue(CourseManager::isCourseTeacher($teacher->getId(), $cid));
        $this->assertFalse(CourseManager::isCourseTeacher($student->getId(), $cid));
        $this->assertFalse(CourseManager::isCourseTeacher($stranger->getId(), $cid));
        $this->assertFalse(CourseManager::isCourseTeacher(0, $cid));
    }

    public function testGetUserCourseInfoReturnsTheRelationRow(): void
    {
        $course = $this->createCourse('cm_uci_a');
        $user = $this->createUser('cm_uci_user');
        $this->sub($user, $course, STUDENT, 0, 7);

        $row = CourseManager::getUserCourseInfo($user->getId(), $course->getId());

        $this->assertSame((int) $user->getId(), (int) $row['user_id']);
        $this->assertSame((int) $course->getId(), (int) $row['c_id']);
        $this->assertSame(STUDENT, (int) $row['status']);
        $this->assertSame(7, (int) $row['sort']);
        $this->assertEmpty(CourseManager::getUserCourseInfo($user->getId(), 0));
    }

    public function testUpdateUserCourseTutorFlagIsReadBackByTutorStatus(): void
    {
        $course = $this->createCourse('cm_tutor_a');
        $user = $this->createUser('cm_tutor_user');
        $this->sub($user, $course, STUDENT);

        $this->assertEquals(0, CourseManager::get_tutor_in_course_status($user->getId(), $course->getId()));

        CourseManager::updateUserCourseTutor($user->getId(), $course->getId(), true);
        $this->assertEquals(1, CourseManager::get_tutor_in_course_status($user->getId(), $course->getId()));

        CourseManager::updateUserCourseTutor($user->getId(), $course->getId(), false);
        $this->assertEquals(0, CourseManager::get_tutor_in_course_status($user->getId(), $course->getId()));
    }

    public function testIsUserSubscribedInCourseIgnoresHrmRelation(): void
    {
        $course = $this->createCourse('cm_subd_a');
        $other = $this->createCourse('cm_subd_b');
        $student = $this->createUser('cm_subd_student');
        $hrm = $this->createUser('cm_subd_hrm');
        $this->sub($student, $course, STUDENT);
        $this->sub($hrm, $course, DRH, COURSE_RELATION_TYPE_RRHH);

        $this->assertTrue(CourseManager::is_user_subscribed_in_course($student->getId(), $course->getCode()));
        $this->assertFalse(CourseManager::is_user_subscribed_in_course($student->getId(), $other->getCode()));
        $this->assertTrue(CourseManager::is_user_subscribed_in_course($student->getId()), 'null code: subscribed anywhere');
        $this->assertFalse(CourseManager::is_user_subscribed_in_course($hrm->getId(), $course->getCode()), 'RRHH relation does not count');
        $this->assertFalse(CourseManager::is_user_subscribed_in_course($student->getId(), 'CM_NO_SUCH_CODE'));
    }

    public function testIsUserSubscribedInCourseLooksInSessionsOnlyWhenAsked(): void
    {
        $course = $this->createCourse('cm_subs_a');
        $session = $this->createSession('cm_subs_session');
        $user = $this->createUser('cm_subs_user');
        $this->sessionCourse($session->getId(), $course);
        $this->sessionCourseUser($session->getId(), $course, $user, 0);

        $this->assertFalse(CourseManager::is_user_subscribed_in_course($user->getId(), $course->getCode(), false, $session->getId()));
        $this->assertTrue(CourseManager::is_user_subscribed_in_course($user->getId(), $course->getCode(), true, $session->getId()));
        $this->assertFalse(CourseManager::is_user_subscribed_in_course($user->getId(), $course->getCode(), true, $session->getId() + 100000));
    }

    public function testSubscribeUserInsertsStudentRowOnceAndRefusesDuplicates(): void
    {
        $course = $this->createCourse('cm_subu_a');
        $user = $this->createUser('cm_subu_user');
        $opts = ['flash' => false, 'emails' => false];

        $this->assertTrue(CourseManager::subscribeUser($user->getId(), $course->getId(), STUDENT, 0, 0, false, $opts));

        $row = CourseManager::getUserCourseInfo($user->getId(), $course->getId());
        $this->assertSame(STUDENT, (int) $row['status']);
        $this->assertSame(0, (int) $row['relation_type']);

        $this->assertFalse(CourseManager::subscribeUser($user->getId(), $course->getId(), STUDENT, 0, 0, false, $opts), 'already registered');
        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = ? AND c_id = ?', [$user->getId(), $course->getId()])
        );
    }

    public function testSubscribeUserStructuredResultAndInvalidInputs(): void
    {
        $course = $this->createCourse('cm_subr_a');
        $user = $this->createUser('cm_subr_user');
        $opts = ['flash' => false, 'emails' => false, 'result' => true];

        $result = CourseManager::subscribeUser($user->getId(), $course->getId(), COURSEMANAGER, 0, 0, false, $opts);
        $this->assertTrue($result['ok']);
        $this->assertNotSame('', $result['message']);
        $this->assertSame(COURSEMANAGER, (int) CourseManager::getUserInCourseStatus($user->getId(), $course->getId()));

        $this->assertFalse(CourseManager::subscribeUser(0, $course->getId(), STUDENT, 0, 0, false, ['flash' => false, 'emails' => false, 'result' => true])['ok']);
        $this->assertFalse(CourseManager::subscribeUser($user->getId(), 0, STUDENT, 0, 0, false, ['flash' => false, 'result' => true])['ok']);
        $this->assertFalse(CourseManager::subscribeUser($user->getId(), 99999999, STUDENT, 0, 0, false, ['flash' => false, 'result' => true])['ok']);
        $this->assertFalse(CourseManager::subscribeUser(99999999, $course->getId(), STUDENT, 0, 0, false, ['flash' => false, 'result' => true])['ok']);
    }

    public function testSubscribeUserRefusedWhenCourseSubscriptionIsClosed(): void
    {
        $course = $this->createCourse('cm_subc_a');
        $user = $this->createUser('cm_subc_user');
        // Through the entity: subscribeUser() reads the managed Course, which a raw UPDATE would leave stale.
        $course->setSubscribe(false);
        Database::getManager()->flush();
        $opts = ['flash' => false, 'emails' => false];

        // Act as a plain student, otherwise api_is_course_admin() is true and the closed subscription is not enforced.
        $this->loginAs($user);
        $this->assertFalse(CourseManager::subscribeUser($user->getId(), $course->getId(), STUDENT, 0, 0, true, $opts));
        $this->assertFalse(CourseManager::getUserInCourseStatus($user->getId(), $course->getId()));

        // The permission check can be bypassed (what admin imports do).
        $this->assertTrue(CourseManager::subscribeUser($user->getId(), $course->getId(), STUDENT, 0, 0, false, $opts));
    }

    public function testUnsubscribeUserRemovesOnlyThatCoursesRelationButKeepsHrm(): void
    {
        $course = $this->createCourse('cm_unsub_a');
        $other = $this->createCourse('cm_unsub_b');
        $user = $this->createUser('cm_unsub_user');
        $keep = $this->createUser('cm_unsub_keep');
        $this->sub($user, $course, STUDENT);
        $this->sub($user, $other, STUDENT);
        $this->sub($keep, $course, STUDENT);

        $this->assertFalse(CourseManager::unsubscribe_user(0, $course->getCode()));
        $this->assertFalse(CourseManager::unsubscribe_user($user->getId(), ''));

        CourseManager::unsubscribe_user($user->getId(), $course->getCode());

        $this->assertFalse(CourseManager::getUserInCourseStatus($user->getId(), $course->getId()));
        $this->assertSame(STUDENT, (int) CourseManager::getUserInCourseStatus($user->getId(), $other->getId()), 'other course untouched');
        $this->assertSame(STUDENT, (int) CourseManager::getUserInCourseStatus($keep->getId(), $course->getId()), 'other user untouched');
    }

    public function testUnsubscribeUserAcceptsAListOfIds(): void
    {
        $course = $this->createCourse('cm_unsubl_a');
        $u1 = $this->createUser('cm_unsubl_one');
        $u2 = $this->createUser('cm_unsubl_two');
        $this->sub($u1, $course);
        $this->sub($u2, $course);

        CourseManager::unsubscribe_user([$u1->getId(), $u2->getId()], $course->getCode());

        $this->assertSame(
            0,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE c_id = ?', [$course->getId()])
        );
    }

    // ---------------------------------------------------------------- user listings

    public function testGetUserListFromCourseCodeOrdersByLastnameFirstnameAndFiltersStatus(): void
    {
        $course = $this->createCourse('cm_ul_a');
        $zed = $this->createUser('cm_ul_zed');
        $amy = $this->createUser('cm_ul_amy');
        $teacher = $this->createUser('cm_ul_teacher');
        $this->sub($zed, $course, STUDENT);
        $this->sub($amy, $course, STUDENT);
        $this->sub($teacher, $course, COURSEMANAGER);
        // Lastname = username in the test helper, so the default order is amy, teacher, zed.

        $all = CourseManager::get_user_list_from_course_code($course->getCode());
        $this->assertSame(['cm_ul_amy', 'cm_ul_teacher', 'cm_ul_zed'], $this->col($all, 'username'));
        $this->assertEqualsCanonicalizing([STUDENT, COURSEMANAGER, STUDENT], array_map('intval', $this->col($all, 'status_rel')));

        $students = CourseManager::get_user_list_from_course_code($course->getCode(), 0, null, null, STUDENT);
        $this->assertSame(['cm_ul_amy', 'cm_ul_zed'], $this->col($students, 'username'));

        $teachers = CourseManager::get_user_list_from_course_code($course->getCode(), 0, null, null, COURSEMANAGER);
        $this->assertSame(['cm_ul_teacher'], $this->col($teachers, 'username'));
        $this->assertSame([(int) $teacher->getId()], array_keys($teachers), 'the list is keyed by user id');
        $this->assertSame((int) $teacher->getId(), (int) $teachers[$teacher->getId()]['user_id']);

        $this->assertSame(2, (int) CourseManager::get_user_list_from_course_code($course->getCode(), 0, null, null, STUDENT, true));
        $this->assertSame(3, (int) CourseManager::get_user_list_from_course_code($course->getCode(), 0, null, null, null, true));
    }

    public function testGetUserListFromCourseCodeOrderLimitKeywordAndIdFilters(): void
    {
        $course = $this->createCourse('cm_ulf_a');
        $users = [];
        foreach (['b', 'c', 'a'] as $suffix) {
            $users[$suffix] = $this->createUser('cm_ulf_'.$suffix);
            $this->sub($users[$suffix], $course);
        }

        $desc = CourseManager::get_user_list_from_course_code($course->getCode(), 0, null, 'user.username DESC');
        $this->assertSame(['cm_ulf_c', 'cm_ulf_b', 'cm_ulf_a'], $this->col($desc, 'username'));

        $limited = CourseManager::get_user_list_from_course_code($course->getCode(), 0, 'LIMIT 1, 1', 'user.username ASC');
        $this->assertSame(['cm_ulf_b'], $this->col($limited, 'username'));

        $byId = CourseManager::get_user_list_from_course_code($course->getCode(), 0, null, null, null, null, false, false, [], [], [$users['c']->getId()]);
        $this->assertSame(['cm_ulf_c'], $this->col($byId, 'username'));

        $exact = CourseManager::get_user_list_from_course_code($course->getCode(), 0, null, null, null, null, false, false, [], [], [], null, [], 'cm_ulf_a');
        $this->assertSame(['cm_ulf_a'], $this->col($exact, 'username'), 'keyword is matched with LIKE but without added wildcards');
        $partial = CourseManager::get_user_list_from_course_code($course->getCode(), 0, null, null, null, null, false, false, [], [], [], null, [], 'ulf_a');
        $this->assertSame([], $partial, 'no implicit % around the keyword');
    }

    public function testGetUserListFromCourseCodeSkipsSoftDeletedAndHonoursActiveFilter(): void
    {
        $course = $this->createCourse('cm_ulx_a');
        $active = $this->createUser('cm_ulx_active');
        $inactive = $this->createUser('cm_ulx_inactive');
        $deleted = $this->createUser('cm_ulx_deleted');
        foreach ([$active, $inactive, $deleted] as $u) {
            $this->sub($u, $course);
        }
        $this->conn()->executeStatement('UPDATE user SET active = 0 WHERE id = ?', [$inactive->getId()]);
        $this->conn()->executeStatement('UPDATE user SET active = -2 WHERE id = ?', [$deleted->getId()]);

        $all = CourseManager::get_user_list_from_course_code($course->getCode());
        $this->assertSame(['cm_ulx_active', 'cm_ulx_inactive'], $this->col($all, 'username'), 'soft deleted (-2) users are hidden');

        $onlyActive = CourseManager::get_user_list_from_course_code($course->getCode(), 0, null, null, null, null, false, false, [], [], [], true);
        $this->assertSame(['cm_ulx_active'], $this->col($onlyActive, 'username'));

        $onlyInactive = CourseManager::get_user_list_from_course_code($course->getCode(), 0, null, null, null, null, false, false, [], [], [], false);
        $this->assertSame(['cm_ulx_inactive'], $this->col($onlyInactive, 'username'));
    }

    public function testGetUserListFromCourseCodeInsideASession(): void
    {
        $course = $this->createCourse('cm_uls_a');
        $session = $this->createSession('cm_uls_session');
        $student = $this->createUser('cm_uls_student');
        $coach = $this->createUser('cm_uls_coach');
        $direct = $this->createUser('cm_uls_direct');
        $this->sessionCourse($session->getId(), $course);
        $this->sessionCourseUser($session->getId(), $course, $student, 0);
        $this->sessionCourseUser($session->getId(), $course, $coach, 2);
        $this->sub($direct, $course);

        $list = CourseManager::get_user_list_from_course_code($course->getCode(), $session->getId());
        $this->assertSame(['cm_uls_coach', 'cm_uls_student'], $this->col($list, 'username'), 'session membership only, not direct course members');
        $this->assertEqualsCanonicalizing(['cm_uls_student'], $this->col(
            CourseManager::get_user_list_from_course_code($course->getCode(), $session->getId(), null, null, 0),
            'username'
        ));
        $this->assertSame((int) $session->getId(), (int) $list[$coach->getId()]['session_id']);
        $this->assertSame(2, (int) $list[$coach->getId()]['status_session']);
        $this->assertSame(1, (int) CourseManager::get_user_list_from_course_code($course->getCode(), $session->getId(), null, null, 2, true));
    }

    public function testGetUsersCountInCourse(): void
    {
        $course = $this->createCourse('cm_cnt_a');
        $session = $this->createSession('cm_cnt_session');
        $s1 = $this->createUser('cm_cnt_one');
        $s2 = $this->createUser('cm_cnt_two');
        $t = $this->createUser('cm_cnt_teacher');
        $hrm = $this->createUser('cm_cnt_hrm');
        $this->sub($s1, $course);
        $this->sub($s2, $course);
        $this->sub($t, $course, COURSEMANAGER);
        $this->sub($hrm, $course, DRH, COURSE_RELATION_TYPE_RRHH);
        $this->sessionCourse($session->getId(), $course);
        $this->sessionCourseUser($session->getId(), $course, $s1, 0);

        $this->assertEquals(3, CourseManager::get_users_count_in_course($course->getCode()), 'students+teachers, no HRM');
        $this->assertEquals(3, CourseManager::get_users_count_in_course($course->getCode(), 0, STUDENT), 'PINNED QUIRK: the status argument is ignored');
        $this->assertEquals(1, CourseManager::get_users_count_in_course($course->getCode(), $session->getId()));
    }

    public function testGetStudentListFromCourseCodeIsKeyedByUserId(): void
    {
        $course = $this->createCourse('cm_stu_a');
        $session = $this->createSession('cm_stu_session');
        $s1 = $this->createUser('cm_stu_one');
        $s2 = $this->createUser('cm_stu_two');
        $invitee = $this->createUser('cm_stu_invitee');
        $teacher = $this->createUser('cm_stu_teacher');
        $viaSession = $this->createUser('cm_stu_sess');
        $this->conn()->executeStatement('UPDATE user SET status = ? WHERE id = ?', [INVITEE, $invitee->getId()]);
        foreach ([$s1, $s2, $invitee] as $u) {
            $this->sub($u, $course);
        }
        $this->sub($teacher, $course, COURSEMANAGER);
        $this->sessionCourse($session->getId(), $course);
        $this->sessionCourseUser($session->getId(), $course, $viaSession, 0);

        $list = CourseManager::get_student_list_from_course_code($course->getCode());
        $this->assertEqualsCanonicalizing([$s1->getId(), $s2->getId(), $invitee->getId()], array_keys($list));
        $this->assertSame('cm_stu_one', $list[$s1->getId()]['username']);

        $noInvitees = CourseManager::get_student_list_from_course_code($course->getCode(), false, 0, null, null, false);
        $this->assertEqualsCanonicalizing([$s1->getId(), $s2->getId()], array_keys($noInvitees));

        $this->assertSame(3, CourseManager::get_student_list_from_course_code($course->getCode(), false, 0, null, null, true, 0, true));

        $withSession = CourseManager::get_student_list_from_course_code($course->getCode(), true);
        $this->assertContains($viaSession->getId(), array_keys($withSession));
        $this->assertCount(4, $withSession);

        $this->assertSame([], CourseManager::get_student_list_from_course_code('CM_NO_SUCH_CODE'));
    }

    public function testTeacherListings(): void
    {
        $course = $this->createCourse('cm_tea_a');
        $t1 = $this->createUser('cm_tea_one');
        $t2 = $this->createUser('cm_tea_two');
        $student = $this->createUser('cm_tea_student');
        $this->sub($t1, $course, COURSEMANAGER);
        $this->sub($t2, $course, COURSEMANAGER);
        $this->sub($student, $course, STUDENT);

        $byCode = CourseManager::get_teacher_list_from_course_code($course->getCode());
        $this->assertEqualsCanonicalizing([$t1->getId(), $t2->getId()], array_keys($byCode), 'keyed by user id');
        $this->assertSame('cm_tea_one', $byCode[$t1->getId()]['username']);
        $this->assertSame('cm_tea_one@example.com', $byCode[$t1->getId()]['email']);

        $byId = CourseManager::getTeachersFromCourse($course->getId());
        $this->assertCount(2, $byId);
        $this->assertEqualsCanonicalizing(['cm_tea_one', 'cm_tea_two'], $this->col($byId, 'username'));
        $first = $byId[0];
        $this->assertSame(
            ['id', 'lastname', 'firstname', 'email', 'username', 'status', 'fullname', 'avatar', 'url'],
            array_keys($first)
        );
        $this->assertStringContainsString('course_id='.$course->getId().'&user_id='.$first['id'], $first['url']);

        $this->assertSame([], CourseManager::get_teacher_list_from_course_code($this->createCourse('cm_tea_empty')->getCode()));
        $this->assertFalse(CourseManager::getTeachersFromCourse(0));
    }

    public function testSessionCoachListings(): void
    {
        $course = $this->createCourse('cm_coach_a');
        $session = $this->createSession('cm_coach_session');
        $coach = $this->createUser('cm_coach_one');
        $student = $this->createUser('cm_coach_student');
        $this->sessionCourse($session->getId(), $course);
        $this->sessionCourseUser($session->getId(), $course, $coach, 2);
        $this->sessionCourseUser($session->getId(), $course, $student, 0);

        $coaches = CourseManager::get_coachs_from_course($session->getId(), $course->getId());
        $this->assertCount(1, $coaches);
        $this->assertSame('cm_coach_one', $coaches[0]['username']);
        $this->assertSame((int) $coach->getId(), (int) $coaches[0]['user_id']);
        $this->assertArrayHasKey('full_name', $coaches[0]);

        $byCode = CourseManager::get_coach_list_from_course_code($course->getCode(), $session->getId(), false);
        $this->assertSame([$coach->getId()], array_keys($byCode));
        $this->assertSame([], CourseManager::get_coach_list_from_course_code($course->getCode(), 0), 'no session: empty');
        $this->assertSame([], CourseManager::get_coach_list_from_course_code('', $session->getId()), 'no code: empty');
    }

    public function testCountUsersForLimitsDistinguishHrmStudentsAndSessionStudents(): void
    {
        $course = $this->createCourse('cm_lim_a');
        $session = $this->createSession('cm_lim_session');
        $student = $this->createUser('cm_lim_student');
        $teacher = $this->createUser('cm_lim_teacher');
        $hrm = $this->createUser('cm_lim_hrm');
        $sessionStudent = $this->createUser('cm_lim_sess');
        $both = $this->createUser('cm_lim_both');
        $this->sub($student, $course, STUDENT);
        $this->sub($teacher, $course, COURSEMANAGER);
        $this->sub($hrm, $course, DRH, COURSE_RELATION_TYPE_RRHH);
        $this->sub($both, $course, STUDENT);
        $this->sessionCourse($session->getId(), $course);
        // Fixed: the session half filtered "status = STUDENT" (5) while session students are stored with
        // Session::STUDENT (0), so they were never counted. A session coach (status 2) is still not a student.
        $coach = $this->createUser('cm_lim_coach');
        $this->sessionCourseUser($session->getId(), $course, $sessionStudent, 0);
        $this->sessionCourseUser($session->getId(), $course, $both, 0);
        $this->sessionCourseUser($session->getId(), $course, $coach, 2);
        $cid = $course->getId();

        $this->assertSame(3, CourseManager::countUsersForGlobalLimit($cid), 'every course_rel_user row but RRHH: student, teacher, both');
        $this->assertSame(3, CourseManager::countStudentsForUsersPerCourseLimit($cid), 'student + both (deduplicated across the UNION) + session student; coach ignored');
        $this->assertSame(0, CourseManager::countUsersForGlobalLimit(0));
        $this->assertSame(0, CourseManager::countStudentsForUsersPerCourseLimit(-1));
        $this->assertGreaterThanOrEqual(0, CourseManager::getEffectiveUsersPerCourseLimit($cid));
    }

    // ---------------------------------------------------------------- user -> courses

    public function testGetCoursesListByUserIdIsOrderedByTitleAndHonoursSkipList(): void
    {
        $b = $this->createCourse('cm_ucl_b');
        $a = $this->createCourse('cm_ucl_a');
        $c = $this->createCourse('cm_ucl_c');
        $user = $this->createUser('cm_ucl_user');
        foreach ([$b, $c, $a] as $course) {
            $this->sub($user, $course);
        }

        $rows = CourseManager::get_courses_list_by_user_id($user->getId(), false, false, false);
        $this->assertSame(['cm_ucl_a', 'cm_ucl_b', 'cm_ucl_c'], $this->col($rows, 'title'));
        $this->assertSame([$a->getCode(), $b->getCode(), $c->getCode()], $this->col($rows, 'code'));
        $this->assertSame(['code', 'real_id', 'title'], array_keys($rows[0]));

        $skipped = CourseManager::get_courses_list_by_user_id($user->getId(), false, false, false, [$b->getId()]);
        $this->assertSame(['cm_ucl_a', 'cm_ucl_c'], $this->col($skipped, 'title'));

        $this->assertSame([], CourseManager::get_courses_list_by_user_id($this->createUser('cm_ucl_none')->getId(), false, false, false));
    }

    public function testGetCoursesListByUserIdIncludesSessionCoursesOnlyWhenAsked(): void
    {
        $direct = $this->createCourse('cm_ucs_direct');
        $viaSession = $this->createCourse('cm_ucs_session_course');
        $session = $this->createSession('cm_ucs_session');
        $user = $this->createUser('cm_ucs_user');
        $this->sub($user, $direct);
        $this->sessionCourse($session->getId(), $viaSession);
        $this->sessionCourseUser($session->getId(), $viaSession, $user, 0);

        $without = CourseManager::get_courses_list_by_user_id($user->getId(), false, false, false);
        $this->assertSame(['cm_ucs_direct'], $this->col($without, 'title'));

        $with = CourseManager::get_courses_list_by_user_id($user->getId(), true, false, false);
        $this->assertCount(2, $with);
        $sessionRow = $with[1];
        $this->assertSame($viaSession->getCode(), $sessionRow['code']);
        $this->assertSame((int) $session->getId(), (int) $sessionRow['session_id']);
        $this->assertSame('cm_ucs_session', $sessionRow['session_name']);
    }

    public function testGetCoursesFollowedByUserFiltersByRoleAndKeyword(): void
    {
        $teaching = $this->createCourse('cm_fol_teaching');
        $learning = $this->createCourse('cm_fol_learning');
        $watched = $this->createCourse('cm_fol_watched');
        $user = $this->createUser('cm_fol_user');
        $this->sub($user, $teaching, COURSEMANAGER);
        $this->sub($user, $learning, STUDENT);
        $this->sub($user, $watched, DRH, COURSE_RELATION_TYPE_RRHH);

        $taught = CourseManager::getCoursesFollowedByUser($user->getId(), COURSEMANAGER);
        $this->assertSame([$teaching->getCode()], array_keys($taught), 'keyed by course code');
        $this->assertSame('en_US', $taught[$teaching->getCode()]['course_language']);

        $drh = CourseManager::get_courses_followed_by_hrm($user->getId());
        $this->assertSame([$watched->getCode()], array_keys($drh));

        $this->assertSame(1, (int) CourseManager::getCoursesFollowedByUser($user->getId(), DRH, null, null, null, null, true));
        $this->assertSame([], CourseManager::getCoursesFollowedByUser($user->getId(), COURSEMANAGER, null, null, null, null, false, 'cm_fol_learning'));
        $kw = CourseManager::getCoursesFollowedByUser($user->getId(), COURSEMANAGER, null, null, null, null, false, 'fol_teach');
        $this->assertSame([$teaching->getCode()], array_keys($kw));

        // PINNED QUIRK: without a status no user condition is added at all, so every course that has any
        // course_rel_user row is returned, including courses of other users.
        $stranger = $this->createCourse('cm_fol_stranger');
        $someoneElse = $this->createUser('cm_fol_else');
        $this->sub($someoneElse, $stranger);
        $all = CourseManager::getCoursesFollowedByUser($user->getId());
        $this->assertContains($stranger->getCode(), array_keys($all));
        $this->assertContains($learning->getCode(), array_keys($all));
    }

    public function testSubscribeCoursesToHrmManagerReplacesPreviousAssignment(): void
    {
        $a = $this->createCourse('cm_hrm_a');
        $b = $this->createCourse('cm_hrm_b');
        $hrm = $this->createUser('cm_hrm_user');

        $this->assertSame(2, CourseManager::subscribeCoursesToHrmManager($hrm->getId(), [$a->getCode(), $b->getCode()]));
        $this->assertEqualsCanonicalizing(
            [$a->getCode(), $b->getCode()],
            array_keys(CourseManager::get_courses_followed_by_hrm($hrm->getId()))
        );

        // Existing RRHH rows are deleted first, then the new list is inserted.
        $this->assertSame(1, CourseManager::subscribeCoursesToHrmManager($hrm->getId(), [$b->getCode()]));
        $this->assertSame([$b->getCode()], array_keys(CourseManager::get_courses_followed_by_hrm($hrm->getId())));

        $row = $this->conn()->fetchAssociative('SELECT status, relation_type FROM course_rel_user WHERE user_id = ? AND c_id = ?', [$hrm->getId(), $b->getId()]);
        $this->assertSame(DRH, (int) $row['status']);
        $this->assertSame(COURSE_RELATION_TYPE_RRHH, (int) $row['relation_type']);

        $this->assertSame(0, CourseManager::subscribeCoursesToHrmManager($hrm->getId(), []));
        $this->assertSame([], CourseManager::get_courses_followed_by_hrm($hrm->getId()));
        $this->assertSame(0, CourseManager::subscribeCoursesToDrhManager($hrm->getId(), []), 'deprecated alias');
    }

    public function testGetCoursesByUserCourseCategoryListsUserCoursesWithTheirFirstCategoryCode(): void
    {
        // Fixed: the query selected course.category_code (gone, categories are many-to-many now) and always threw.
        // 'category' now carries the code of the course's first category, or null when it has none.
        $user = $this->createUser('cm_cat_user');
        $withCat = $this->createCourse('cm_cat_a');
        $noCat = $this->createCourse('cm_cat_b');
        $other = $this->createCourse('cm_cat_c');
        $this->sub($user, $withCat, STUDENT, 0, 1);
        $this->sub($user, $noCat, COURSEMANAGER, 0, 2);
        $this->conn()->executeStatement(
            'INSERT INTO course_category (code, title, tree_pos, children_count) VALUES (?, ?, 0, 0)',
            ['CMCAT', 'cm_cat']
        );
        $catId = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO course_rel_category (course_id, course_category_id) VALUES (?, ?)', [$withCat->getId(), $catId]);

        $rows = CourseManager::getCoursesByUserCourseCategory($user->getId());

        $this->assertSame([$withCat->getCode(), $noCat->getCode()], $this->col($rows, 'code'), 'only the user courses, by sort');
        $this->assertSame('CMCAT', $rows[0]['category']);
        $this->assertNull($rows[1]['category']);
        $this->assertSame(COURSEMANAGER, (int) $rows[1]['status']);
        $this->assertNotNull($other);
    }

    public function testUserCourseSortReturnsAndMakesRoomForTheAlphabeticalPosition(): void
    {
        $this->assertSame(0, CourseManager::userCourseSort(0, 'X'));
        $this->assertSame(0, CourseManager::userCourseSort(1, ''));

        $a = $this->createCourse('cm_sort_a');
        $c = $this->createCourse('cm_sort_c');
        $b = $this->createCourse('cm_sort_b');
        $user = $this->createUser('cm_sort_user');
        $this->sub($user, $a, STUDENT, 0, 1);
        $this->sub($user, $c, STUDENT, 0, 2);

        // Course b sorts between a and c: it gets a's sort slot + 1 and c is pushed down.
        $position = CourseManager::userCourseSort($user->getId(), $b->getCode());

        $this->assertSame(2, $position);
        $sorts = $this->conn()->fetchAllKeyValue('SELECT c_id, sort FROM course_rel_user WHERE user_id = ?', [$user->getId()]);
        $this->assertSame(1, (int) $sorts[$a->getId()]);
        $this->assertSame(3, (int) $sorts[$c->getId()], 'the later course was shifted by one');
    }

    // ---------------------------------------------------------------- special / catalogue

    public function testSpecialCoursesAreTheStickyOnes(): void
    {
        $sticky = $this->createCourse('cm_sticky_a');
        $plain = $this->createCourse('cm_sticky_b');
        $this->conn()->executeStatement('UPDATE course SET sticky = 1 WHERE id = ?', [$sticky->getId()]);

        $this->assertTrue(CourseManager::isSpecialCourse($sticky->getId()));
        $this->assertFalse(CourseManager::isSpecialCourse($plain->getId()));
        $this->assertFalse(CourseManager::isSpecialCourse(0));

        $special = CourseManager::get_special_course_list();
        $this->assertContains((int) $sticky->getId(), array_map('intval', $special));
        $this->assertNotContains((int) $plain->getId(), array_map('intval', $special));
    }

    public function testCatalogueVisibilityRoundTrip(): void
    {
        // Fixed: the user existence check read "user.user_id" (the column is "id") and always threw.
        $course = $this->createCourse('cm_catv_a');
        $user = $this->createUser('cm_catv_user');

        $this->assertTrue(CourseManager::addUserVisibilityToCourseInCatalogue($user->getId(), $course->getCode()));
        $this->assertSame([$course->getCode()], CourseManager::getCatalogCourseList(true, $user->getId()));

        $this->assertTrue(CourseManager::addUserVisibilityToCourseInCatalogue($user->getId(), $course->getCode()), 'adding twice is still true');
        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course_rel_user_catalogue WHERE user_id = ? AND c_id = ?', [$user->getId(), $course->getId()]),
            'no duplicate row'
        );

        $this->assertFalse(CourseManager::addUserVisibilityToCourseInCatalogue(99999999, $course->getCode()), 'unknown user');

        // Guard clauses return before touching the database.
        $this->assertFalse(CourseManager::addUserVisibilityToCourseInCatalogue(0, $course->getCode()));
        $this->assertFalse(CourseManager::addUserVisibilityToCourseInCatalogue($user->getId(), ''));
        $this->assertFalse(CourseManager::addUserVisibilityToCourseInCatalogue('abc', $course->getCode()));
        $this->assertFalse(CourseManager::removeUserVisibilityToCourseInCatalogue(0, $course->getCode()));
    }

    public function testCatalogCourseListAndRemovalReadTheCatalogueTable(): void
    {
        $shown = $this->createCourse('cm_cats_shown');
        $hidden = $this->createCourse('cm_cats_hidden');
        $user = $this->createUser('cm_cats_user');
        $other = $this->createUser('cm_cats_other');
        $conn = $this->conn();
        $conn->executeStatement('INSERT INTO course_rel_user_catalogue (user_id, c_id, visible) VALUES (?, ?, 1)', [$user->getId(), $shown->getId()]);
        $conn->executeStatement('INSERT INTO course_rel_user_catalogue (user_id, c_id, visible) VALUES (?, ?, 0)', [$user->getId(), $hidden->getId()]);

        $this->assertSame([$shown->getCode()], CourseManager::getCatalogCourseList(true, $user->getId()));
        $this->assertSame([$hidden->getCode()], CourseManager::getCatalogCourseList(false, $user->getId()));
        $this->assertSame([], CourseManager::getCatalogCourseList(true, $other->getId()));
        $this->assertContains($shown->getCode(), CourseManager::getCatalogCourseList(true), 'no user: any user restriction');

        // Fixed: it returned the deleted row count (int) while its docblock promises a bool.
        $this->assertTrue(CourseManager::removeUserVisibilityToCourseInCatalogue($user->getId(), $shown->getCode()), 'true when a row was removed');
        $this->assertSame([], CourseManager::getCatalogCourseList(true, $user->getId()));
        $this->assertTrue(CourseManager::removeUserVisibilityToCourseInCatalogue($user->getId(), $shown->getCode()), 'removing a missing row is still true');
        $this->assertSame([$hidden->getCode()], CourseManager::getCatalogCourseList(false, $user->getId()), 'visible=0 row not touched when removing visible=1');
    }

    // ---------------------------------------------------------------- legal acceptance / votes

    public function testLegalAcceptanceIsStoredOnTheRelationRow(): void
    {
        $course = $this->createCourse('cm_legal_a');
        $user = $this->createUser('cm_legal_user');
        $stranger = $this->createUser('cm_legal_stranger');
        $this->sub($user, $course);
        $info = ['code' => $course->getCode(), 'real_id' => $course->getId()];

        $this->assertFalse(CourseManager::is_user_accepted_legal($user->getId(), $course->getCode()));

        $this->assertTrue(CourseManager::save_user_legal($user->getId(), $info));
        $this->assertTrue(CourseManager::is_user_accepted_legal($user->getId(), $course->getCode()));
        $this->assertFalse(CourseManager::is_user_accepted_legal($stranger->getId(), $course->getCode()), 'only that user row is updated');
        $this->assertFalse(CourseManager::save_user_legal($user->getId(), []), 'empty course info is refused');
    }

    public function testLegalAcceptanceInASessionUsesTheSessionRelation(): void
    {
        $course = $this->createCourse('cm_legals_a');
        $session = $this->createSession('cm_legals_session');
        $user = $this->createUser('cm_legals_user');
        $this->sessionCourse($session->getId(), $course);
        $this->sessionCourseUser($session->getId(), $course, $user, 0);
        $info = ['code' => $course->getCode(), 'real_id' => $course->getId()];

        $this->assertFalse(CourseManager::is_user_accepted_legal($user->getId(), $course->getCode(), $session->getId()));
        $this->assertTrue(CourseManager::save_user_legal($user->getId(), $info, $session->getId()));
        $this->assertTrue(CourseManager::is_user_accepted_legal($user->getId(), $course->getCode(), $session->getId()));
        $this->assertFalse(CourseManager::is_user_accepted_legal($user->getId(), $course->getCode()), 'course-level relation is separate');
    }

    public function testCourseVotesAddUpdateRankAndRemove(): void
    {
        $course = $this->createCourse('cm_vote_a');
        $session = $this->createSession('cm_vote_session');
        $u1 = $this->createUser('cm_vote_one');
        $u2 = $this->createUser('cm_vote_two');
        $cid = (int) $course->getId();
        $sid = (int) $session->getId();
        $urlId = (int) api_get_current_access_url_id();

        $this->assertFalse(CourseManager::add_course_vote($u1->getId(), 6, $cid, $sid), 'vote must be 1..5');
        $this->assertFalse(CourseManager::add_course_vote(0, 3, $cid, $sid));
        $this->assertFalse(CourseManager::get_user_course_vote($u1->getId(), $cid, $sid, $urlId));

        $this->assertSame('added', CourseManager::add_course_vote($u1->getId(), 4, $cid, $sid));
        $this->assertSame('added', CourseManager::add_course_vote($u2->getId(), 2, $cid, $sid));
        $this->assertSame('updated', CourseManager::add_course_vote($u1->getId(), 5, $cid, $sid), 'one vote per user and course');

        $this->assertEquals(5, CourseManager::get_user_course_vote($u1->getId(), $cid, $sid, $urlId));

        $ranking = CourseManager::get_course_ranking($cid, $sid);
        $this->assertSame($cid, $ranking['c_id']);
        $this->assertSame(2, $ranking['users']);
        $this->assertSame(7, $ranking['total_score']);
        $this->assertSame(70.0, (float) $ranking['point_average'], '(7/2) * 100 / 5');
        $this->assertSame(3.5, (float) $ranking['point_average_star']);

        $this->assertSame(2, (int) $this->conn()->fetchOne('SELECT popularity FROM course WHERE id = ?', [$cid]), 'popularity = distinct voters');

        CourseManager::remove_course_ranking($cid);
        $this->assertSame(0, CourseManager::get_course_ranking($cid, $sid)['users']);
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT popularity FROM course WHERE id = ?', [$cid]));
        $this->assertSame([], CourseManager::get_course_ranking(0));
    }

    public function testAddCourseVoteWithoutASessionStoresNullAndCanBeReadBack(): void
    {
        // Fixed: with no session the vote was stored with session_id = 0, which the foreign key on
        // user_rel_course_vote.session_id rejects. It is now NULL (and get_user_course_vote matches it).
        $course = $this->createCourse('cm_votefk_a');
        $user = $this->createUser('cm_votefk_user');
        $conn = $this->conn();

        $this->assertSame('added', CourseManager::add_course_vote($user->getId(), 3, $course->getId()));
        $row = $conn->fetchAssociative('SELECT session_id, vote FROM user_rel_course_vote WHERE user_id = ? AND c_id = ?', [$user->getId(), $course->getId()]);
        $this->assertNull($row['session_id']);
        $this->assertSame(3, (int) $row['vote']);
        $this->assertSame(3, (int) CourseManager::get_user_course_vote($user->getId(), $course->getId()));

        $this->assertSame('updated', CourseManager::add_course_vote($user->getId(), 5, $course->getId()));
        $this->assertSame(
            1,
            (int) $conn->fetchOne('SELECT COUNT(*) FROM user_rel_course_vote WHERE user_id = ? AND c_id = ?', [$user->getId(), $course->getId()])
        );
        $this->assertSame(5, (int) CourseManager::get_user_course_vote($user->getId(), $course->getId()));
    }

    // ---------------------------------------------------------------- extra fields

    public function testCourseExtraFieldValueRoundTrip(): void
    {
        $course = $this->createCourse('cm_xf_a');
        $other = $this->createCourse('cm_xf_b');
        $variable = 'cm_xf_var';

        $fieldId = CourseManager::create_course_extra_field($variable, 1, 'cm_xf_label', 'dflt');
        $this->assertGreaterThan(0, (int) $fieldId);

        $this->assertNull(CourseManager::get_course_extra_field_value($variable, $course->getCode()), 'no value yet');

        $this->setCourseExtraValue($course, $variable, 'cm_xf_value');
        $this->assertSame('cm_xf_value', CourseManager::get_course_extra_field_value($variable, $course->getCode()));
        $this->assertNull(CourseManager::get_course_extra_field_value($variable, $other->getCode()));

        $this->assertSame((int) $course->getId(), (int) CourseManager::get_course_id_from_original_id('cm_xf_value', $variable));
        $this->assertSame(0, CourseManager::get_course_id_from_original_id('cm_nope', $variable));
    }

    public function testRawExtraFieldQueriesFilterOnFieldValue(): void
    {
        // Fixed: both methods filtered on extra_field_values.value, the column is field_value, so they always threw.
        $one = $this->createCourse('cm_xfr_a');
        $two = $this->createCourse('cm_xfr_b');
        $three = $this->createCourse('cm_xfr_c');
        CourseManager::create_course_extra_field('cm_xfr_var', 1, 'cm_xfr_label');
        $this->setCourseExtraValue($one, 'cm_xfr_var', 'cm_xfr_shared');
        $this->setCourseExtraValue($two, 'cm_xfr_var', 'cm_xfr_shared');
        $this->setCourseExtraValue($three, 'cm_xfr_var', 'cm_xfr_other');

        $this->assertSame(2, (int) CourseManager::getCountRegisteredUsersWithCourseExtraField('cm_xfr_shared'));
        $this->assertSame(1, (int) CourseManager::getCountRegisteredUsersWithCourseExtraField('cm_xfr_other'));
        $this->assertSame(0, CourseManager::getCountRegisteredUsersWithCourseExtraField('cm_xfr_none'));
        $this->assertSame(0, CourseManager::getCountRegisteredUsersWithCourseExtraField('-'));
        $this->assertSame(0, CourseManager::getCountRegisteredUsersWithCourseExtraField(''));

        // Despite its name it returns the course id (item_id), 0 when nothing matches.
        $this->assertSame((int) $three->getId(), (int) CourseManager::get_course_code_from_original_id('cm_xfr_other', 'cm_xfr_var'));
        $this->assertSame(0, CourseManager::get_course_code_from_original_id('cm_xfr_none', 'cm_xfr_var'));
        $this->assertSame(0, CourseManager::get_course_code_from_original_id('cm_xfr_other', 'cm_xfr_novar'));
    }

    public function testUpdateCourseExtraFieldValueSavesAndOverwritesTheValue(): void
    {
        // Fixed: the value was passed under the key 'value' while ExtraFieldValue::save() reads 'field_value',
        // so nothing was ever saved.
        $course = $this->createCourse('cm_xfb_a');
        CourseManager::create_course_extra_field('cm_xfb_var', 1, 'cm_xfb_label');

        CourseManager::update_course_extra_field_value($course->getCode(), 'cm_xfb_var', 'first');
        $this->assertSame('first', CourseManager::get_course_extra_field_value('cm_xfb_var', $course->getCode()));

        CourseManager::update_course_extra_field_value($course->getCode(), 'cm_xfb_var', 'second');
        $this->assertSame('second', CourseManager::get_course_extra_field_value('cm_xfb_var', $course->getCode()));
    }

    // ---------------------------------------------------------------- misc

    public function testGetCoursesWithoutSessionListsCoursesNotInAnySession(): void
    {
        // Fixed: the subquery selected session_rel_course.course_code (now c_id) and always threw.
        $free = $this->createCourse('cm_nos_free');
        $inSession = $this->createCourse('cm_nos_insess');
        $hidden = $this->createCourse('cm_nos_hidden');
        $this->setVisibility($hidden, Course::HIDDEN);
        $session = $this->createSession('cm_nos_session');
        $this->sessionCourse($session->getId(), $inSession);

        $ids = fn (array $rows): array => array_map('intval', $this->col($rows, 'id'));

        $default = $ids(CourseManager::getCoursesWithoutSession());
        $this->assertContains((int) $free->getId(), $default);
        $this->assertNotContains((int) $inSession->getId(), $default, 'a course in a session is excluded');
        $this->assertNotContains((int) $hidden->getId(), $default, 'hidden courses are excluded unless includeClosed');
        $this->assertContains((int) $hidden->getId(), $ids(CourseManager::getCoursesWithoutSession(null, null, true)));
        $this->assertEqualsCanonicalizing(['id', 'code', 'title', 0, 1, 2], array_keys(CourseManager::getCoursesWithoutSession()[0]));

        // With dates only the sessions of exactly that period count.
        $this->conn()->executeStatement(
            'UPDATE session SET access_start_date = ?, access_end_date = ? WHERE id = ?',
            ['2031-01-01 00:00:00', '2031-01-31 00:00:00', $session->getId()]
        );
        $samePeriod = $ids(CourseManager::getCoursesWithoutSession('2031-01-01 00:00:00', '2031-01-31 00:00:00'));
        $this->assertNotContains((int) $inSession->getId(), $samePeriod);
        $otherPeriod = $ids(CourseManager::getCoursesWithoutSession('2032-01-01 00:00:00', '2032-01-31 00:00:00'));
        $this->assertContains((int) $inSession->getId(), $otherPeriod, 'its session is not in that period');
    }

    public function testGetCourseByCategoryReadsGradebookCategory(): void
    {
        $course = $this->createCourse('cm_gb_a');
        $this->conn()->executeStatement(
            'INSERT INTO gradebook_category (c_id, title, weight, visible, locked, generate_certificates) VALUES (?, ?, 1, 1, 0, 0)',
            [$course->getId(), 'cm_gb_cat']
        );
        $categoryId = (int) $this->conn()->lastInsertId();

        $this->assertSame((int) $course->getId(), CourseManager::get_course_by_category($categoryId));
        $this->assertNull(CourseManager::get_course_by_category(99999999));
    }

    public function testGenerateCourseCodeAndNextCode(): void
    {
        $this->assertSame('CMGENERATEDCODE', CourseManager::generate_course_code('cm generated-code!'));
        $this->assertLessThanOrEqual(CourseManager::MAX_COURSE_LENGTH_CODE, strlen(CourseManager::generate_course_code(str_repeat('cm', 50))));

        $this->assertSame('CMNEXTFREE', CourseManager::generate_nice_next_course_code('CMNEXTFREE'), 'a free code is returned as is');

        $course = $this->createCourse('cm_next_a');
        $next = CourseManager::generate_nice_next_course_code($course->getCode());
        $this->assertStringStartsWith($course->getCode().'_', (string) $next, 'a taken code gets a _N suffix');
        $this->assertFalse(CourseManager::course_code_exists((string) $next));
    }

    public function testGetCourseCategoryReadsByCode(): void
    {
        $this->assertEmpty(CourseManager::get_course_category('CM_NO_SUCH_CATEGORY'));
        $this->conn()->executeStatement(
            'INSERT INTO course_category (code, title, tree_pos, children_count, auth_course_child) VALUES (?, ?, 1, 0, ?)',
            ['CM_CAT', 'cm_category', 'TRUE']
        );
        $row = CourseManager::get_course_category('CM_CAT');
        $this->assertSame('cm_category', $row['title']);
    }

    public function testGetAccessCourseFiltersAndGroupsPerDay(): void
    {
        $course = $this->createCourse('cm_acc_a');
        $user = $this->createUser('cm_acc_user');
        $conn = $this->conn();
        foreach ([['2031-01-10 08:00:00', 0], ['2031-01-10 18:00:00', 0], ['2031-01-12 09:00:00', 0], ['2031-01-12 09:30:00', 5]] as [$date, $sid]) {
            $conn->executeStatement(
                'INSERT INTO track_e_course_access (user_id, c_id, login_course_date, counter, session_id, user_ip) VALUES (?, ?, ?, 1, ?, ?)',
                [$user->getId(), $course->getId(), $date, $sid, '127.0.0.1']
            );
        }
        $cid = (int) $course->getId();

        $all = CourseManager::getAccessCourse($cid, 2, (int) $user->getId());
        $this->assertSame(
            ['2031-01-10', '2031-01-12'],
            $this->col($all, 'login_course_date'),
            'session_id is not selected, so SELECT DISTINCT merges the 12th with and without a session'
        );

        $noSession = CourseManager::getAccessCourse($cid, 0, (int) $user->getId());
        $this->assertSame(['2031-01-10', '2031-01-12'], $this->col($noSession, 'login_course_date'), 'two logins the same day collapse to one row');

        $onlySession = CourseManager::getAccessCourse($cid, 1, (int) $user->getId());
        $this->assertCount(1, $onlySession);

        $this->assertSame([], CourseManager::getAccessCourse($cid, 2, (int) $user->getId(), '10/01/2031'), 'malformed dates are refused');
        $this->assertSame([], CourseManager::getAccessCourse($cid, 2, 999999999), 'unknown user');
    }
}
