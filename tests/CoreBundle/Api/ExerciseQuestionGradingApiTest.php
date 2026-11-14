<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEAttemptQualify;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

/**
 * Grade-by-question screen (#1638): a teacher lists the answers of every learner to one open
 * question and saves a score per answer. Saving only changes the marks and the attempt total; the
 * full review still releases the result.
 */
final class ExerciseQuestionGradingApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const int FREE_ANSWER = 5;
    private const int UNIQUE_ANSWER = 1;

    public function testTeacherListsAnswersOfOneQuestionSortedAndFiltered(): void
    {
        $ctx = $this->createScenario('list');

        $data = $this->fetchGrading($ctx['teacher'], $ctx, '&sort=lastname');
        $this->assertSame($ctx['openQuestionId'], $data['questionId']);
        $this->assertSame([$ctx['openQuestionId']], array_column($data['questions'], 'id'), 'Only manually graded questions are offered.');
        $this->assertSame(2, $data['questions'][0]['unscoredCount']);
        $this->assertSame(2, $data['totalItems']);
        $this->assertSame(['Adams', 'Zola'], array_column($data['items'], 'lastName'));
        $this->assertSame("First line\nSecond <b>line</b>", $data['items'][0]['answer']['text'], 'Markup is reduced to text; typed tags stay text.');

        $byDate = $this->fetchGrading($ctx['teacher'], $ctx, '&sort=date');
        $this->assertSame(['Zola', 'Adams'], array_column($byDate['items'], 'lastName'), 'Zola submitted first.');

        $paged = $this->fetchGrading($ctx['teacher'], $ctx, '&sort=date&itemsPerPage=1&page=2');
        $this->assertSame(2, $paged['totalItems']);
        $this->assertSame(['Adams'], array_column($paged['items'], 'lastName'));

        $this->postScore($ctx['teacher'], $ctx, $ctx['attempts']['zola'], 4.0);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $unscored = $this->fetchGrading($ctx['teacher'], $ctx, '&onlyUnscored=1');
        $this->assertSame(1, $unscored['totalItems']);
        $this->assertSame(['Adams'], array_column($unscored['items'], 'lastName'));
        $this->assertSame(1, $unscored['questions'][0]['unscoredCount']);

        $all = $this->fetchGrading($ctx['teacher'], $ctx, '&sort=date');
        $this->assertTrue($all['items'][0]['scored']);
        $this->assertTrue($all['items'][0]['awaitingReview'], 'A score alone does not complete the review.');
        $this->assertFalse($all['items'][1]['scored']);
    }

    public function testScoreIsSavedWithoutReviewOrNotificationAndTotalIsRecomputed(): void
    {
        $ctx = $this->createScenario('save');
        $attemptId = $ctx['attempts']['adams'];
        $messagesBefore = $this->countMessages();

        $payload = $this->postScore($ctx['teacher'], $ctx, $attemptId, 7.5);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertTrue($payload['success']);
        $this->assertSame(10.5, (float) $payload['score'], 'Total = 3 (auto-graded) + 7.5 (open question).');
        $this->assertSame([$ctx['openQuestionId']], $payload['questionsToCheck']);

        $em = $this->getEntityManager();
        $em->clear();
        $attempt = $em->find(TrackEExercise::class, $attemptId);
        $this->assertSame(10.5, (float) $attempt->getScore());
        $this->assertSame((string) $ctx['openQuestionId'], $attempt->getQuestionsToCheck(), 'The question stays pending review.');

        $row = $em->getRepository(TrackEAttempt::class)->findOneBy(['trackExercise' => $attemptId, 'questionId' => $ctx['openQuestionId']]);
        $this->assertSame(7.5, (float) $row->getMarks());
        $this->assertSame('Existing comment', $row->getTeacherComment(), 'The teacher comment is not touched.');

        $qualify = $em->getRepository(TrackEAttemptQualify::class)->findBy(['trackExercise' => $attemptId]);
        $this->assertCount(1, $qualify);
        $this->assertFalse($qualify[0]->isFinal(), 'The history row is a draft, so the attempt is not reviewed.');
        $this->assertSame($this->countMessages(), $messagesBefore, 'No message is sent to the learner.');
    }

    public function testScoreOutOfBoundsIsRejected(): void
    {
        $ctx = $this->createScenario('bounds');

        foreach ([-1.0, 10.01] as $marks) {
            $this->postScore($ctx['teacher'], $ctx, $ctx['attempts']['adams'], $marks);
            $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        }

        $this->postScore($ctx['teacher'], $ctx, $ctx['attempts']['adams'], 10.0);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testAutoGradedQuestionCannotBeScored(): void
    {
        $ctx = $this->createScenario('auto');

        $this->postScore($ctx['teacher'], $ctx, $ctx['attempts']['adams'], 1.0, $ctx['autoQuestionId']);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $this->fetchGrading($ctx['teacher'], $ctx, '&questionId='.$ctx['autoQuestionId'], Response::HTTP_NOT_FOUND);
    }

    public function testLearnerIsDenied(): void
    {
        $ctx = $this->createScenario('learner');

        $this->fetchGrading($ctx['learner'], $ctx, '', Response::HTTP_FORBIDDEN);
        $this->postScore($ctx['learner'], $ctx, $ctx['attempts']['adams'], 10.0);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertMarksUnchanged($ctx['attempts']['adams'], $ctx['openQuestionId']);
    }

    public function testTeacherOfAnotherCourseIsDenied(): void
    {
        $ctx = $this->createScenario('victim');
        $other = $this->createScenario('other');

        // Own course in the query string, victim exercise and attempt in the path.
        $this->fetchGrading($other['teacher'], ['quiz' => $ctx['quiz'], 'course' => $other['course']], '', Response::HTTP_FORBIDDEN);
        $this->postScore($other['teacher'], ['quiz' => $ctx['quiz'], 'course' => $other['course'], 'openQuestionId' => $ctx['openQuestionId']], $ctx['attempts']['adams'], 10.0);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Victim course in the query string: not a teacher there.
        $this->fetchGrading($other['teacher'], $ctx, '', Response::HTTP_FORBIDDEN);
        $this->postScore($other['teacher'], $ctx, $ctx['attempts']['adams'], 10.0);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->assertMarksUnchanged($ctx['attempts']['adams'], $ctx['openQuestionId']);
    }

    public function testAttemptOfAnotherExerciseIsRejected(): void
    {
        $ctx = $this->createScenario('idor');
        $other = $this->createScenario('idor_other');

        // Own exercise and course, an attempt id of another course in the path.
        $this->postScore($ctx['teacher'], $ctx, $other['attempts']['adams'], 10.0);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertMarksUnchanged($other['attempts']['adams'], $other['openQuestionId']);
    }

    public function testSessionCoachGradesOnlyTheAttemptsOfTheSession(): void
    {
        $ctx = $this->createScenario('session');
        $em = $this->getEntityManager();
        // Session course coaches get their course role in a registered-only course.
        $course = $em->find(Course::class, $ctx['course']->getId())->setVisibility(Course::REGISTERED);
        $coach = $this->createUser('gbq_insession_coach', '', '', 'ROLE_TEACHER');
        $learner = $this->createUser('gbq_insession_learner')->setLastname('Sessionlearner');

        $session = $this->createSession('Grade by question session');
        $session->addCourse($course);
        $session->addUserInCourse(Session::COURSE_COACH, $coach, $course);
        $session->addUserInCourse(Session::STUDENT, $learner, $course);
        self::getContainer()->get(SessionRepository::class)->update($session);

        $quiz = $em->find(CQuiz::class, $ctx['quiz']->getIid());
        $auto = $em->find(CQuizQuestion::class, $ctx['autoQuestionId']);
        $open = $em->find(CQuizQuestion::class, $ctx['openQuestionId']);
        $sessionAttemptId = $this->createAttempt($course, $quiz, $learner, $auto, $open, 'Session answer', new DateTime('-2 hours'), $session);

        $query = '?cid='.$course->getId().'&sid='.$session->getId();
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($coach));
        $data = $client->request('GET', '/api/exercise/runtime/'.$quiz->getIid().'/question-grading'.$query)->toArray();
        $this->assertSame([$sessionAttemptId], array_column($data['items'], 'attemptId'), 'Base course attempts are not listed in the session.');

        $url = '/api/exercise/runtime/'.$quiz->getIid().'/attempt/%d/question-score'.$query;
        $this->createClientWithCredentials($this->getUserTokenFromUser($coach))
            ->request('POST', \sprintf($url, $sessionAttemptId), ['json' => ['questionId' => $ctx['openQuestionId'], 'marks' => 5]])
        ;
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->createClientWithCredentials($this->getUserTokenFromUser($coach))
            ->request('POST', \sprintf($url, $ctx['attempts']['adams']), ['json' => ['questionId' => $ctx['openQuestionId'], 'marks' => 5]])
        ;
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertMarksUnchanged($ctx['attempts']['adams'], $ctx['openQuestionId']);
    }

    private function assertMarksUnchanged(int $attemptId, int $questionId): void
    {
        $em = $this->getEntityManager();
        $em->clear();
        $row = $em->getRepository(TrackEAttempt::class)->findOneBy(['trackExercise' => $attemptId, 'questionId' => $questionId]);
        $this->assertSame(0.0, (float) $row->getMarks());
        $this->assertSame([], $em->getRepository(TrackEAttemptQualify::class)->findBy(['trackExercise' => $attemptId]));
    }

    private function countMessages(): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(message.id)')
            ->from(Message::class, 'message')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param array<string, mixed> $ctx
     *
     * @return array<string, mixed>
     */
    private function fetchGrading(User $user, array $ctx, string $extraQuery, int $expectedStatus = Response::HTTP_OK): array
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'GET',
            '/api/exercise/runtime/'.$ctx['quiz']->getIid().'/question-grading?cid='.$ctx['course']->getId().'&sid=0'.$extraQuery
        );

        $this->assertResponseStatusCodeSame($expectedStatus);

        return Response::HTTP_OK === $expectedStatus ? $response->toArray() : [];
    }

    /**
     * @param array<string, mixed> $ctx
     *
     * @return array<string, mixed>
     */
    private function postScore(User $user, array $ctx, int $attemptId, float $marks, ?int $questionId = null): array
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'POST',
            '/api/exercise/runtime/'.$ctx['quiz']->getIid().'/attempt/'.$attemptId.'/question-score?cid='.$ctx['course']->getId().'&sid=0',
            ['json' => ['questionId' => $questionId ?? $ctx['openQuestionId'], 'marks' => $marks]]
        );

        return $response->toArray(false);
    }

    /**
     * A course with a teacher, a learner, and an exercise with one auto-graded question (3/3 scored
     * for everyone) and one open question (max 10) answered by two learners and not corrected yet.
     *
     * @return array{course: Course, teacher: User, learner: User, quiz: CQuiz, openQuestionId: int, autoQuestionId: int, attempts: array{zola: int, adams: int}}
     */
    private function createScenario(string $suffix): array
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('Grade by question '.$suffix);
        $teacher = $this->createUser('gbq_'.$suffix.'_teacher', '', '', 'ROLE_TEACHER');
        $learner = $this->createUser('gbq_'.$suffix.'_learner');
        $zola = $this->createUser('gbq_'.$suffix.'_zola')->setFirstname('Emile')->setLastname('Zola');
        $adams = $this->createUser('gbq_'.$suffix.'_adams')->setFirstname('Ansel')->setLastname('Adams');

        $course->addUserAsTeacher($teacher);
        foreach ([$learner, $zola, $adams] as $student) {
            $course->addUserAsStudent($student);
        }
        self::getContainer()->get(CourseRepository::class)->update($course);

        $quiz = (new CQuiz())
            ->setTitle('Grade by question quiz '.$suffix)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        $auto = $this->createQuestion($course, $quiz, 'Pick one', self::UNIQUE_ANSWER, 3.0, 1);
        $open = $this->createQuestion($course, $quiz, '<p>Explain <em>why</em></p>', self::FREE_ANSWER, 10.0, 2);

        $attempts = [
            'zola' => $this->createAttempt($course, $quiz, $zola, $auto, $open, 'Because.', new DateTime('-3 hours')),
            'adams' => $this->createAttempt($course, $quiz, $adams, $auto, $open, '<p>First line</p><p>Second &lt;b&gt;line&lt;/b&gt;</p>', new DateTime('-1 hour')),
        ];
        $em->flush();

        return [
            'course' => $course,
            'teacher' => $teacher,
            'learner' => $learner,
            'quiz' => $quiz,
            'openQuestionId' => (int) $open->getIid(),
            'autoQuestionId' => (int) $auto->getIid(),
            'attempts' => $attempts,
        ];
    }

    private function createQuestion(Course $course, CQuiz $quiz, string $title, int $type, float $maxScore, int $order): CQuizQuestion
    {
        $question = (new CQuizQuestion())
            ->setQuestion($title)
            ->setType($type)
            ->setLevel(1)
            ->setPosition($order)
            ->setExtra('')
            ->setPonderation($maxScore)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizQuestionRepository::class)->create($question);

        $this->getEntityManager()->persist(
            (new CQuizRelQuestion())
                ->setQuiz($quiz)
                ->setQuestion($question)
                ->setQuestionOrder($order)
        );

        return $question;
    }

    private function createAttempt(Course $course, CQuiz $quiz, User $learner, CQuizQuestion $auto, CQuizQuestion $open, string $answer, DateTime $submittedAt, ?Session $session = null): int
    {
        $em = $this->getEntityManager();
        $attempt = (new TrackEExercise())
            ->setQuiz($quiz)
            ->setCourse($course)
            ->setSession($session)
            ->setUser($learner)
            ->setUserIp('127.0.0.1')
            ->setScore(3.0)
            ->setMaxScore(13.0)
            ->setStatus('completed')
            ->setDataTracking($auto->getIid().','.$open->getIid())
            ->setStartDate((clone $submittedAt)->modify('-10 minutes'))
            ->setExeDate($submittedAt)
            ->setExeDuration(600)
            ->setStepsCounter(1)
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
            ->setQuestionsToCheck((string) $open->getIid())
        ;
        $em->persist($attempt);

        foreach ([[$auto, '1', 3.0, 1, ''], [$open, $answer, 0.0, 2, 'Existing comment']] as [$question, $value, $marks, $position, $comment]) {
            $em->persist(
                (new TrackEAttempt())
                    ->setTrackEExercise($attempt)
                    ->setUser($learner)
                    ->setQuestionId((int) $question->getIid())
                    ->setAnswer($value)
                    ->setTeacherComment($comment)
                    ->setMarks($marks)
                    ->setPosition($position)
                    ->setTms(new DateTime())
                    ->setSecondsSpent(0)
            );
        }
        $em->flush();

        return (int) $attempt->getExeId();
    }
}
