<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The button of the exercise start page tells the learner whether they resume an attempt they left
 * unfinished, or start a new one. Finished attempts are not resumed: after them, a new attempt
 * starts, as in Chamilo 1.11.
 */
final class ExerciseOverviewStartButtonTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testLearnerStartsANewAttemptAfterFinishedOnes(): void
    {
        $ctx = $this->bootstrapScenario('finished');
        $this->createAttempt($ctx, 'completed');

        $this->assertSame('Start test', $this->getStartButtonLabel($ctx));
    }

    public function testLearnerProceedsWithTheUnfinishedAttempt(): void
    {
        $ctx = $this->bootstrapScenario('unfinished');
        $this->assertSame('Start test', $this->getStartButtonLabel($ctx));

        $this->createAttempt($ctx, 'incomplete');
        $this->assertSame('Proceed with the test', $this->getStartButtonLabel($ctx));
    }

    /**
     * @param array{course: Course, quiz: CQuiz, learner: User} $ctx
     */
    private function getStartButtonLabel(array $ctx): string
    {
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['learner']));

        return $client->request(
            'GET',
            '/api/exercise/overview/'.$ctx['quiz']->getIid().'?cid='.$ctx['course']->getId().'&sid=0',
        )->toArray()['startButtonLabel'];
    }

    /**
     * @param array{course: Course, quiz: CQuiz, learner: User} $ctx
     */
    private function createAttempt(array $ctx, string $status): void
    {
        $em = $this->getEntityManager();
        // A request to the API in between leaves the entities of the scenario detached
        $attempt = (new TrackEExercise())
            ->setUser($em->find(User::class, $ctx['learner']->getId()))
            ->setCourse($em->find(Course::class, $ctx['course']->getId()))
            ->setQuiz($em->find(CQuiz::class, $ctx['quiz']->getIid()))
            ->setStatus($status)
            ->setMaxScore(10)
            ->setUserIp('127.0.0.1')
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
        ;
        $em->persist($attempt);
        $em->flush();
    }

    /**
     * A course with a learner and a published exercise.
     *
     * @return array{course: Course, quiz: CQuiz, learner: User}
     */
    private function bootstrapScenario(string $suffix): array
    {
        $course = $this->createCourse('Exercise start button '.$suffix);
        $learner = $this->createUser('exercise_start_learner_'.$suffix);
        $course->addUserAsStudent($learner);
        $this->getEntityManager()->flush();

        $quiz = (new CQuiz())
            ->setTitle('Exercise start button '.$suffix)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        return ['course' => $course, 'quiz' => $quiz, 'learner' => $learner];
    }
}
