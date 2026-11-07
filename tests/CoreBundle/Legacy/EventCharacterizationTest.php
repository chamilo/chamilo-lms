<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Event;
use Exercise;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization of the legacy Event library (public/main/inc/lib/events.lib.php).
 *
 * Every test pins what the raw SQL currently stores or returns, so that a rewrite to DQL that changes the
 * rows written to the track_e_* tables, the ordering, the NULL/session handling or the clock handling
 * fails here. Fixtures are scoped to a course and users created by the test itself (prefix ev_).
 */
final class EventCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $exoSeq = 0;
    private int $userSeq = 0;
    private int $courseSeq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/events.lib.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        unset($_SERVER['HTTP_REFERER']);
        \ChamiloSession::erase('login_as');
        $_SERVER['HTTP_USER_AGENT'] = 'ev_agent';
        $_SERVER['REQUEST_URI'] = '/ev_uri';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_REFERER']);
        \ChamiloSession::erase('login_as');
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    /**
     * Creating users/courses is by far the most lock-contended part of these tests when sibling suites run
     * against the same database, so the fixture accounts/courses are reused first (DAMA rolls everything back,
     * and the track_e_* tables are empty for them) and a fresh one is only created beyond that.
     */
    private function makeUser(string $name): User
    {
        $fixtures = ['admin', 'anon', 'fallback_user'];
        if (isset($fixtures[$this->userSeq])) {
            return $this->getUser($fixtures[$this->userSeq++]);
        }

        return $this->createUser('ev_'.$name.'_'.(++$this->userSeq));
    }

    private function makeCourse(string $title): Course
    {
        if ($this->courseSeq < 2) {
            return $this->getCourse(++$this->courseSeq);
        }

        return $this->createCourse('ev_'.$title.'_'.(++$this->courseSeq));
    }

    /**
     * Sets the legacy "current context" (user, course, session) the way local.inc.php does.
     */
    private function setContext(?User $user, ?Course $course, int $sessionId = 0): void
    {
        \ChamiloSession::write('_user', null !== $user ? ['id' => $user->getId()] : null);
        if (null !== $course) {
            \ChamiloSession::write('_real_cid', $course->getId());
            \ChamiloSession::write('_cid', $course->getCode());
            \ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        } else {
            \ChamiloSession::erase('_real_cid');
            \ChamiloSession::erase('_cid');
            \ChamiloSession::erase('_course');
        }
        \ChamiloSession::write('sid', $sessionId);
        \ChamiloSession::erase('login_as');
    }

    private function utc(string $modify = 'now'): string
    {
        return gmdate('Y-m-d H:i:s', strtotime($modify));
    }

    private function assertNowish(string $dbDate, int $tolerance = 10): void
    {
        $this->assertLessThanOrEqual(
            $tolerance,
            abs(time() - strtotime($dbDate.' UTC')),
            "Date $dbDate is not within {$tolerance}s of now (UTC)"
        );
    }

    private function rows(string $table, string $where, array $params = [], string $order = ''): array
    {
        return $this->conn->fetchAllAssociative(
            "SELECT * FROM $table WHERE $where".('' !== $order ? " ORDER BY $order" : ''),
            $params
        );
    }

    /**
     * track_e_exercises.exe_exo_id is a foreign key to c_quiz, so a real (resource-less) quiz row is needed.
     */
    private function makeQuiz(): int
    {
        $this->conn->insert('c_quiz', [
            'title' => 'ev_quiz_'.(++$this->exoSeq),
            'type' => 1,
            'random' => 0,
            'random_answers' => 0,
            'results_disabled' => 0,
            'max_attempt' => 0,
            'feedback_type' => 0,
            'expired_time' => 0,
            'propagate_neg' => 0,
            'review_answers' => 0,
            'random_by_category' => 0,
            'display_category_name' => 0,
            'hide_question_title' => 0,
            'page_result_configuration' => '[]',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function makeSession(string $title): int
    {
        return (int) $this->createSession($title)->getId();
    }

    private function seedExercise(array $o): int
    {
        $row = $o + [
            'exe_user_id' => 0,
            'c_id' => 0,
            'session_id' => null,
            'exe_exo_id' => null,
            'exe_date' => '2020-01-01 10:00:00',
            'score' => 0,
            'max_score' => 0,
            'user_ip' => '10.0.0.1',
            'status' => '',
            'data_tracking' => '',
            'start_date' => '2020-01-01 09:00:00',
            'steps_counter' => 0,
            'orig_lp_id' => 0,
            'orig_lp_item_id' => 0,
            'exe_duration' => 0,
            'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ];
        $this->conn->insert('track_e_exercises', $row);

        return (int) $this->conn->lastInsertId();
    }

    private function seedAttempt(int $exeId, int $userId, int $questionId, array $o = []): int
    {
        $this->conn->insert('track_e_attempt', $o + [
            'exe_id' => $exeId,
            'user_id' => $userId,
            'question_id' => $questionId,
            'answer' => 'a',
            'teacher_comment' => '',
            'marks' => 1,
            'position' => 1,
            'tms' => '2020-01-01 10:00:00',
            'seconds_spent' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    // ------------------------------------------------------------------ login

    public function testLoginWritesOneRowWithEqualLoginAndLogoutDates(): void
    {
        $user = $this->makeUser('ev_login');
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM track_e_login WHERE login_user_id = ?', [$user->getId()]);

        $this->assertTrue(Event::eventLogin($user->getId()));

        $rows = $this->rows('track_e_login', 'login_user_id = ?', [$user->getId()]);
        $this->assertCount($before + 1, $rows);
        $row = $rows[0];
        $this->assertSame('127.0.0.1', $row['user_ip']);
        $this->assertSame($row['login_date'], $row['logout_date'], 'logout_date starts equal to login_date');
        $this->assertNowish($row['login_date']);
    }

    public function testLoginOfUnknownUserReturnsFalseAndWritesNothing(): void
    {
        $max = (int) $this->conn->fetchOne('SELECT COALESCE(MAX(login_id),0) FROM track_e_login');

        $this->assertFalse(Event::eventLogin(2147483000));

        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM track_e_login WHERE login_id > ? AND login_user_id = 2147483000', [$max]));
    }

    // ------------------------------------------------------------- addEvent

    public function testAddEventStoresScalarEventWithContextDefaults(): void
    {
        $user = $this->makeUser('ev_adder');
        $course = $this->makeCourse('ev_course_add');
        $this->setContext($user, $course, 0);

        $this->assertTrue(Event::addEvent('ev_type_scalar', 'ev_value_type', 'ev_value'));

        $rows = $this->rows('track_e_default', 'default_event_type = ?', ['ev_type_scalar']);
        $this->assertCount(1, $rows);
        $this->assertSame($user->getId(), (int) $rows[0]['default_user_id']);
        $this->assertSame($course->getId(), (int) $rows[0]['c_id']);
        $this->assertSame('ev_value_type', $rows[0]['default_value_type']);
        $this->assertSame('ev_value', $rows[0]['default_value']);
        $this->assertSame(0, (int) $rows[0]['session_id']);
        $this->assertNowish($rows[0]['default_date']);
    }

    public function testAddEventWithEmptyTypeIsRejected(): void
    {
        $this->assertFalse(Event::addEvent('', 'ev_vt', 'ev_val'));
    }

    public function testAddEventUsesExplicitArgumentsAndSerializesArrays(): void
    {
        $user = $this->makeUser('ev_adder2');
        $course = $this->makeCourse('ev_course_add2');
        $this->setContext(null, null);

        $this->assertTrue(Event::addEvent(
            'ev_type_array',
            'ev_array_type',
            ['a' => 1, 'b' => 'x'],
            '2021-03-04 05:06:07',
            $user->getId(),
            $course->getId(),
            7
        ));

        $row = $this->rows('track_e_default', 'default_event_type = ?', ['ev_type_array'])[0];
        $this->assertSame(serialize(['a' => 1, 'b' => 'x']), $row['default_value']);
        $this->assertSame('2021-03-04 05:06:07', $row['default_date']);
        $this->assertSame($user->getId(), (int) $row['default_user_id']);
        $this->assertSame($course->getId(), (int) $row['c_id']);
        $this->assertSame(7, (int) $row['session_id']);
    }

    public function testAddEventStripsSensitiveKeysFromUserObjectArray(): void
    {
        $user = $this->makeUser('ev_adder3');
        $this->setContext($user, null);
        $info = [
            'user_id' => 5,
            'username' => 'ev_x',
            'complete_name' => 'n',
            'complete_name_with_username' => 'n',
            'firstName' => 'f',
            'lastName' => 'l',
            'avatar_small' => 's',
            'avatar' => 'a',
            'mail' => 'm',
            'password' => 'p',
            'last_login' => 'l',
            'picture_uri' => 'u',
            'email' => 'ev@example.com',
        ];

        Event::addEvent('ev_type_userobj', LOG_USER_OBJECT, $info);

        $row = $this->rows('track_e_default', 'default_event_type = ?', ['ev_type_userobj'])[0];
        $this->assertSame(
            serialize(['user_id' => 5, 'username' => 'ev_x', 'email' => 'ev@example.com']),
            $row['default_value']
        );
    }

    public function testAddEventSerializesUserEntityAsFourFields(): void
    {
        $user = $this->makeUser('ev_adder4');
        $this->setContext($user, null);

        Event::addEvent('ev_type_userent', LOG_USER_OBJECT, $user);

        $row = $this->rows('track_e_default', 'default_event_type = ?', ['ev_type_userent'])[0];
        $this->assertSame(
            serialize([
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
            ]),
            $row['default_value']
        );
    }

    public function testSubscriptionWrappersWriteCourseCodeAndUserObjectEvents(): void
    {
        $registrant = $this->makeUser('ev_registrant');
        $subscribed = $this->makeUser('ev_subscribed');
        $course = $this->makeCourse('ev_course_sub');
        $this->setContext($registrant, null);

        Event::logSubscribedUserInCourse($subscribed, $course);

        $rows = $this->rows(
            'track_e_default',
            'default_event_type = ? AND c_id = ?',
            [LOG_SUBSCRIBE_USER_TO_COURSE, $course->getId()],
            'default_id'
        );
        $this->assertCount(2, $rows);
        $this->assertSame(LOG_COURSE_CODE, $rows[0]['default_value_type']);
        $this->assertSame($course->getCode(), $rows[0]['default_value']);
        $this->assertSame(LOG_USER_OBJECT, $rows[1]['default_value_type']);
        $this->assertSame($registrant->getId(), (int) $rows[0]['default_user_id']);
        $info = unserialize($rows[1]['default_value']);
        $this->assertSame($subscribed->getUsername(), $info['username']);
        $this->assertArrayNotHasKey('password', $info);
        $this->assertArrayNotHasKey('mail', $info);
        $this->assertSame($rows[0]['default_date'], $rows[1]['default_date'], 'both events share one timestamp');
    }

    public function testSessionSubscriptionWrapperWritesThreeEventsWithSession(): void
    {
        $registrant = $this->makeUser('ev_registrant2');
        $subscribed = $this->makeUser('ev_subscribed2');
        $course = $this->makeCourse('ev_course_sub2');
        $session = $this->createSession('ev_session_sub2');
        $this->setContext($registrant, null);

        Event::logUserSubscribedInCourseSession($subscribed, $course, $session);

        $rows = $this->rows('track_e_default', 'c_id = ? AND session_id = ?', [$course->getId(), $session->getId()], 'default_id');
        $this->assertCount(3, $rows);
        $this->assertSame(LOG_SESSION_ADD_USER_COURSE, $rows[0]['default_event_type']);
        $this->assertSame(LOG_USER_ID, $rows[0]['default_value_type']);
        // A User entity given with a non-LOG_USER_OBJECT type is not an array, so setDefaultValue gets the
        // entity cast to string by Doctrine/PHP: pin whatever lands in the column.
        $this->assertNotSame('', $rows[0]['default_value']);
        $this->assertSame(LOG_SUBSCRIBE_USER_TO_COURSE, $rows[1]['default_event_type']);
        $this->assertSame($course->getCode(), $rows[1]['default_value']);
        $this->assertSame(LOG_USER_OBJECT, $rows[2]['default_value_type']);
    }

    // ----------------------------------------------------- tool access / upload

    public function testAccessToolRecordsAccessAndLastAccessWhenComingFromTheCourse(): void
    {
        $user = $this->makeUser('ev_acc');
        $course = $this->makeCourse('ev_course_acc');
        $this->setContext($user, $course, 0);
        $_SERVER['HTTP_REFERER'] = api_get_path(WEB_COURSE_PATH).(api_get_course_info()['path'] ?? '').'/index';

        $this->assertTrue(Event::event_access_tool('ev_tool'));

        $access = $this->rows('track_e_access', 'access_user_id = ? AND c_id = ?', [$user->getId(), $course->getId()]);
        $this->assertCount(1, $access);
        $this->assertSame('ev_tool', $access[0]['access_tool']);
        $this->assertSame(0, (int) $access[0]['session_id']);
        $this->assertSame('127.0.0.1', $access[0]['user_ip']);
        $this->assertNowish($access[0]['access_date']);

        $last = $this->rows('track_e_lastaccess', 'access_user_id = ? AND c_id = ?', [$user->getId(), $course->getId()]);
        $this->assertCount(1, $last);
        $this->assertSame('ev_tool', $last[0]['access_tool']);
        $this->assertSame(0, (int) $last[0]['session_id']);
    }

    public function testAccessToolWithForeignRefererOnlyTouchesLastAccess(): void
    {
        $user = $this->makeUser('ev_acc2');
        $course = $this->makeCourse('ev_course_acc2');
        $this->setContext($user, $course, 0);
        $_SERVER['HTTP_REFERER'] = 'http://elsewhere.invalid/page';

        $this->assertTrue(Event::event_access_tool('ev_tool2'));

        $this->assertCount(0, $this->rows('track_e_access', 'access_user_id = ?', [$user->getId()]));
        $this->assertCount(1, $this->rows('track_e_lastaccess', 'access_user_id = ?', [$user->getId()]));
    }

    public function testAccessToolWithoutRefererOnlyTouchesLastAccess(): void
    {
        $user = $this->makeUser('ev_acc3');
        $course = $this->makeCourse('ev_course_acc3');
        $this->setContext($user, $course, 0);

        $this->assertTrue(Event::event_access_tool('ev_tool3'));

        $this->assertCount(0, $this->rows('track_e_access', 'access_user_id = ?', [$user->getId()]));
        $this->assertCount(1, $this->rows('track_e_lastaccess', 'access_user_id = ?', [$user->getId()]));
    }

    public function testAccessToolUpdatesTheExistingLastAccessRowInsteadOfInserting(): void
    {
        $user = $this->makeUser('ev_acc4');
        $course = $this->makeCourse('ev_course_acc4');
        $this->setContext($user, $course, 0);
        $this->conn->insert('track_e_lastaccess', [
            'access_user_id' => $user->getId(),
            'access_date' => '2020-01-01 00:00:00',
            'c_id' => $course->getId(),
            'access_tool' => 'ev_tool4',
            'session_id' => 0,
        ]);

        Event::event_access_tool('ev_tool4');

        $last = $this->rows('track_e_lastaccess', 'access_user_id = ?', [$user->getId()]);
        $this->assertCount(1, $last);
        $this->assertNowish($last[0]['access_date']);
    }

    public function testAccessToolIsIgnoredWithoutToolWithoutCourseOrWhenLoggedInAs(): void
    {
        $user = $this->makeUser('ev_acc5');
        $course = $this->makeCourse('ev_course_acc5');

        $this->setContext($user, $course, 0);
        $this->assertFalse(Event::event_access_tool(''));

        $this->setContext($user, null);
        $this->assertFalse(Event::event_access_tool('ev_tool5'));

        $this->setContext($user, $course, 0);
        \ChamiloSession::write('login_as', 1);
        $this->assertFalse(Event::event_access_tool('ev_tool5'));

        $this->assertCount(0, $this->rows('track_e_lastaccess', 'access_user_id = ?', [$user->getId()]));
    }

    public function testUploadAndLinkEventsStoreContextAndReturnOne(): void
    {
        $user = $this->makeUser('ev_upl');
        $course = $this->makeCourse('ev_course_upl');
        $this->setContext($user, $course, 0);

        $this->assertSame(1, Event::event_upload(4242));
        $this->assertSame(1, Event::event_link(5353));

        $up = $this->rows('track_e_uploads', 'upload_user_id = ?', [$user->getId()]);
        $this->assertCount(1, $up);
        $this->assertSame($course->getId(), (int) $up[0]['c_id']);
        $this->assertSame(4242, (int) $up[0]['upload_work_id']);
        $this->assertSame(0, (int) $up[0]['session_id']);
        $this->assertNowish($up[0]['upload_date']);

        $link = $this->rows('track_e_links', 'links_user_id = ?', [$user->getId()]);
        $this->assertCount(1, $link);
        $this->assertSame($course->getId(), (int) $link[0]['c_id']);
        $this->assertSame(5353, (int) $link[0]['links_link_id']);
        $this->assertNowish($link[0]['links_date']);
    }

    public function testUploadLinkAndDownloadAreSkippedWhenLoggedInAs(): void
    {
        $user = $this->makeUser('ev_upl2');
        $course = $this->makeCourse('ev_course_upl2');
        $this->setContext($user, $course, 0);
        \ChamiloSession::write('login_as', 1);

        $this->assertSame(0, Event::event_upload(1));
        $this->assertSame(0, Event::event_link(1));
        $this->assertSame(0, Event::event_download('x'));

        $this->assertCount(0, $this->rows('track_e_uploads', 'upload_user_id = ?', [$user->getId()]));
        $this->assertCount(0, $this->rows('track_e_links', 'links_user_id = ?', [$user->getId()]));
    }

    public function testDownloadStoresPathUserAndReturnsTheNewId(): void
    {
        $user = $this->makeUser('ev_dl');
        $course = $this->makeCourse('ev_course_dl');
        $this->setContext($user, $course, 0);

        $id = Event::event_download('ev/path/file.pdf');

        $this->assertGreaterThan(0, $id);
        $rows = $this->rows('track_e_downloads', 'down_user_id = ?', [$user->getId()]);
        $this->assertCount(1, $rows);
        $this->assertSame($id, (int) $rows[0]['down_id']);
        $this->assertSame('ev/path/file.pdf', $rows[0]['down_doc_path']);
    }

    // ---------------------------------------------------------- course access

    public function testCourseLoginInsertsFirstAccessRowWithCounterOne(): void
    {
        $user = $this->makeUser('ev_cl');
        $course = $this->makeCourse('ev_course_cl');

        $this->assertTrue(Event::eventCourseLogin($course->getId(), $user->getId(), 0));

        $rows = $this->rows('track_e_course_access', 'user_id = ? AND c_id = ?', [$user->getId(), $course->getId()]);
        $this->assertCount(1, $rows);
        $this->assertSame(1, (int) $rows[0]['counter']);
        $this->assertSame(0, (int) $rows[0]['session_id']);
        $this->assertSame('127.0.0.1', $rows[0]['user_ip']);
        $this->assertSame($rows[0]['login_course_date'], $rows[0]['logout_course_date']);
        $this->assertNowish($rows[0]['login_course_date']);
    }

    public function testCourseLoginIsSkippedWhenLoggedInAs(): void
    {
        $user = $this->makeUser('ev_cl2');
        $course = $this->makeCourse('ev_course_cl2');
        $this->setContext($user, $course);
        \ChamiloSession::write('login_as', 1);

        $this->assertFalse(Event::eventCourseLogin($course->getId(), $user->getId(), 0));
        $this->assertFalse(Event::eventCourseLoginUpdate($course->getId(), $user->getId(), 0));
        $this->assertFalse(Event::courseLogout(['uid' => $user->getId(), 'cid' => $course->getId(), 'sid' => 0]));

        $this->assertCount(0, $this->rows('track_e_course_access', 'user_id = ?', [$user->getId()]));
    }

    private function seedCourseAccess(int $userId, int $courseId, string $login, string $logout, int $sid = 0, int $counter = 1): int
    {
        $this->conn->insert('track_e_course_access', [
            'user_id' => $userId,
            'c_id' => $courseId,
            'login_course_date' => $login,
            'logout_course_date' => $logout,
            'counter' => $counter,
            'session_id' => $sid,
            'user_ip' => '10.0.0.9',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function testCourseLoginUpdateRefreshesLogoutOnlyBetweenFiveMinutesAndOneHour(): void
    {
        $user = $this->makeUser('ev_clu');
        $course = $this->makeCourse('ev_course_clu');
        $cid = $course->getId();
        $uid = $user->getId();

        // Idle for 10 minutes: refreshed and the counter bumped.
        $old = $this->utc('-10 minutes');
        $id = $this->seedCourseAccess($uid, $cid, $this->utc('-30 minutes'), $old);
        $this->assertTrue(Event::eventCourseLoginUpdate($cid, $uid, 0));
        $row = $this->rows('track_e_course_access', 'course_access_id = ?', [$id])[0];
        $this->assertSame(2, (int) $row['counter']);
        $this->assertNowish($row['logout_course_date']);

        // Idle for 1 minute (< 5): untouched but still true.
        $fresh = $this->utc('-1 minutes');
        $this->conn->update('track_e_course_access', ['logout_course_date' => $fresh], ['course_access_id' => $id]);
        $this->assertTrue(Event::eventCourseLoginUpdate($cid, $uid, 0));
        $row = $this->rows('track_e_course_access', 'course_access_id = ?', [$id])[0];
        $this->assertSame($fresh, $row['logout_course_date']);
        $this->assertSame(2, (int) $row['counter']);

        // Idle for 2 hours (> 1 h): untouched, still true.
        $stale = $this->utc('-2 hours');
        $this->conn->update('track_e_course_access', ['logout_course_date' => $stale], ['course_access_id' => $id]);
        $this->assertTrue(Event::eventCourseLoginUpdate($cid, $uid, 0));
        $this->assertSame($stale, $this->rows('track_e_course_access', 'course_access_id = ?', [$id])[0]['logout_course_date']);

        // Custom threshold of 1 minute makes the 1-minute-idle row eligible.
        $this->conn->update('track_e_course_access', ['logout_course_date' => $this->utc('-2 minutes')], ['course_access_id' => $id]);
        $this->assertTrue(Event::eventCourseLoginUpdate($cid, $uid, 0, 1));
        $this->assertSame(3, (int) $this->rows('track_e_course_access', 'course_access_id = ?', [$id])[0]['counter']);
    }

    public function testCourseLoginUpdateWorksOnTheLatestRowAndReturnsFalseWithoutAny(): void
    {
        $user = $this->makeUser('ev_clu2');
        $course = $this->makeCourse('ev_course_clu2');
        $cid = $course->getId();
        $uid = $user->getId();

        $this->assertFalse(Event::eventCourseLoginUpdate($cid, $uid, 0));
        $this->assertFalse(Event::eventCourseLoginUpdate(0, $uid, 0));
        $this->assertFalse(Event::eventCourseLoginUpdate($cid, 0, 0));

        $olderId = $this->seedCourseAccess($uid, $cid, $this->utc('-3 hours'), $this->utc('-3 hours'));
        $newerId = $this->seedCourseAccess($uid, $cid, $this->utc('-20 minutes'), $this->utc('-10 minutes'));
        $otherSession = $this->seedCourseAccess($uid, $cid, $this->utc('-20 minutes'), $this->utc('-10 minutes'), 99);

        $this->assertTrue(Event::eventCourseLoginUpdate($cid, $uid, 0));

        $this->assertSame(1, (int) $this->rows('track_e_course_access', 'course_access_id = ?', [$olderId])[0]['counter']);
        $this->assertSame(2, (int) $this->rows('track_e_course_access', 'course_access_id = ?', [$newerId])[0]['counter']);
        $this->assertSame(1, (int) $this->rows('track_e_course_access', 'course_access_id = ?', [$otherSession])[0]['counter']);
    }

    public function testCourseLogoutUpdatesRecentRowOtherwiseInserts(): void
    {
        $user = $this->makeUser('ev_clo');
        $course = $this->makeCourse('ev_course_clo');
        $cid = $course->getId();
        $uid = $user->getId();
        $info = ['uid' => $uid, 'cid' => $cid, 'sid' => 0];

        // No row at all: a fresh one is inserted with counter 1 and equal dates.
        $this->assertTrue(Event::courseLogout($info));
        $rows = $this->rows('track_e_course_access', 'user_id = ? AND c_id = ?', [$uid, $cid]);
        $this->assertCount(1, $rows);
        $this->assertSame(1, (int) $rows[0]['counter']);
        $this->assertSame($rows[0]['login_course_date'], $rows[0]['logout_course_date']);
        $this->assertSame('127.0.0.1', $rows[0]['user_ip']);

        // A login within the last hour is refreshed in place.
        $id = $this->seedCourseAccess($uid, $cid, $this->utc('-5 minutes'), $this->utc('-5 minutes'), 0, 4);
        $this->conn->delete('track_e_course_access', ['course_access_id' => (int) $rows[0]['course_access_id']]);
        $this->assertTrue(Event::courseLogout($info));
        $rows = $this->rows('track_e_course_access', 'user_id = ? AND c_id = ?', [$uid, $cid]);
        $this->assertCount(1, $rows);
        $this->assertSame($id, (int) $rows[0]['course_access_id']);
        $this->assertSame(5, (int) $rows[0]['counter']);
        $this->assertNowish($rows[0]['logout_course_date']);

        // A login older than the one hour lifetime leads to a new row.
        $this->conn->update('track_e_course_access', ['login_course_date' => $this->utc('-3 hours')], ['course_access_id' => $id]);
        $this->assertTrue(Event::courseLogout($info));
        $this->assertCount(2, $this->rows('track_e_course_access', 'user_id = ? AND c_id = ?', [$uid, $cid]));
    }

    public function testCourseLogoutRejectsIncompleteInfo(): void
    {
        $this->assertFalse(Event::courseLogout([]));
        $this->assertFalse(Event::courseLogout(['uid' => 5]));
        $this->assertFalse(Event::courseLogout(['cid' => 5]));
    }

    // -------------------------------------------------------- virtual time

    public function testVirtualCourseTimeAddsCourseAndPlatformRowsAndRemovalDeletesThem(): void
    {
        $user = $this->makeUser('ev_virt');
        $course = $this->makeCourse('ev_course_virt');
        $cid = $course->getId();
        $uid = $user->getId();
        $this->setContext($user, $course);

        $this->assertFalse(Event::eventAddVirtualCourseTime($cid, $uid, 0, '', 1));
        $this->assertTrue(Event::eventAddVirtualCourseTime($cid, $uid, 0, '01:30:00', 77));

        $course = $this->rows('track_e_course_access', 'user_id = ? AND c_id = ?', [$uid, $cid]);
        $this->assertCount(1, $course);
        $this->assertSame(0, (int) $course[0]['counter']);
        $this->assertSame(
            5400,
            strtotime($course[0]['logout_course_date']) - strtotime($course[0]['login_course_date'])
        );
        $this->assertNowish($course[0]['logout_course_date']);
        $platform = $this->rows('track_e_login', 'login_user_id = ?', [$uid]);
        $this->assertCount(1, $platform);
        $this->assertSame($course[0]['login_course_date'], $platform[0]['login_date']);
        $this->assertSame($course[0]['logout_course_date'], $platform[0]['logout_date']);

        // Removal is keyed on the exact duration; it returns true only when it deleted something (fixed: it used
        // to return false even after deleting).
        $this->assertFalse(Event::eventRemoveVirtualCourseTime($cid, $uid, 0, '', 77));
        $this->assertCount(1, $this->rows('track_e_course_access', 'user_id = ?', [$uid]));
        $this->assertFalse(Event::eventRemoveVirtualCourseTime($cid, $uid, 0, '02:00:00', 77));
        $this->assertCount(1, $this->rows('track_e_course_access', 'user_id = ?', [$uid]));
        $this->assertTrue(Event::eventRemoveVirtualCourseTime($cid, $uid, 0, '01:30:00', 77));
        $this->assertFalse(Event::eventRemoveVirtualCourseTime($cid, $uid, 0, '01:30:00', 77), 'nothing left to remove');
        $this->assertCount(0, $this->rows('track_e_course_access', 'user_id = ?', [$uid]));
        $this->assertCount(0, $this->rows('track_e_login', 'login_user_id = ?', [$uid]));
    }

    public function testRegisterLogDoesNothingWhileMinimumTimeTrackingIsOff(): void
    {
        $user = $this->makeUser('ev_reg');
        $course = $this->makeCourse('ev_course_reg');
        $this->setContext($user, $course);

        $this->assertFalse(Event::registerLog(['tool' => 'ev_tool', 'action' => 'ev_action']));
        $this->assertCount(0, $this->rows('track_e_access_complete', 'user_id = ?', [$user->getId()]));
    }

    // ------------------------------------------------------ exercise writers

    public function testUpdateEventExerciseOverwritesTheAttemptRow(): void
    {
        $user = $this->makeUser('ev_upd');
        $course = $this->makeCourse('ev_course_upd');
        $this->setContext($user, $course);
        $exo = $this->makeQuiz();
        $exo2 = $this->makeQuiz();
        $exe = $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $this->makeQuiz(), 'status' => 'incomplete']);

        $this->assertFalse(Event::updateEventExercise(0, $exo, 1.5, 3, 0));
        $this->assertTrue(Event::updateEventExercise($exe, $exo, 1.5, 3, 0, 11, 12, 13, 45, [], 'ev_status', [4, 0, 5], '2022-02-02 02:02:02'));

        $row = $this->rows('track_e_exercises', 'exe_id = ?', [$exe])[0];
        $this->assertSame($exo, (int) $row['exe_exo_id']);
        $this->assertEquals(1.5, $row['score']);
        $this->assertEquals(3, $row['max_score']);
        $this->assertSame(11, (int) $row['orig_lp_id']);
        $this->assertSame(12, (int) $row['orig_lp_item_id']);
        $this->assertSame(13, (int) $row['orig_lp_item_view_id']);
        $this->assertSame(45, (int) $row['exe_duration']);
        $this->assertSame('2022-02-02 02:02:02', $row['exe_date']);
        $this->assertSame('ev_status', $row['status']);
        $this->assertSame('4,5', $row['questions_to_check'], 'zero ids are filtered out of the remind list');
        $this->assertSame('', $row['data_tracking']);
        $this->assertSame('127.0.0.1', $row['user_ip']);
        // Untouched columns.
        $this->assertSame($user->getId(), (int) $row['exe_user_id']);
        $this->assertSame('2020-01-01 09:00:00', $row['start_date']);
    }

    public function testUpdateEventExerciseDefaultsEndDateToNow(): void
    {
        $user = $this->makeUser('ev_upd2');
        $course = $this->makeCourse('ev_course_upd2');
        $exe = $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'status' => 'incomplete']);

        Event::updateEventExercise($exe, $this->makeQuiz(), 2.0, 4, 0);

        $row = $this->rows('track_e_exercises', 'exe_id = ?', [$exe])[0];
        $this->assertNowish($row['exe_date']);
        $this->assertSame('', $row['status']);
        $this->assertSame('', $row['questions_to_check']);
    }

    public function testSaveQuestionAttemptInsertsThenRefusesDuplicatesUnlessUpdating(): void
    {
        $user = $this->makeUser('ev_sqa');
        $course = $this->makeCourse('ev_course_sqa');
        $this->setContext($user, $course);
        $exe = $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $course->getId(), 'exe_exo_id' => $this->makeQuiz()]);
        $exercise = new Exercise($course->getId());

        $id = Event::saveQuestionAttempt($exercise, 2.5, 'my answer', 321, $exe, 3, 1, false, 12, '/some/dir/audio.wav', $user->getId(), $course->getId(), 0, 0, 0);

        $this->assertGreaterThan(0, $id);
        $rows = $this->rows('track_e_attempt', 'exe_id = ?', [$exe]);
        $this->assertCount(1, $rows);
        $this->assertSame($id, (int) $rows[0]['id']);
        $this->assertSame($user->getId(), (int) $rows[0]['user_id']);
        $this->assertSame(321, (int) $rows[0]['question_id']);
        $this->assertSame('my answer', $rows[0]['answer']);
        $this->assertEquals(2.5, $rows[0]['marks']);
        $this->assertSame(3, (int) $rows[0]['position']);
        $this->assertSame('audio.wav', $rows[0]['filename'], 'only the basename is stored');
        $this->assertSame('', $rows[0]['teacher_comment']);
        $this->assertSame(12, (int) $rows[0]['seconds_spent']);
        $this->assertNowish($rows[0]['tms']);

        // Same exe/user/question/position without $updateResults: refused.
        $this->assertFalse(Event::saveQuestionAttempt($exercise, 9.0, 'other', 321, $exe, 3, 1, false, 0, null, $user->getId(), $course->getId(), 0, 0, 0));
        $this->assertCount(1, $this->rows('track_e_attempt', 'exe_id = ?', [$exe]));
        $this->assertSame('my answer', $this->rows('track_e_attempt', 'exe_id = ?', [$exe])[0]['answer']);

        // With $updateResults the row is rewritten and the exe_id is returned.
        $this->assertSame($exe, Event::saveQuestionAttempt($exercise, 9.0, 'other', 321, $exe, 3, 1, true, 0, null, $user->getId(), $course->getId(), 0, 0, 0));
        $rows = $this->rows('track_e_attempt', 'exe_id = ?', [$exe]);
        $this->assertCount(1, $rows);
        $this->assertSame('other', $rows[0]['answer']);
        $this->assertEquals(9.0, $rows[0]['marks']);
    }

    public function testSaveQuestionAttemptRejectsMissingIdsAndStoresEmptyAnswerForNull(): void
    {
        $user = $this->makeUser('ev_sqa2');
        $course = $this->makeCourse('ev_course_sqa2');
        $this->setContext($user, $course);
        $exe = $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $course->getId()]);
        $exercise = new Exercise($course->getId());

        $this->assertFalse(Event::saveQuestionAttempt($exercise, 1.0, 'a', 0, $exe, 1, 1, false, 0, null, $user->getId()));
        $this->assertFalse(Event::saveQuestionAttempt($exercise, 1.0, 'a', 5, 0, 1, 1, false, 0, null, $user->getId()));

        // The user defaults to the one of the current context.
        $this->assertGreaterThan(0, Event::saveQuestionAttempt($exercise, 1.0, null, 7, $exe, 1));
        $row = $this->rows('track_e_attempt', 'exe_id = ? AND question_id = 7', [$exe])[0];
        $this->assertSame('', $row['answer']);
        $this->assertSame($user->getId(), (int) $row['user_id']);
        $this->assertNull($row['filename']);
    }

    public function testHotspotAttemptInsertThenUpdate(): void
    {
        $user = $this->makeUser('ev_hot');
        $course = $this->makeCourse('ev_course_hot');
        $this->setContext($user, $course);
        $exe = $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $course->getId()]);
        $exercise = new Exercise($course->getId());

        $this->assertSame(0, Event::saveExerciseAttemptHotspot($exercise, 0, 5, 6, 1, '1;2'));
        $id = Event::saveExerciseAttemptHotspot($exercise, $exe, 5, 6, 1, '10;20');
        $this->assertGreaterThan(0, $id);

        $rows = $this->rows('track_e_hotspot', 'hotspot_exe_id = ?', [$exe]);
        $this->assertCount(1, $rows);
        $this->assertSame($id, (int) $rows[0]['hotspot_id']);
        $this->assertSame($user->getId(), (int) $rows[0]['hotspot_user_id']);
        $this->assertSame($course->getId(), (int) $rows[0]['c_id']);
        $this->assertSame(5, (int) $rows[0]['hotspot_question_id']);
        $this->assertSame(6, (int) $rows[0]['hotspot_answer_id']);
        $this->assertSame(1, (int) $rows[0]['hotspot_correct']);
        $this->assertSame('10;20', $rows[0]['hotspot_coordinate']);

        $this->assertSame(1, Event::saveExerciseAttemptHotspot($exercise, $exe, 5, 6, 0, '30;40', true));
        $rows = $this->rows('track_e_hotspot', 'hotspot_exe_id = ?', [$exe]);
        $this->assertSame(0, (int) $rows[0]['hotspot_correct']);
        $this->assertSame('30;40', $rows[0]['hotspot_coordinate']);

        // Updating a spot that was never recorded reports 0.
        $this->assertSame(0, Event::saveExerciseAttemptHotspot($exercise, $exe, 5, 999, 1, '0;0', true));
    }

    public function testDeleteAttemptRemovesOneAnswerAndLogsTheDeletion(): void
    {
        $user = $this->makeUser('ev_dela');
        $course = $this->makeCourse('ev_course_dela');
        $this->setContext($user, $course);
        $exe = $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $course->getId()]);
        $this->seedAttempt($exe, $user->getId(), 10);
        $this->seedAttempt($exe, $user->getId(), 11);

        Event::delete_attempt($exe, $user->getId(), $course->getId(), 0, 10);

        $left = $this->rows('track_e_attempt', 'exe_id = ?', [$exe]);
        $this->assertCount(1, $left);
        $this->assertSame(11, (int) $left[0]['question_id']);
        $log = $this->rows('track_e_default', 'default_event_type = ? AND default_value = ?', [LOG_QUESTION_RESULT_DELETE, $exe.'-10']);
        $this->assertCount(1, $log);
        $this->assertSame(LOG_EXERCISE_ATTEMPT_QUESTION_ID, $log[0]['default_value_type']);
        $this->assertSame($course->getId(), (int) $log[0]['c_id']);
        $this->assertSame($user->getId(), (int) $log[0]['default_user_id']);
    }

    public function testDeleteAttemptHotspotRemovesOnlyTheMatchingSpot(): void
    {
        $user = $this->makeUser('ev_delh');
        $course = $this->makeCourse('ev_course_delh');
        $this->setContext($user, $course);
        $exe = $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $course->getId()]);
        $exercise = new Exercise($course->getId());
        Event::saveExerciseAttemptHotspot($exercise, $exe, 5, 1, 1, 'a');
        Event::saveExerciseAttemptHotspot($exercise, $exe, 6, 1, 1, 'b');

        Event::delete_attempt_hotspot($exe, $user->getId(), $course->getId(), 5);

        $left = $this->rows('track_e_hotspot', 'hotspot_exe_id = ?', [$exe]);
        $this->assertCount(1, $left);
        $this->assertSame(6, (int) $left[0]['hotspot_question_id']);
        $this->assertCount(1, $this->rows('track_e_default', 'default_event_type = ? AND default_value = ?', [LOG_QUESTION_RESULT_DELETE, $exe.'-5']));
    }

    public function testDeleteAllIncompleteAttemptsOnlyRemovesIncompleteOnesOfThatExercise(): void
    {
        $user = $this->makeUser('ev_deli');
        $course = $this->makeCourse('ev_course_deli');
        $this->setContext($user, $course);
        $exo = $this->makeQuiz();
        $exo2 = $this->makeQuiz();
        $cid = $course->getId();
        $uid = $user->getId();
        $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo, 'status' => 'incomplete']);
        $done = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo, 'status' => '']);
        $otherExo = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo2, 'status' => 'incomplete']);

        Event::delete_all_incomplete_attempts($uid, $exo, $cid, 0);

        $left = array_map('intval', array_column($this->rows('track_e_exercises', 'exe_user_id = ? AND c_id = ?', [$uid, $cid], 'exe_id'), 'exe_id'));
        $this->assertSame([$done, $otherExo], $left);
        $log = $this->rows('track_e_default', 'default_event_type = ? AND c_id = ?', [LOG_EXERCISE_RESULT_DELETE, $cid]);
        $this->assertCount(1, $log);
        $this->assertSame(LOG_EXERCISE_AND_USER_ID, $log[0]['default_value_type']);
        $this->assertSame($exo.'-'.$uid, $log[0]['default_value']);
    }

    public function testDeleteStudentLpEventsDeletesResultsOrDetachesThemFromTheLp(): void
    {
        $user = $this->makeUser('dlp');
        $course = $this->makeCourse('course_dlp');
        $this->setContext($user, $course);
        $cid = $course->getId();
        $uid = $user->getId();
        $sid = $this->makeSession('ev_s_dlp');
        $lpId = 880001;
        $lp = ['orig_lp_id' => $lpId, 'orig_lp_item_id' => 3, 'orig_lp_item_view_id' => 4];

        $inLp = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'session_id' => $sid, 'exe_exo_id' => $this->makeQuiz()] + $lp);
        $this->seedAttempt($inLp, $uid, 1);
        $outside = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'session_id' => $sid, 'exe_exo_id' => $this->makeQuiz()]);
        // A result stored without session (NULL) must be matched by session id 0 (fixed: the SELECT used to
        // lack the IS NULL branch, so session-0 cleanups silently left these results behind).
        $noSession = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $this->makeQuiz()] + $lp);

        $this->assertFalse(Event::delete_student_lp_events(0, $lpId, ['real_id' => $cid], $sid));
        $this->assertFalse(Event::delete_student_lp_events($uid, $lpId, [], $sid));

        // Detach mode used to set the NOT NULL orig_lp_* columns to NULL, so the UPDATE failed with
        // NotAllowedException. Fixed: they are reset to 0, the "not played from a learning path" value of those
        // columns (what seedExercise/outside results hold); the result and its answers are kept.
        $this->assertTrue(Event::delete_student_lp_events($uid, $lpId, ['real_id' => $cid], $sid, true));
        $row = $this->rows('track_e_exercises', 'exe_id = ?', [$inLp])[0];
        $this->assertSame(0, (int) $row['orig_lp_id']);
        $this->assertSame(0, (int) $row['orig_lp_item_id']);
        $this->assertSame(0, (int) $row['orig_lp_item_view_id']);
        $this->assertCount(1, $this->rows('track_e_attempt', 'exe_id = ?', [$inLp]));
        $this->assertSame($lpId, (int) $this->rows('track_e_exercises', 'exe_id = ?', [$noSession])[0]['orig_lp_id'], 'other session untouched');

        // The destructive mode removes the results with their answers (inLp is already detached, so it survives).
        $inLp2 = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'session_id' => $sid, 'exe_exo_id' => $this->makeQuiz()] + $lp);
        $this->seedAttempt($inLp2, $uid, 2);
        $this->assertTrue(Event::delete_student_lp_events($uid, $lpId, ['real_id' => $cid], $sid));
        $this->assertCount(0, $this->rows('track_e_exercises', 'exe_id = ?', [$inLp2]));
        $this->assertCount(0, $this->rows('track_e_attempt', 'exe_id = ?', [$inLp2]));
        $this->assertCount(1, $this->rows('track_e_exercises', 'exe_id = ?', [$inLp]), 'detached result survives');
        $this->assertCount(1, $this->rows('track_e_exercises', 'exe_id = ?', [$outside]), 'results outside the LP survive');
        $this->assertCount(1, $this->rows('track_e_exercises', 'exe_id = ?', [$noSession]), 'NULL-session results survive a session cleanup');
        $this->assertTrue(Event::delete_student_lp_events($uid, $lpId, ['real_id' => $cid], 0));
        $this->assertCount(0, $this->rows('track_e_exercises', 'exe_id = ?', [$noSession]), 'session 0 matches NULL-session results');

        $log = $this->rows('track_e_default', 'default_event_type = ? AND default_value = ?', [LOG_LP_ATTEMPT_DELETE, (string) $lpId], 'default_id');
        $this->assertCount(3, $log, 'one log per call that reached the end');
        $this->assertSame(LOG_LP_ID, $log[0]['default_value_type']);
        $this->assertSame($cid, (int) $log[0]['c_id']);
        $this->assertSame($sid, (int) $log[0]['session_id']);
    }

    // ------------------------------------------------------ exercise readers

    public function testAttemptCountIgnoresIncompleteAndOtherLpContexts(): void
    {
        $user = $this->makeUser('ev_cnt');
        $course = $this->makeCourse('ev_course_cnt');
        $this->setContext($user, $course, 0);
        $cid = $course->getId();
        $uid = $user->getId();
        $exo = $this->makeQuiz();
        $exo2 = $this->makeQuiz();
        $base = ['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo, 'orig_lp_id' => 2, 'orig_lp_item_id' => 3, 'orig_lp_item_view_id' => 4];
        $this->seedExercise($base);
        $this->seedExercise($base + ['status' => 'ev_other']);
        $this->seedExercise(['status' => 'incomplete'] + $base);
        $this->seedExercise(['orig_lp_item_view_id' => 5] + $base);
        $this->seedExercise(['session_id' => $this->makeSession('ev_s_cnt')] + $base);

        $this->assertSame(2, Event::get_attempt_count($uid, $exo, 2, 3, 4));
        $this->assertSame(0, Event::get_attempt_count($uid, $exo2, 2, 3, 4));
    }

    public function testAttemptPositionIsTheRankAmongBlankStatusAttemptsOfTheSameContext(): void
    {
        $user = $this->makeUser('ev_pos');
        $course = $this->makeCourse('ev_course_pos');
        $this->setContext($user, $course, 0);
        $cid = $course->getId();
        $uid = $user->getId();
        $exo = $this->makeQuiz();
        $exo2 = $this->makeQuiz();
        $base = ['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo];
        $first = $this->seedExercise($base);
        $this->seedExercise(['status' => 'incomplete'] + $base);
        $second = $this->seedExercise($base);
        $third = $this->seedExercise($base);

        $this->assertSame(1, Event::getAttemptPosition($first, $uid, $exo, 0, 0, 0));
        $this->assertSame(2, Event::getAttemptPosition($second, $uid, $exo, 0, 0, 0));
        $this->assertSame(3, Event::getAttemptPosition($third, $uid, $exo, 0, 0, 0));
        // An id that is not in the list ends one past the end; no attempt at all gives 0.
        $this->assertSame(4, Event::getAttemptPosition(1, $uid, $exo, 0, 0, 0));
        $this->assertSame(0, Event::getAttemptPosition($first, $uid, $exo2, 0, 0, 0));
    }

    public function testAllExerciseResultsAreOrderedKeyedByExeIdAndCarryTheirAnswers(): void
    {
        $user = $this->makeUser('ev_all');
        $other = $this->makeUser('ev_all_other');
        $course = $this->makeCourse('ev_course_all');
        $sid = $this->makeSession('ev_s_all');
        $cid = $course->getId();
        $exo = $this->makeQuiz();
        $exo2 = $this->makeQuiz();
        $a = $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $cid, 'exe_exo_id' => $exo, 'score' => 1, 'max_score' => 2]);
        $b = $this->seedExercise(['exe_user_id' => $other->getId(), 'c_id' => $cid, 'exe_exo_id' => $exo]);
        $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $cid, 'exe_exo_id' => $exo, 'status' => 'incomplete']);
        $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $cid, 'exe_exo_id' => $exo, 'orig_lp_id' => 1]);
        $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $cid, 'exe_exo_id' => $exo, 'session_id' => $sid]);
        $this->seedAttempt($a, $user->getId(), 31, ['answer' => 'x', 'marks' => 1]);

        $all = Event::get_all_exercise_results($exo, $cid, 0);
        $this->assertSame([$a, $b], array_keys($all));
        $this->assertEquals(1, $all[$a]['score']);
        $this->assertSame([31], array_keys($all[$a]['question_list']));
        $this->assertSame('x', $all[$a]['question_list'][31]['answer']);
        $this->assertArrayNotHasKey('question_list', $all[$b]);

        $noQuestions = Event::get_all_exercise_results($exo, $cid, 0, false);
        $this->assertArrayNotHasKey('question_list', $noQuestions[$a]);

        $onlyUser = Event::get_all_exercise_results($exo, $cid, 0, true, $other->getId());
        $this->assertSame([$b], array_keys($onlyUser));

        $inSession = Event::get_all_exercise_results($exo, $cid, $sid);
        $this->assertCount(1, $inSession);
    }

    public function testResultsByCourseCanCountOrList(): void
    {
        $user = $this->makeUser('ev_byc');
        $course = $this->makeCourse('ev_course_byc');
        $cid = $course->getId();
        $a = $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $cid, 'exe_exo_id' => $this->makeQuiz()]);
        $b = $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $cid, 'exe_exo_id' => $this->makeQuiz()]);
        $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $cid, 'status' => 'incomplete']);
        $this->seedExercise(['exe_user_id' => $user->getId(), 'c_id' => $cid, 'orig_lp_id' => 3]);

        $count = Event::get_all_exercise_results_by_course($cid, 0);
        $this->assertEquals(2, $count);
        $list = Event::get_all_exercise_results_by_course($cid, 0, false);
        $this->assertSame([$a, $b], array_keys($list));
    }

    public function testResultsByUserAndByAttemptIncludeAnswersAndRevisionFlag(): void
    {
        $user = $this->makeUser('ev_byu');
        $course = $this->makeCourse('ev_course_byu');
        $cid = $course->getId();
        $uid = $user->getId();
        $a = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $this->makeQuiz()]);
        $b = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $this->makeQuiz()]);
        $this->seedAttempt($a, $uid, 41, ['tms' => '2020-01-01 10:00:02', 'answer' => 'late']);
        $this->seedAttempt($a, $uid, 42, ['tms' => '2020-01-01 10:00:01', 'answer' => 'early']);

        $byUser = Event::get_all_exercise_results_by_user($uid, $cid, 0);
        $this->assertSame([$a, $b], array_keys($byUser));
        $this->assertEqualsCanonicalizing([41, 42], array_keys($byUser[$a]['question_list']));

        $att = Event::get_exercise_results_by_attempt($a);
        $this->assertSame([$a], array_keys($att));
        $this->assertSame(0, $att[$a]['attempt_revised']);
        $this->assertSame([42, 41], array_keys($att[$a]['question_list']), 'questions come ordered by tms');

        $this->assertSame([], Event::get_exercise_results_by_attempt($a, 'incomplete'));
        $this->assertSame([], Event::get_exercise_results_by_attempt(2147483000));

        // A qualification by a teacher (author != '' and final = 1) flags the attempt as revised.
        $this->conn->insert('track_e_attempt_qualify', [
            'exe_id' => $a, 'question_id' => 41, 'marks' => 1, 'insert_date' => '2020-01-01 11:00:00',
            'author' => 1, 'teacher_comment' => '', 'session_id' => 0, 'final' => 1,
        ]);
        $this->assertSame(1, Event::get_exercise_results_by_attempt($a)[$a]['attempt_revised']);
        $this->assertSame(0, Event::get_exercise_results_by_attempt($b)[$b]['attempt_revised']);
    }

    public function testExerciseResultsByUserOrderPercentageAndRevision(): void
    {
        $user = $this->makeUser('ev_gru');
        $course = $this->makeCourse('ev_course_gru');
        $cid = $course->getId();
        $uid = $user->getId();
        $exo = $this->makeQuiz();
        $exo2 = $this->makeQuiz();
        $a = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo, 'score' => 1, 'max_score' => 4]);
        $b = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo, 'score' => 3, 'max_score' => 0]);
        $this->seedAttempt($a, $uid, 51);
        $this->seedAttempt($a, $uid, 51, ['position' => 2]);

        $asc = Event::getExerciseResultsByUser($uid, $exo, $cid);
        $this->assertSame([$a, $b], array_keys($asc));
        $this->assertEquals(25, $asc[$a]['total_percentage']);
        $this->assertSame(0, $asc[$b]['total_percentage'], 'a zero max score yields 0, not a division error');
        $this->assertSame(0, $asc[$a]['attempt_revised']);
        $this->assertCount(2, $asc[$a]['question_list'][51], 'attempts of one question are listed, not overwritten');

        $desc = Event::getExerciseResultsByUser($uid, $exo, $cid, 0, 0, 0, 'DESC');
        $this->assertSame([$b, $a], array_keys($desc));
        $bogus = Event::getExerciseResultsByUser($uid, $exo, $cid, 0, 0, 0, 'sideways');
        $this->assertSame([$a, $b], array_keys($bogus), 'unknown order falls back to ascending');

        $this->conn->insert('track_e_attempt_qualify', [
            'exe_id' => $a, 'question_id' => 51, 'marks' => 1, 'insert_date' => '2020-01-01 11:00:00',
            'author' => 1, 'teacher_comment' => '', 'session_id' => 0, 'final' => 1,
        ]);
        $this->assertSame(1, Event::getExerciseResultsByUser($uid, $exo, $cid)[$a]['attempt_revised']);
    }

    public function testCountAttemptsByUserCountsOnlyCompletedOutOfLpAttempts(): void
    {
        $user = $this->makeUser('ev_cu');
        $course = $this->makeCourse('ev_course_cu');
        $sid = $this->makeSession('ev_s_cu');
        $cid = $course->getId();
        $uid = $user->getId();
        $exo = $this->makeQuiz();
        $exo2 = $this->makeQuiz();
        $base = ['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo];
        $this->seedExercise($base);
        $this->seedExercise($base);
        $this->seedExercise(['status' => 'incomplete'] + $base);
        $this->seedExercise(['orig_lp_id' => 8] + $base);
        $this->seedExercise(['session_id' => $sid] + $base);

        $this->assertEquals(2, Event::count_exercise_attempts_by_user($uid, $exo, $cid, 0));
        $this->assertEquals(1, Event::count_exercise_attempts_by_user($uid, $exo, $cid, $sid));
        $this->assertEquals(0, Event::count_exercise_attempts_by_user($uid, $exo2, $cid, 0));
    }

    public function testBestResultsPerUserKeepTheFirstAttemptOnTies(): void
    {
        $alice = $this->makeUser('ev_best_a');
        $bob = $this->makeUser('ev_best_b');
        $course = $this->makeCourse('ev_course_best');
        $cid = $course->getId();
        $exo = $this->makeQuiz();
        $exo2 = $this->makeQuiz();
        $a1 = $this->seedExercise(['exe_user_id' => $alice->getId(), 'c_id' => $cid, 'exe_exo_id' => $exo, 'score' => 2, 'max_score' => 5]);
        $a2 = $this->seedExercise(['exe_user_id' => $alice->getId(), 'c_id' => $cid, 'exe_exo_id' => $exo, 'score' => 4, 'max_score' => 5]);
        $a3 = $this->seedExercise(['exe_user_id' => $alice->getId(), 'c_id' => $cid, 'exe_exo_id' => $exo, 'score' => 4, 'max_score' => 5]);
        $b1 = $this->seedExercise(['exe_user_id' => $bob->getId(), 'c_id' => $cid, 'exe_exo_id' => $exo, 'score' => 0, 'max_score' => 5]);
        $this->seedExercise(['exe_user_id' => $bob->getId(), 'c_id' => $cid, 'exe_exo_id' => $exo, 'score' => 5, 'max_score' => 5, 'status' => 'incomplete']);

        $best = Event::get_best_exercise_results_by_user($exo, $cid, 0);
        $this->assertSame([$alice->getId(), $bob->getId()], array_keys($best));
        $this->assertSame($a2, (int) $best[$alice->getId()]['exe_id'], 'a later attempt with an equal score does not replace the best');
        $this->assertSame($b1, (int) $best[$bob->getId()]['exe_id']);

        $onlyBob = Event::get_best_exercise_results_by_user($exo, $cid, 0, $bob->getId());
        $this->assertSame([$bob->getId()], array_keys($onlyBob));

        $one = Event::get_best_attempt_exercise_results_per_user($alice->getId(), $exo, $cid, 0);
        $this->assertSame($a2, (int) $one['exe_id']);
        $this->assertSame($a3 > $a2, true);
    }

    public function testBestAttemptPerUserIsEmptyWithoutAnyPositiveScoreAndCanIncludeLpResults(): void
    {
        $user = $this->makeUser('ev_bap');
        $course = $this->makeCourse('ev_course_bap');
        $cid = $course->getId();
        $uid = $user->getId();
        $exo = $this->makeQuiz();
        $exo2 = $this->makeQuiz();
        $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo, 'score' => 0, 'max_score' => 5]);
        $lp = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo, 'score' => 3, 'max_score' => 5, 'orig_lp_id' => 4, 'orig_lp_item_id' => 5]);

        $this->assertSame([], Event::get_best_attempt_exercise_results_per_user($uid, $exo, $cid, 0), 'score 0 never beats the initial 0');
        $this->assertSame([], Event::get_best_attempt_exercise_results_per_user($uid, $exo, $cid, 0, true));
        $withLp = Event::get_best_attempt_exercise_results_per_user($uid, $exo, $cid, 0, false);
        $this->assertSame($lp, (int) $withLp['exe_id']);
    }

    public function testExerciseEventsFromLpOnlyReturnLpResults(): void
    {
        $user = $this->makeUser('ev_flp');
        $course = $this->makeCourse('ev_course_flp');
        $cid = $course->getId();
        $uid = $user->getId();
        $exo = $this->makeQuiz();
        $exo2 = $this->makeQuiz();
        $inLp = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo, 'orig_lp_id' => 4, 'orig_lp_item_id' => 5]);
        $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo]);
        $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $cid, 'exe_exo_id' => $exo, 'orig_lp_id' => 4, 'orig_lp_item_id' => 0]);
        $this->seedAttempt($inLp, $uid, 61);

        $res = Event::get_all_exercise_event_from_lp($exo, $cid, 0);
        $this->assertSame([$inLp], array_keys($res));
        $this->assertSame([61], array_keys($res[$inLp]['question_list']));
    }

    public function testAttemptDetailReaders(): void
    {
        $user = $this->makeUser('ev_det');
        $course = $this->makeCourse('ev_course_det');
        $uid = $user->getId();
        $exe = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $course->getId()]);

        $this->assertSame('', Event::getLastAttemptDateOfExercise($exe));
        $this->assertSame(0, Event::getLatestQuestionIdFromAttempt($exe));
        $this->assertSame([], Event::getAllExerciseEventByExeId($exe));
        $this->assertSame([], Event::getQuestionAttemptByExeIdAndQuestion($exe, 71));

        $this->seedAttempt($exe, $uid, 71, ['position' => 2, 'tms' => '2020-01-01 10:00:05', 'teacher_comment' => '  well done  ']);
        $this->seedAttempt($exe, $uid, 72, ['position' => 1, 'tms' => '2020-01-01 10:00:09']);
        $this->seedAttempt($exe, $uid, 71, ['position' => 1, 'tms' => '2020-01-01 10:00:01', 'answer' => 'first']);

        $this->assertSame('2020-01-01 10:00:09', Event::getLastAttemptDateOfExercise($exe));
        $this->assertSame(72, (int) Event::getLatestQuestionIdFromAttempt($exe));

        $byExe = Event::getAllExerciseEventByExeId($exe);
        $this->assertSame([72, 71], array_keys($byExe), 'questions are grouped in position order');
        $this->assertSame([1, 2], array_map('intval', array_column($byExe[71], 'position')));

        $one = Event::getQuestionAttemptByExeIdAndQuestion($exe, 71);
        $this->assertSame(1, (int) $one['position'], 'the lowest position wins');
        $this->assertSame('first', $one['answer']);

        $this->assertSame('', Event::get_comments($exe, 72));
        $this->assertSame('', Event::get_comments($exe, 999));
        $this->conn->update('track_e_attempt', ['teacher_comment' => '  well done  '], ['exe_id' => $exe, 'question_id' => 72]);
        $this->assertSame('well done', Event::get_comments($exe, 72), 'comments are trimmed');
    }

    public function testAttemptQuestionDurationCountsFromTheFirstCallAndResumesFromSpentSeconds(): void
    {
        $user = $this->makeUser('ev_dur');
        $course = $this->makeCourse('ev_course_dur');
        $uid = $user->getId();
        $exe = $this->seedExercise(['exe_user_id' => $uid, 'c_id' => $course->getId()]);
        $this->seedAttempt($exe, $uid, 81, ['seconds_spent' => 30]);
        \ChamiloSession::erase('question_start');

        $first = Event::getAttemptQuestionDuration($exe, 81);
        $this->assertSame(30, $first, 'resumes from the seconds already stored');
        $this->assertSame(0, Event::getAttemptQuestionDuration($exe, 82), 'a question without attempt starts at 0');
        $this->assertArrayHasKey(81, \ChamiloSession::read('question_start'));
    }

    public function testSessionLogIsSavedByDefault(): void
    {
        $this->assertTrue(Event::isSessionLogNeedToBeSave(0));
        $this->assertTrue(Event::isSessionLogNeedToBeSave(2147483000));
    }
}
