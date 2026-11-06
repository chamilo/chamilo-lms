<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression test for the attempt-limit bypass on
 * POST /api/exercise/runtime/{exerciseId}/attempt.
 *
 * Attempts are counted per (origLpId, origLpItemId, origLpItemViewId). The processor used to read
 * that triple straight from the query string and seal the new row with it, so a learner who had
 * already spent every allowed attempt could append a never-seen id (e.g.
 * ?learnpath_item_view_id=7) to open a fresh, private, empty counter and take the test again — an
 * unlimited number of times, each result feeding the gradebook.
 *
 * The processor must now resolve the triple through a validated lookup: a forged id resolves to the
 * plain exercise-tool context (0, 0, 0) and hits the same exhausted counter, while a real in-lesson
 * attempt keeps its own separate counter.
 */
final class ExerciseAttemptLimitApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testPlainSecondAttemptIsRejected(): void
    {
        $context = $this->createExerciseWithOneAttemptLimit('plain');
        $this->consumeAllowedAttempt($context);

        $before = $this->countUserAttempts($context['quiz'], $context['student']);
        $payload = $this->postAttempt($context, '');

        $this->assertFalse($payload['success']);
        $this->assertSame('legacy_required', $payload['status']);
        $this->assertSame($before, $this->countUserAttempts($context['quiz'], $context['student']));
    }

    public function testForgedViewIdDoesNotBypassTheLimit(): void
    {
        $context = $this->createExerciseWithOneAttemptLimit('viewid');
        $this->consumeAllowedAttempt($context);

        $before = $this->countUserAttempts($context['quiz'], $context['student']);
        $payload = $this->postAttempt($context, '&learnpath_item_view_id=7');

        $this->assertFalse($payload['success'], 'A forged learnpath_item_view_id must not open a new attempt counter.');
        $this->assertSame('legacy_required', $payload['status']);
        $this->assertSame(
            $before,
            $this->countUserAttempts($context['quiz'], $context['student']),
            'No attempt row may be created for the rejected request.'
        );
    }

    public function testForgedFullTripleDoesNotBypassTheLimit(): void
    {
        $context = $this->createExerciseWithOneAttemptLimit('triple');
        $this->consumeAllowedAttempt($context);

        $before = $this->countUserAttempts($context['quiz'], $context['student']);
        $payload = $this->postAttempt(
            $context,
            '&learnpath_id=999999&learnpath_item_id=999999&learnpath_item_view_id=999999'
        );

        $this->assertFalse($payload['success'], 'A fully forged learning-path triple must not open a new attempt counter.');
        $this->assertSame('legacy_required', $payload['status']);
        $this->assertSame($before, $this->countUserAttempts($context['quiz'], $context['student']));
    }

    public function testLegitimateLearnpathAttemptKeepsItsOwnCounter(): void
    {
        $context = $this->createExerciseWithOneAttemptLimit('lesson');
        $this->consumeAllowedAttempt($context);

        // A real lesson attempt carries the three ids of a published quiz item opened by this user,
        // so it must still be allowed: it counts against its own (lpId, lpItemId, lpItemViewId)
        // counter, separate from the exhausted exercise-tool one.
        [$lpId, $lpItemId, $lpItemViewId] = $this->createLearnpathQuizContext($context);

        $before = $this->countUserAttempts($context['quiz'], $context['student']);
        $payload = $this->postAttempt(
            $context,
            \sprintf('&learnpath_id=%d&learnpath_item_id=%d&learnpath_item_view_id=%d', $lpId, $lpItemId, $lpItemViewId)
        );

        $this->assertTrue($payload['success'], 'A legitimate in-lesson attempt must still be allowed.');
        $this->assertSame('incomplete', $payload['status']);
        $this->assertSame(
            $before + 1,
            $this->countUserAttempts($context['quiz'], $context['student']),
            'The legitimate in-lesson attempt is created.'
        );

        $created = $this->getEntityManager()->getRepository(TrackEExercise::class)->find((int) $payload['attemptId']);
        $this->assertInstanceOf(TrackEExercise::class, $created);
        $this->assertSame($lpId, (int) $created->getOrigLpId());
        $this->assertSame($lpItemId, (int) $created->getOrigLpItemId());
        $this->assertSame($lpItemViewId, (int) $created->getOrigLpItemViewId(), 'The row is sealed with the validated view id.');
    }

    /**
     * @param array{course: Course, quiz: CQuiz, student: User} $context
     *
     * @return array<string, mixed>
     */
    private function postAttempt(array $context, string $extraQuery): array
    {
        $url = '/api/exercise/runtime/'.$context['quiz']->getIid().'/attempt?cid='.$context['course']->getId().'&sid=0'.$extraQuery;

        $response = $this
            ->createClientWithCredentials($this->getUserTokenFromUser($context['student']))
            ->request('POST', $url, ['json' => []])
        ;

        // The processor always returns the representation (a rejection carries success=false in the
        // body), so API Platform answers 201 whether or not a new attempt was created.
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return $response->toArray();
    }

    /**
     * @param array{course: Course, quiz: CQuiz, student: User} $context
     */
    private function consumeAllowedAttempt(array $context): void
    {
        $token = $this->getUserTokenFromUser($context['student']);
        $baseUrl = '/api/exercise/runtime/'.$context['quiz']->getIid();
        $query = '?cid='.$context['course']->getId().'&sid=0';

        $start = $this
            ->createClientWithCredentials($token)
            ->request('POST', $baseUrl.'/attempt'.$query, ['json' => []])
            ->toArray()
        ;
        $this->assertTrue($start['success'], 'The first, allowed attempt must start.');

        $this
            ->createClientWithCredentials($token)
            ->request('POST', $baseUrl.'/attempt/'.$start['attemptId'].'/finish'.$query, ['json' => []])
        ;
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    private function countUserAttempts(CQuiz $quiz, User $student): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(attempt.exeId)')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.quiz) = :quizId')
            ->andWhere('IDENTITY(attempt.user) = :userId')
            ->setParameter('quizId', (int) $quiz->getIid())
            ->setParameter('userId', (int) $student->getId())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return array{course: Course, quiz: CQuiz, student: User}
     */
    private function createExerciseWithOneAttemptLimit(string $suffix): array
    {
        $em = $this->getEntityManager();

        $course = $this->createCourse('Attempt limit '.$suffix);
        $student = $this->createUser('attempt_limit_student_'.$suffix);
        $course->addUserAsStudent($student);
        $em->flush();

        $quiz = (new CQuiz())
            ->setTitle('Attempt limit quiz '.$suffix)
            ->setMaxAttempt(1)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        $question = (new CQuizQuestion())
            ->setQuestion('What is 2 + 2?')
            ->setType(1)
            ->setLevel(1)
            ->setPosition(1)
            ->setExtra('')
            ->setPonderation(10.0)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizQuestionRepository::class)->create($question);

        $position = 1;
        foreach ([['4', 1, 10.0], ['5', 0, 0.0]] as [$answer, $correct, $weight]) {
            $em->persist(
                (new CQuizAnswer())
                    ->setQuestion($question)
                    ->setAnswer($answer)
                    ->setCorrect($correct)
                    ->setComment('')
                    ->setPonderation($weight)
                    ->setPosition($position)
            );
            ++$position;
        }

        $em->persist(
            (new CQuizRelQuestion())
                ->setQuiz($quiz)
                ->setQuestion($question)
                ->setQuestionOrder(1)
        );
        $em->flush();

        return ['course' => $course, 'quiz' => $quiz, 'student' => $student];
    }

    /**
     * Build a real learning path that publishes the exercise as a quiz item, plus the student's own
     * view of it, so the validated lookup accepts the triple.
     *
     * @param array{course: Course, quiz: CQuiz, student: User} $context
     *
     * @return array{0: int, 1: int, 2: int} the (lpId, lpItemId, lpItemViewId) triple
     */
    private function createLearnpathQuizContext(array $context): array
    {
        $em = $this->getEntityManager();

        // The HTTP calls that spent the allowed attempt rebooted the kernel, so the entities built
        // earlier are detached from the current manager. Reload them before persisting new links.
        $course = $em->find(Course::class, (int) $context['course']->getId());
        $student = $em->find(User::class, (int) $context['student']->getId());

        $lp = (new CLp())
            ->setTitle('Lesson with the quiz')
            ->setParent($course)
            ->setCreator($this->getAdmin())
            ->setLpType(CLp::LP_TYPE)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        self::getContainer()->get(CLpRepository::class)->createLp($lp);

        $itemRepo = self::getContainer()->get(CLpItemRepository::class);
        $root = $itemRepo->getRootItem((int) $lp->getIid());

        $item = (new CLpItem())
            ->setLp($lp)
            ->setParent($root)
            ->setItemType('quiz')
            ->setTitle('Quiz step')
            ->setPath((string) $context['quiz']->getIid())
            ->setRef((string) $context['quiz']->getIid())
            ->setDisplayOrder(1)
            ->setPrerequisite('')
        ;
        $itemRepo->create($item);

        $lpView = (new CLpView())
            ->setUser($student)
            ->setLp($lp)
            ->setCourse($course)
            ->setViewCount(1)
            ->setLastItem(0)
        ;
        $em->persist($lpView);

        $itemView = (new CLpItemView())
            ->setItem($item)
            ->setView($lpView)
            ->setViewCount(1)
            ->setStartTime(0)
            ->setTotalTime(0)
            ->setScore(0.0)
        ;
        $em->persist($itemView);
        $em->flush();

        return [(int) $lp->getIid(), (int) $item->getIid(), (int) $itemView->getIid()];
    }
}
