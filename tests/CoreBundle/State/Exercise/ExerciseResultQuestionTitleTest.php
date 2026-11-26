<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Hide the question title" must also apply to the result page: the title often gives the
 * answer away. Only a teacher correcting someone else's attempt keeps it, to know which
 * question is corrected; in their own preview, the teacher sees the page as the learner does.
 */
final class ExerciseResultQuestionTitleTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const UNIQUE_ANSWER = 1;

    public function testTheLearnerDoesNotSeeTheHiddenTitle(): void
    {
        $ctx = $this->bootstrapScenario(true);
        $attemptId = $this->createAttempt($ctx, $ctx['learner']);

        $this->assertFalse($this->getVisibility($ctx, $ctx['learner'], $attemptId)['showQuestionTitle']);
    }

    public function testTheTeacherSeesTheHiddenTitleOnlyWhenCorrectingSomeoneElse(): void
    {
        $ctx = $this->bootstrapScenario(true);

        $learnerAttemptId = $this->createAttempt($ctx, $ctx['learner']);
        $this->assertTrue($this->getVisibility($ctx, $ctx['teacher'], $learnerAttemptId)['showQuestionTitle']);

        $previewAttemptId = $this->createAttempt($ctx, $ctx['teacher']);
        $this->assertFalse($this->getVisibility($ctx, $ctx['teacher'], $previewAttemptId)['showQuestionTitle']);
    }

    public function testTheTitleIsShownWhenTheOptionIsOff(): void
    {
        $ctx = $this->bootstrapScenario(false);
        $attemptId = $this->createAttempt($ctx, $ctx['learner']);

        $this->assertTrue($this->getVisibility($ctx, $ctx['learner'], $attemptId)['showQuestionTitle']);
    }

    /**
     * @param array{course: Course, quiz: CQuiz, question: CQuizQuestion, learner: User, teacher: User} $ctx
     *
     * @return array<string, mixed>
     */
    private function getVisibility(array $ctx, User $user, int $attemptId): array
    {
        $response = $this
            ->createClientWithCredentials($this->getUserTokenFromUser($user))
            ->request(
                'GET',
                '/api/exercise/runtime/'.$ctx['quiz']->getIid().'/attempt/'.$attemptId.'/result?cid='.$ctx['course']->getId().'&sid=0',
            )
        ;
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        return $response->toArray()['visibility'];
    }

    /**
     * @param array{course: Course, quiz: CQuiz, question: CQuizQuestion, learner: User, teacher: User} $ctx
     */
    private function createAttempt(array $ctx, User $user): int
    {
        $em = $this->getEntityManager();
        // A request to the API in between leaves the entities of the scenario detached
        $attempt = (new TrackEExercise())
            ->setUser($em->find(User::class, $user->getId()))
            ->setCourse($em->find(Course::class, $ctx['course']->getId()))
            ->setQuiz($em->find(CQuiz::class, $ctx['quiz']->getIid()))
            ->setStatus('completed')
            ->setScore(0)
            ->setMaxScore(1)
            ->setDataTracking((string) $ctx['question']->getIid())
            ->setUserIp('127.0.0.1')
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
        ;
        $em->persist($attempt);
        $em->flush();

        return (int) $attempt->getExeId();
    }

    /**
     * A course with a learner, a teacher and a test of one question.
     *
     * @return array{course: Course, quiz: CQuiz, question: CQuizQuestion, learner: User, teacher: User}
     */
    private function bootstrapScenario(bool $hideQuestionTitle): array
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('Exercise result question title');
        $learner = $this->createUser('exercise_title_learner');
        $teacher = $this->createUser('exercise_title_teacher');
        $course->addUserAsStudent($learner);
        $course->addUserAsTeacher($teacher);
        $em->flush();

        $quiz = (new CQuiz())
            ->setTitle('Exercise result question title')
            ->setHideQuestionTitle($hideQuestionTitle)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        $question = (new CQuizQuestion())
            ->setQuestion('What is the capital of France?')
            ->setType(self::UNIQUE_ANSWER)
            ->setLevel(1)
            ->setPosition(1)
            ->setPonderation(1.0)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizQuestionRepository::class)->create($question);

        $em->persist(
            (new CQuizRelQuestion())
                ->setQuiz($quiz)
                ->setQuestion($question)
                ->setQuestionOrder(1)
        );
        $em->flush();

        return [
            'course' => $course,
            'quiz' => $quiz,
            'question' => $question,
            'learner' => $learner,
            'teacher' => $teacher,
        ];
    }
}
