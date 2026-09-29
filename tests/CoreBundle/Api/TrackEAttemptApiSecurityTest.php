<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Access control of the exercise attempt endpoints.
 *
 * Both /api/track_e_attempts and /api/track_e_attempt_qualifies expose one learner's answers
 * and the teacher's grading of them, so they carry the rules of the legacy pages they replace:
 * main/exercise/result.php (a learner reaches only their own attempt) and
 * main/exercise/exercise_show.php (a teacher, tutor or coach reaches the attempts of the course
 * or session they run). TrackEExerciseVoter is the single authority, and TrackEExerciseRepository mirrors it for
 * the collection endpoints.
 *
 * The case worth naming is testGetAttemptAsTeacherOfAnotherCourseIsDenied: the contextual roles
 * ROLE_CURRENT_COURSE_TEACHER and ROLE_CURRENT_COURSE_SESSION_TEACHER answer for the course the
 * request names in `cid`, not for the course the attempt belongs to. A teacher passing the id of
 * a course they legitimately run must not thereby reach an attempt from a different course.
 *
 * The tracking rows are written straight through DBAL: TrackEExercise::$exeId is a non-nullable
 * int with no default, which API Platform's PurgeHttpCacheListener cannot read while the insert
 * is still pending.
 */
final class TrackEAttemptApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * One course with a teacher and a student who took an exercise, plus an unrelated course
     * whose own teacher must stay out.
     *
     * @return array{
     *     course: Course,
     *     teacher: User,
     *     student: User,
     *     foreignStudent: User,
     *     foreignTeacher: User,
     *     foreignCourse: Course,
     *     trackExerciseId: int,
     *     attemptId: int,
     *     qualifyId: int
     * }
     */
    private function bootstrapAttemptScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('ATSC'.$suffix);
        $foreignCourse = $this->createCourse('ATSF'.$suffix);

        $teacher = $this->createUser('ats_teacher_'.$suffix);
        $student = $this->createUser('ats_student_'.$suffix);
        $foreignStudent = $this->createUser('ats_fstudent_'.$suffix);
        $foreignTeacher = $this->createUser('ats_fteacher_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $courseRepo->update($course);

        $foreignCourse->addUserAsTeacher($foreignTeacher);
        $foreignCourse->addUserAsStudent($foreignStudent);
        $courseRepo->update($foreignCourse);

        $connection = $this->getEntityManager()->getConnection();

        $connection->insert('track_e_exercises', [
            'exe_user_id' => (int) $student->getId(),
            'c_id' => (int) $course->getId(),
            'session_id' => null,
            'exe_exo_id' => null,
            'exe_date' => '2026-01-01 10:00:00',
            'score' => 5.0,
            'max_score' => 10.0,
            'user_ip' => '127.0.0.1',
            'status' => '',
            'data_tracking' => '',
            'start_date' => '2026-01-01 09:00:00',
            'steps_counter' => 0,
            'orig_lp_id' => 0,
            'orig_lp_item_id' => 0,
            'exe_duration' => 60,
            'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ]);
        $trackExerciseId = (int) $connection->lastInsertId();

        $connection->insert('track_e_attempt', [
            'exe_id' => $trackExerciseId,
            'user_id' => (int) $student->getId(),
            'question_id' => 1,
            'answer' => 'the answer under test',
            'teacher_comment' => '',
            'marks' => 5.0,
            'tms' => '2026-01-01 10:00:00',
            'seconds_spent' => 30,
        ]);
        $attemptId = (int) $connection->lastInsertId();

        $connection->insert('track_e_attempt_qualify', [
            'exe_id' => $trackExerciseId,
            'question_id' => 1,
            'marks' => 5.0,
            'insert_date' => '2026-01-02 10:00:00',
            'author' => (int) $teacher->getId(),
            'teacher_comment' => 'graded under test',
            'session_id' => 0,
        ]);
        $qualifyId = (int) $connection->lastInsertId();

        return [
            'course' => $course,
            'teacher' => $teacher,
            'student' => $student,
            'foreignStudent' => $foreignStudent,
            'foreignTeacher' => $foreignTeacher,
            'foreignCourse' => $foreignCourse,
            'trackExerciseId' => $trackExerciseId,
            'attemptId' => $attemptId,
            'qualifyId' => $qualifyId,
        ];
    }

    /**
     * Identifiers come from the @id IRI: track_e_attempt:read does not expose a plain `id`
     * field, so reading one would silently yield an empty list and pass every "must not
     * contain" assertion for the wrong reason.
     *
     * @param array<string, mixed> $payload
     *
     * @return int[]
     */
    private function extractIds(array $payload): array
    {
        $ids = [];
        foreach ($payload['hydra:member'] ?? [] as $member) {
            if (isset($member['@id']) && preg_match('#/(\d+)$#', (string) $member['@id'], $matches)) {
                $ids[] = (int) $matches[1];
            }
        }

        return $ids;
    }

    // -------------------------------------------------------------------------
    // Item operation — TrackEAttempt
    // -------------------------------------------------------------------------

    public function testGetAttemptAsOwnerIsAllowed(): void
    {
        $ctx = $this->bootstrapAttemptScenario('own');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))->request(
            'GET',
            '/api/track_e_attempts/'.$ctx['attemptId'],
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetAttemptAsCourseTeacherIsAllowed(): void
    {
        $ctx = $this->bootstrapAttemptScenario('tea');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'GET',
            '/api/track_e_attempts/'.$ctx['attemptId'].'?cid='.$ctx['course']->getId(),
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetAttemptAsForeignStudentIsDenied(): void
    {
        $ctx = $this->bootstrapAttemptScenario('fst');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['foreignStudent']))->request(
            'GET',
            '/api/track_e_attempts/'.$ctx['attemptId'],
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetAttemptAsTeacherOfAnotherCourseIsDenied(): void
    {
        $ctx = $this->bootstrapAttemptScenario('xco');

        // Passing the id of a course they really do teach must not vouch for an attempt
        // that belongs to another course.
        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['foreignTeacher']))->request(
            'GET',
            '/api/track_e_attempts/'.$ctx['attemptId'].'?cid='.$ctx['foreignCourse']->getId(),
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetAttemptAsAdminIsAllowed(): void
    {
        $ctx = $this->bootstrapAttemptScenario('adm');

        $this->createClientWithCredentials()->request(
            'GET',
            '/api/track_e_attempts/'.$ctx['attemptId'],
        );

        $this->assertResponseStatusCodeSame(200);
    }

    // -------------------------------------------------------------------------
    // Collection operation — TrackEAttempt
    // -------------------------------------------------------------------------

    public function testListAttemptsAsForeignStudentDoesNotLeakForeignAttempt(): void
    {
        $ctx = $this->bootstrapAttemptScenario('lfs');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['foreignStudent']))->request(
            'GET',
            '/api/track_e_attempts?itemsPerPage=5000&order[id]=desc',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertNotContains($ctx['attemptId'], $this->extractIds($response->toArray()));
    }

    public function testListAttemptsAsTeacherOfAnotherCourseDoesNotLeakForeignAttempt(): void
    {
        $ctx = $this->bootstrapAttemptScenario('lft');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['foreignTeacher']))->request(
            'GET',
            '/api/track_e_attempts?itemsPerPage=5000&order[id]=desc',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertNotContains($ctx['attemptId'], $this->extractIds($response->toArray()));
    }

    public function testListAttemptsAsOwnerContainsOwnAttempt(): void
    {
        $ctx = $this->bootstrapAttemptScenario('low');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))->request(
            'GET',
            '/api/track_e_attempts?itemsPerPage=5000&order[id]=desc',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertContains($ctx['attemptId'], $this->extractIds($response->toArray()));
    }

    public function testListAttemptsAsCourseTeacherContainsStudentAttempt(): void
    {
        $ctx = $this->bootstrapAttemptScenario('lte');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'GET',
            '/api/track_e_attempts?itemsPerPage=5000&order[id]=desc',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertContains($ctx['attemptId'], $this->extractIds($response->toArray()));
    }

    // -------------------------------------------------------------------------
    // TrackEExercise — the authority both child voters defer to
    // -------------------------------------------------------------------------

    public function testGetTrackExerciseAsOwnerIsAllowed(): void
    {
        $ctx = $this->bootstrapAttemptScenario('teo');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))->request(
            'GET',
            '/api/track_e_exercises/'.$ctx['trackExerciseId'],
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetTrackExerciseAsCourseTeacherIsAllowed(): void
    {
        $ctx = $this->bootstrapAttemptScenario('tet');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'GET',
            '/api/track_e_exercises/'.$ctx['trackExerciseId'].'?cid='.$ctx['course']->getId(),
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetTrackExerciseAsTeacherOfAnotherCourseIsDenied(): void
    {
        $ctx = $this->bootstrapAttemptScenario('tex');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['foreignTeacher']))->request(
            'GET',
            '/api/track_e_exercises/'.$ctx['trackExerciseId'].'?cid='.$ctx['foreignCourse']->getId(),
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListTrackExercisesAsTeacherOfAnotherCourseDoesNotLeakForeignAttempt(): void
    {
        $ctx = $this->bootstrapAttemptScenario('tel');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['foreignTeacher']))->request(
            'GET',
            '/api/track_e_exercises?itemsPerPage=5000&order[exeId]=desc',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertNotContains($ctx['trackExerciseId'], $this->extractIds($response->toArray()));
    }

    public function testListTrackExercisesAsCourseTeacherContainsStudentAttempt(): void
    {
        $ctx = $this->bootstrapAttemptScenario('tec');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'GET',
            '/api/track_e_exercises?itemsPerPage=5000&order[exeId]=desc',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertContains($ctx['trackExerciseId'], $this->extractIds($response->toArray()));
    }

    public function testListTrackExercisesAsOwnerContainsOwnAttempt(): void
    {
        $ctx = $this->bootstrapAttemptScenario('tew');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))->request(
            'GET',
            '/api/track_e_exercises?itemsPerPage=5000&order[exeId]=desc',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertContains($ctx['trackExerciseId'], $this->extractIds($response->toArray()));
    }

    // -------------------------------------------------------------------------
    // TrackEAttemptQualify — same rules, same helper
    // -------------------------------------------------------------------------

    public function testGetQualifyAsTeacherOfAnotherCourseIsDenied(): void
    {
        $ctx = $this->bootstrapAttemptScenario('qxc');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['foreignTeacher']))->request(
            'GET',
            '/api/track_e_attempt_qualifies/'.$ctx['qualifyId'].'?cid='.$ctx['foreignCourse']->getId(),
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetQualifyAsCourseTeacherIsAllowed(): void
    {
        $ctx = $this->bootstrapAttemptScenario('qte');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'GET',
            '/api/track_e_attempt_qualifies/'.$ctx['qualifyId'].'?cid='.$ctx['course']->getId(),
        );

        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * The previous extension only narrowed the query for students, so every other role — a
     * teacher of any unrelated course included — read the whole grading table.
     */
    public function testListQualifiesAsTeacherOfAnotherCourseDoesNotLeakForeignGrading(): void
    {
        $ctx = $this->bootstrapAttemptScenario('qlf');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['foreignTeacher']))->request(
            'GET',
            '/api/track_e_attempt_qualifies?itemsPerPage=5000&order[id]=desc',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertNotContains($ctx['qualifyId'], $this->extractIds($response->toArray()));
    }

    public function testListQualifiesAsCourseTeacherContainsOwnCourseGrading(): void
    {
        $ctx = $this->bootstrapAttemptScenario('qlt');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'GET',
            '/api/track_e_attempt_qualifies?itemsPerPage=5000&order[id]=desc',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertContains($ctx['qualifyId'], $this->extractIds($response->toArray()));
    }
}
