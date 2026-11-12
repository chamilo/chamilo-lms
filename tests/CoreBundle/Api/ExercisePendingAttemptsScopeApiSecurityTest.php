<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * The pending attempts report shows the name, official code, score and IP of every learner
 * in its scope, so its scope must be the courses the user manages: the ones they teach, and
 * the session courses they coach. A platform teacher who is only a student in a course must
 * not read the attempts of their classmates there.
 *
 * Decision: the fix narrows the scope, it does not change who reaches the report, so a
 * platform teacher outside the course gets 200 with an empty list rather than 403.
 */
final class ExercisePendingAttemptsScopeApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const string URI = '/api/exercise/pending-attempts';
    private const string EXPORT_URI = '/api/exercise/pending-attempts/export.csv';
    private const string VICTIM_IP = '203.0.113.45';

    protected function setUp(): void
    {
        parent::setUp();

        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('exercise.my_courses_show_pending_exercise_attempts', 'true')
        ;
    }

    public function testPlatformTeacherSubscribedAsStudentDoesNotSeeClassmatesAttempts(): void
    {
        $context = $this->createCourseWithPendingAttempt('scope_atk');
        $attacker = $this->createUser('scope_atk_attacker', '', '', 'ROLE_TEACHER');
        $this->subscribeAsStudent($context['course'], $attacker);

        $data = $this->fetchReport($attacker, $context['course']);

        $this->assertNotContains($context['attemptId'], $this->attemptIds($data));
        $this->assertNotContains(
            $context['course']->getId(),
            array_column($data['courseOptions'], 'value'),
            'The course selector must not offer a course where the user is only a student.'
        );

        $csv = $this->createClientWithCredentials($this->getUserTokenFromUser($attacker))->request(
            'GET',
            self::EXPORT_URI.'?courseId='.$context['course']->getId().'&status=3'
        );
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString(self::VICTIM_IP, $csv->getContent());
    }

    public function testCourseTeacherSeesPendingAttempts(): void
    {
        $context = $this->createCourseWithPendingAttempt('scope_tea');

        $data = $this->fetchReport($context['teacher'], $context['course']);

        $this->assertContains($context['attemptId'], $this->attemptIds($data));
        $this->assertContains($context['course']->getId(), array_column($data['courseOptions'], 'value'));
    }

    public function testPlatformAdminSeesPendingAttempts(): void
    {
        $context = $this->createCourseWithPendingAttempt('scope_adm');
        $admin = $this->createUser('scope_adm_admin', '', '', 'ROLE_ADMIN');

        $data = $this->fetchReport($admin, $context['course']);

        $this->assertContains($context['attemptId'], $this->attemptIds($data));
    }

    public function testPlatformTeacherWithoutRelationSeesNothing(): void
    {
        $context = $this->createCourseWithPendingAttempt('scope_str');
        $stranger = $this->createUser('scope_str_stranger', '', '', 'ROLE_TEACHER');

        $data = $this->fetchReport($stranger, $context['course']);

        $this->assertSame([], $this->attemptIds($data));
    }

    public function testSessionCourseCoachSeesSessionAttemptsButSessionStudentDoesNot(): void
    {
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('exercise.show_exercise_attempts_in_all_user_sessions', 'true')
        ;

        $course = $this->createCourse('Pending scope session course');
        $coach = $this->createUser('scope_ses_coach', '', '', 'ROLE_TEACHER');
        $sessionStudent = $this->createUser('scope_ses_attacker', '', '', 'ROLE_TEACHER');
        $learner = $this->createUser('scope_ses_learner');

        $session = $this->createSession('Pending scope session');
        $session->addCourse($course);
        $session->addUserInCourse(Session::COURSE_COACH, $coach, $course);
        $session->addUserInCourse(Session::STUDENT, $sessionStudent, $course);
        $session->addUserInCourse(Session::STUDENT, $learner, $course);
        self::getContainer()->get(SessionRepository::class)->update($session);

        $attemptId = $this->createPendingAttempt($course, $this->createQuiz($course), $learner, $session);

        $this->assertContains($attemptId, $this->attemptIds($this->fetchReport($coach, $course)));
        $this->assertNotContains($attemptId, $this->attemptIds($this->fetchReport($sessionStudent, $course)));
    }

    /**
     * @return array{course: Course, teacher: User, attemptId: int}
     */
    private function createCourseWithPendingAttempt(string $prefix): array
    {
        $course = $this->createCourse('Pending scope '.$prefix);
        $teacher = $this->createUser($prefix.'_teacher', '', '', 'ROLE_TEACHER');
        $learner = $this->createUser($prefix.'_learner');

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($learner);
        self::getContainer()->get(CourseRepository::class)->update($course);

        $attemptId = $this->createPendingAttempt($course, $this->createQuiz($course), $learner);

        return ['course' => $course, 'teacher' => $teacher, 'attemptId' => $attemptId];
    }

    private function subscribeAsStudent(Course $course, User $user): void
    {
        $course->addUserAsStudent($user);
        self::getContainer()->get(CourseRepository::class)->update($course);
    }

    private function createQuiz(Course $course): CQuiz
    {
        $quiz = (new CQuiz())
            ->setTitle('Pending scope quiz')
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        return $quiz;
    }

    /**
     * A finished attempt that no teacher corrected yet: what the report lists by default.
     */
    private function createPendingAttempt(Course $course, CQuiz $quiz, User $learner, ?Session $session = null): int
    {
        $attempt = (new TrackEExercise())
            ->setQuiz($quiz)
            ->setCourse($course)
            ->setSession($session)
            ->setUser($learner)
            ->setUserIp(self::VICTIM_IP)
            ->setScore(0.0)
            ->setMaxScore(10.0)
            ->setStatus('completed')
            ->setDataTracking('')
            ->setStartDate(new DateTime('-2 hours'))
            ->setExeDate(new DateTime('-1 hour'))
            ->setExeDuration(3600)
            ->setStepsCounter(1)
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
            ->setQuestionsToCheck('')
        ;

        $em = $this->getEntityManager();
        $em->persist($attempt);
        $em->flush();

        return $attempt->getExeId();
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchReport(User $user, Course $course): array
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'GET',
            self::URI.'?courseId='.$course->getId()
        );

        $this->assertResponseIsSuccessful();

        return $response->toArray();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, int>
     */
    private function attemptIds(array $data): array
    {
        return array_map('intval', array_column($data['items'], 'attemptId'));
    }
}
