<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
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
        $submission = (new CStudentPublication())->setTitle('sm3c_submission')->setActive(1)->setUser($student)->setPublicationParent($work);
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
}
