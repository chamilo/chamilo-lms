<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use DateTime;
use Doctrine\DBAL\Connection;
use SessionManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests (tier 3c) of the session progress report, SessionManager::get_session_progress(), which pins the
 * values each column is meant to hold. Fixtures start with "sm3c_".
 */
final class SessionManagerTier3cCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/sessionmanager.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function sessionCourse(int $sessionId, Course $course): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, 0, 0)',
            [$sessionId, $course->getId()]
        );
    }

    private function sessionCourseUser(int $sessionId, Course $course, User $user, int $status = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$sessionId, $course->getId(), $user->getId(), $status]
        );
    }

    private function forum(string $title, Course $course, ?SessionEntity $session = null): CForum
    {
        $forum = (new CForum())->setTitle($title)->setParent($course)->setCreator($this->getUser('admin'))->addCourseLink($course, $session);
        $this->getEntityManager()->persist($forum);
        $this->getEntityManager()->flush();

        return $forum;
    }

    private function post(CForum $forum, User $author, Course $course, ?SessionEntity $session = null): void
    {
        $post = (new CForumPost())
            ->setTitle('sm3c_post')
            ->setPostText('text')
            ->setForum($forum)
            ->setUser($author)
            ->setCreator($author)
            ->setPostDate(new DateTime())
            ->setVisible(true)
            ->setStatus(CForumPost::STATUS_VALIDATED)
            ->setParent($forum)
            ->addCourseLink($course, $session)
        ;
        $this->getEntityManager()->persist($post);
        $this->getEntityManager()->flush();
    }

    public function testGetSessionProgressRunsForASessionWithACourseAndAStudent(): void
    {
        $course = $this->createCourse('sm3c_sp_course');
        $student = $this->createUser('sm3c_sp_student');
        $session = $this->createSession('sm3c_sp_session');
        $this->sessionCourse($session->getId(), $course);
        $this->sessionCourseUser($session->getId(), $course, $student);
        $forum = $this->forum('sm3c_sp_forum', $course, $session);
        $this->post($forum, $student, $course, $session);

        $rows = SessionManager::get_session_progress($session->getId(), $course->getId(), '', '', []);

        $this->assertCount(1, $rows);
        $this->assertSame('sm3c_sp_student', $rows[0]['username']);
        $this->assertStringContainsString('1', strip_tags($rows[0]['forums_total']), 'the forum of the session is counted');
        $this->assertStringContainsString('1', strip_tags($rows[0]['forums_done']), 'the student wrote in it');
        $this->assertStringContainsString('100', strip_tags($rows[0]['forums_progress']));
    }

    private function linked(object $resource, Course $course, SessionEntity $session): void
    {
        $resource->setParent($course)->setCreator($this->getUser('admin'))->addCourseLink($course, $session);
        $this->getEntityManager()->persist($resource);
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<string, string> the report columns of the single row, tags stripped
     */
    private function plainRow(array $row): array
    {
        return array_map(static fn ($value) => trim(strip_tags((string) $value)), $row);
    }

    public function testGetSessionProgressReportsEveryColumnOfTheLearner(): void
    {
        $course = $this->createCourse('sm3c_full_course');
        $student = $this->createUser('sm3c_full_student');
        $session = $this->createSession('sm3c_full_session');
        $this->sessionCourse($session->getId(), $course);
        $this->sessionCourseUser($session->getId(), $course, $student);
        $courseId = $course->getId();
        $sessionId = $session->getId();
        $studentId = $student->getId();

        // One lesson, followed at 40%.
        $lp = (new CLp())->setTitle('sm3c_lp')->setLpType(CLp::LP_TYPE);
        $this->linked($lp, $course, $session);
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, ?, ?, 1, 0, 40)',
            [$studentId, $lp->getIid(), $courseId, $sessionId]
        );

        // One exercise, attempted.
        $quiz = (new CQuiz())->setTitle('sm3c_quiz');
        $this->linked($quiz, $course, $session);
        $this->conn()->executeStatement(
            "INSERT INTO track_e_exercises (exe_exo_id, exe_user_id, c_id, session_id, exe_date, score, max_score, user_ip, status,
                data_tracking, start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check)
             VALUES (?, ?, ?, ?, NOW(), 1, 1, '127.0.0.1', '', '', NOW(), 0, 0, 0, 0, 0, '')",
            [$quiz->getIid(), $studentId, $courseId, $sessionId]
        );

        // One assignment, handed in by the learner.
        $work = (new CStudentPublication())->setTitle('sm3c_work')->setActive(1);
        $this->linked($work, $course, $session);
        $submission = (new CStudentPublication())->setTitle('sm3c_submission')->setFiletype('file')->setActive(1)->setUser($student)->setPublicationParent($work);
        $submission->setParent($work)->setCreator($student)->addCourseLink($course, $session);
        $this->getEntityManager()->persist($submission);
        $this->getEntityManager()->flush();

        // Two wiki pages of the session: the learner wrote two revisions of one and read it.
        foreach ([[7, $studentId], [7, $studentId], [8, $this->getUser('admin')->getId()]] as [$pageId, $authorId]) {
            $this->conn()->executeStatement(
                "INSERT INTO c_wiki (c_id, page_id, reflink, title, content, user_id, addlock, editlock, visibility, addlock_disc,
                    visibility_disc, ratinglock_disc, assignment, comment, progress, is_editing, linksto, tag, user_ip, session_id)
                 VALUES (?, ?, 'sm3c', 'sm3c', 'c', ?, 1, 0, 1, 1, 1, 1, 0, '', '', 0, '', '', '127.0.0.1', ?)",
                [$courseId, $pageId, $authorId, $sessionId]
            );
        }
        $this->conn()->executeStatement(
            "INSERT INTO track_e_default (default_user_id, c_id, session_id, default_date, default_event_type, default_value_type, default_value)
             VALUES (?, ?, ?, NOW(), 'wiki_page_view', 'wiki_page_id', '7')",
            [$studentId, $courseId, $sessionId]
        );

        // One forum with a post of the learner.
        $this->post($this->forum('sm3c_forum', $course, $session), $student, $course, $session);

        // One survey, answered by the learner (the answer stores the user id as text).
        $survey = (new CSurvey())->setCode('sm3c_survey')->setTitle('sm3c_survey')->setSubtitle('')->setLang('english')->setIntro('')
            ->setSurveythanks('')->setCreationDate(new DateTime())->setInviteMail('')->setReminderMail('')->setMailSubject('')
            ->setAnonymous('0')->setAccessCondition('')->setSurveyType(0)->setShowFormProfile(0)->setFormFields('')
            ->setVisibleResults(0)->setSurveyVersion('')->setIsShared('0')->setTemplate('template')
        ;
        $this->linked($survey, $course, $session);
        $this->conn()->executeStatement(
            "INSERT INTO c_survey_answer (survey_id, option_id, value, user, c_lp_item_id) VALUES (?, '0', 1, ?, 0)",
            [$survey->getIid(), (string) $studentId]
        );

        // The learner opened the course description.
        $this->conn()->executeStatement(
            "INSERT INTO track_e_access (access_user_id, access_date, c_id, access_tool, session_id, user_ip)
             VALUES (?, NOW(), ?, 'course_description', ?, '127.0.0.1')",
            [$studentId, $courseId, $sessionId]
        );

        $rows = SessionManager::get_session_progress($sessionId, $courseId, '', '', []);
        $this->assertCount(1, $rows);
        $row = $this->plainRow($rows[0]);

        $this->assertSame('sm3c_full_student', $row['username']);
        $this->assertSame('sm3c_full_student', $row['lastname']);
        $this->assertSame('100%', $row['course_description_progress']);
        $this->assertSame(['1', '0.4', '0.6', '40%'], [$row['lessons_total'], $row['lessons_done'], $row['lessons_left'], $row['lessons_progress']]);
        $this->assertSame(['1', '1', '0', '100%'], [$row['exercises_total'], $row['exercises_done'], $row['exercises_left'], $row['exercises_progress']]);
        $this->assertSame(['1', '1', '0', '100%'], [$row['forums_total'], $row['forums_done'], $row['forums_left'], $row['forums_progress']]);
        $this->assertSame(['1', '1', '0', '100%'], [$row['assignments_total'], $row['assignments_done'], $row['assignments_left'], $row['assignments_progress']]);
        $this->assertSame(['2', '2', '1', '1', '50%'], [$row['wiki_total'], $row['wiki_revisions'], $row['wiki_read'], $row['wiki_unread'], $row['wiki_progress']]);
        $this->assertSame(['1', '1', '0', '100%'], [$row['surveys_total'], $row['surveys_done'], $row['surveys_left'], $row['surveys_progress']]);
        $this->assertSame('91.67%', $row['total'], 'the mean of the six progresses: (100 + 100 + 100 + 100 + 50 + 100) / 6');
    }

    public function testGetSessionProgressAppliesTheGridOrderLimitAndFilterFragments(): void
    {
        $course = $this->createCourse('sm3c_opt_course');
        $session = $this->createSession('sm3c_opt_session');
        $this->sessionCourse($session->getId(), $course);
        $names = ['sm3c_opt_a', 'sm3c_opt_b', 'sm3c_opt_c'];
        foreach ($names as $name) {
            $this->sessionCourseUser($session->getId(), $course, $this->createUser($name));
        }
        // A teacher of the course is not a learner of the report.
        $this->sessionCourseUser($session->getId(), $course, $this->createUser('sm3c_opt_coach'), 2);

        $all = SessionManager::get_session_progress($session->getId(), $course->getId(), '', '', ['order' => 'username DESC']);
        $this->assertSame(array_reverse($names), array_column($all, 'username'));

        $page = SessionManager::get_session_progress($session->getId(), $course->getId(), '', '', ['order' => 'username ASC', 'limit' => '1 , 1']);
        $this->assertSame(['sm3c_opt_b'], array_column($page, 'username'));

        $searched = SessionManager::get_session_progress($session->getId(), $course->getId(), '', '', ['where' => " 1 = 1 AND (  (u.username LIKE '%opt_c%') ) "]);
        $this->assertSame(['sm3c_opt_c'], array_column($searched, 'username'), 'the bare fragment of a grid search, with a percent sign');

        $filtered = SessionManager::get_session_progress($session->getId(), $course->getId(), '', '', ['where' => "AND u.username = 'sm3c_opt_c'"]);
        $this->assertSame(['sm3c_opt_c'], array_column($filtered, 'username'));
    }

    public function testGetSessionProgressOfAnEmptySessionIsAnEmptyReport(): void
    {
        $course = $this->createCourse('sm3c_none_course');
        $session = $this->createSession('sm3c_none_session');
        $this->sessionCourse($session->getId(), $course);

        $this->assertSame([], SessionManager::get_session_progress($session->getId(), $course->getId(), '', '', []));
    }

    private function access(Course $course, SessionEntity $session, User $user, string $login, string $logout, int $counter): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, ?, ?, ?, ?, '')",
            [$user->getId(), $course->getId(), $login, $logout, $counter, $session->getId()]
        );
    }

    private function platformLogin(User $user, string $date, string $ip): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO track_e_login (login_user_id, login_date, user_ip) VALUES (?, ?, ?)',
            [$user->getId(), $date, $ip]
        );
    }

    public function testGetUserDataAccessTrackingOverviewListsTheCourseAccessesWithDurationSessionAndIp(): void
    {
        $course = $this->createCourse('sm3c_ov_course');
        $session = $this->createSession('sm3c_ov_session');
        $student = $this->createUser('sm3c_ov_student');
        $other = $this->createUser('sm3c_ov_other');
        $this->access($course, $session, $student, '2026-03-02 10:00:00', '2026-03-02 11:30:15', 7);
        $this->access($course, $session, $student, '2026-03-05 09:00:00', '2026-03-05 09:00:01', 1);
        $this->access($course, $session, $other, '2026-03-03 08:00:00', '2026-03-03 08:10:00', 2);
        $this->access($course, $session, $other, '2026-03-04 23:30:00', '2026-03-04 23:40:00', 1);
        // An access to another course of the session is not listed for this course.
        $otherCourse = $this->createCourse('sm3c_ov_other_course');
        $this->access($otherCourse, $session, $student, '2026-03-06 09:00:00', '2026-03-06 09:10:00', 1);
        // The closest earlier platform login gives the address; with none earlier the closest later one does.
        $this->platformLogin($student, '2026-03-01 08:00:00', '10.0.0.1');
        $this->platformLogin($student, '2026-03-02 09:55:00', '10.0.0.2');
        $this->platformLogin($student, '2026-03-04 09:00:00', '10.0.0.3');
        $this->platformLogin($other, '2026-03-09 09:00:00', '10.0.0.9');

        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('tracking.hide_ip_address', 'true');
        $hidden = SessionManager::get_user_data_access_tracking_overview($session->getId(), $course->getId(), $student->getId());
        $this->assertSame(['', ''], array_column($hidden, 'ip'), 'the setting hides the addresses');

        $settings->updateSetting('tracking.hide_ip_address', 'false');
        $rows = SessionManager::get_user_data_access_tracking_overview(
            $session->getId(),
            $course->getId(),
            $student->getId(),
            '',
            '',
            '',
            ['order' => 'logindate ASC']
        );

        $this->assertSame(['2026-03-02 10:00:00', '2026-03-05 09:00:00'], array_column($rows, 'logindate'));
        $this->assertSame(['sm3c_ov_student', 'sm3c_ov_student'], array_column($rows, 'username'));
        $this->assertEquals([7, 1], array_column($rows, 'clicks'));
        $this->assertSame(['01:30:15', '00:00:01'], array_column($rows, 'timeLoggedIn'));
        $this->assertSame(['sm3c_ov_session', 'sm3c_ov_session'], array_column($rows, 'session'));
        $this->assertSame(['10.0.0.2', '10.0.0.3'], array_column($rows, 'ip'));

        $withoutStudent = SessionManager::get_user_data_access_tracking_overview($session->getId(), $course->getId());
        $this->assertCount(4, $withoutStudent);
        $this->assertSame(['10.0.0.9'], array_values(array_unique(array_column(array_filter($withoutStudent, static fn ($r) => 'sm3c_ov_other' === $r['username']), 'ip'))));

        $dated = SessionManager::get_user_data_access_tracking_overview($session->getId(), $course->getId(), 0, '', '2026-03-03', '2026-03-04');
        $this->assertSame(['sm3c_ov_other', 'sm3c_ov_other'], array_column($dated, 'username'), 'both ends of the range are whole days');
    }

    public function testGetUserDataAccessTrackingOverviewFiltersOnTheDatesTheReportGridPasses(): void
    {
        $course = $this->createCourse('sm3c_ovd_course');
        $session = $this->createSession('sm3c_ovd_session');
        $student = $this->createUser('sm3c_ovd_student');
        $this->access($course, $session, $student, '2026-03-02 10:00:00', '2026-03-02 11:00:00', 1);
        $this->access($course, $session, $student, '2026-03-10 10:00:00', '2026-03-10 11:00:00', 1);

        // What model.ajax.php used to pass: the dates swapped and cast to int, i.e. never a filter.
        $old = SessionManager::get_user_data_access_tracking_overview(
            $session->getId(),
            $course->getId(),
            0,
            0,
            (int) '2026-03-05',
            (int) '2026-03-01'
        );
        $this->assertCount(2, $old, 'the old arguments left the date filter inactive');

        // What it passes now: date_from then date_to, as the YYYY-MM-DD strings of the grid.
        $new = SessionManager::get_user_data_access_tracking_overview(
            $session->getId(),
            $course->getId(),
            0,
            0,
            '2026-03-01',
            '2026-03-05'
        );
        $this->assertSame(['2026-03-02 10:00:00'], array_column($new, 'logindate'));

        // Swapped strings select nothing: the order of the two arguments matters.
        $this->assertSame([], SessionManager::get_user_data_access_tracking_overview(
            $session->getId(),
            $course->getId(),
            0,
            0,
            '2026-03-05',
            '2026-03-01'
        ));
    }

    public function testGetUserDataAccessTrackingOverviewAcceptsTheSortFieldsOfTheGrid(): void
    {
        $course = $this->createCourse('sm3c_ovs_course');
        $session = $this->createSession('sm3c_ovs_session');
        $student = $this->createUser('sm3c_ovs_student');
        $this->access($course, $session, $student, '2026-03-02 10:00:00', '2026-03-02 11:00:00', 1);
        $this->access($course, $session, $student, '2026-03-05 10:00:00', '2026-03-05 11:00:00', 1);

        // model.ajax.php sorts by "logindate" unless the grid asks for another column.
        $rows = SessionManager::get_user_data_access_tracking_overview(
            $session->getId(),
            $course->getId(),
            0,
            '',
            '',
            '',
            ['order' => 'logindate DESC', 'limit' => '0 , 10']
        );

        $this->assertSame(['2026-03-05 10:00:00', '2026-03-02 10:00:00'], array_column($rows, 'logindate'));

        $byUnknown = SessionManager::get_user_data_access_tracking_overview($session->getId(), $course->getId(), 0, '', '', '', ['order' => 'ip; DROP TABLE user']);
        $this->assertCount(2, $byUnknown, 'an unknown sort field falls back to the login date instead of reaching the SQL');

        $searched = SessionManager::get_user_data_access_tracking_overview(
            $session->getId(),
            $course->getId(),
            0,
            '',
            '',
            '',
            ['where' => " 1 = 1 AND (  (u.username LIKE '%ovs_stu%') ) "]
        );
        $this->assertCount(2, $searched, 'the bare fragment of a grid search');
        $none = SessionManager::get_user_data_access_tracking_overview($session->getId(), $course->getId(), 0, '', '', '', ['where' => "AND u.username = 'nobody'"]);
        $this->assertSame([], $none);
    }

    public function testGetUserDataAccessTrackingOverviewOfASessionWithoutAccessesIsEmpty(): void
    {
        $course = $this->createCourse('sm3c_ove_course');
        $session = $this->createSession('sm3c_ove_session');

        $this->assertSame([], SessionManager::get_user_data_access_tracking_overview($session->getId(), $course->getId()));
    }
}
