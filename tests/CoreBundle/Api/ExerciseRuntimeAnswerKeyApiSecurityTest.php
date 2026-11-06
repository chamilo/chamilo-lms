<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression test for the answer-key disclosure on
 * GET /api/exercise/runtime/{exerciseId}.
 *
 * Sequence ordering questions (type 18) store the expected position of every
 * item in c_quiz_answer.correct, which is the very value the scoring service
 * compares the learner's order against. The runtime used to publish it as
 * `targetPosition`, together with the per-item score, to anyone allowed to
 * take the test, so a learner could read the solution in the network tab
 * before answering.
 *
 * The exercise player only needs the item id and its label, so both keys must
 * now reach course managers only.
 */
final class ExerciseRuntimeAnswerKeyApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testLearnerDoesNotReceiveTheDraggableAnswerKey(): void
    {
        $context = $this->createOrderingExercise('learner');

        $response = $this
            ->createClientWithCredentials($this->getUserTokenFromUser($context['student']))
            ->request('GET', $this->runtimeUrl($context['course'], $context['quiz']))
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $response->toArray();
        $this->assertFalse($payload['canManage']);

        $items = $payload['questions'][0]['draggable']['items'];
        $this->assertCount(3, $items);

        foreach ($items as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('answer', $item);
            $this->assertArrayNotHasKey('targetPosition', $item);
            $this->assertArrayNotHasKey('score', $item);
        }
    }

    public function testTeacherStillReceivesTheDraggableAnswerKey(): void
    {
        $context = $this->createOrderingExercise('teacher');

        $response = $this
            ->createClientWithCredentials($this->getUserTokenFromUser($context['teacher']))
            ->request('GET', $this->runtimeUrl($context['course'], $context['quiz']))
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $response->toArray();
        $this->assertTrue($payload['canManage']);

        $items = $payload['questions'][0]['draggable']['items'];
        $this->assertCount(3, $items);

        $expectedPositions = [];
        foreach ($items as $item) {
            $this->assertSame(10.0, (float) $item['score']);
            $expectedPositions[$item['answer']] = $item['targetPosition'];
        }

        $this->assertSame(['Item RED' => 3, 'Item GREEN' => 1, 'Item BLUE' => 2], $expectedPositions);
    }

    private function runtimeUrl(Course $course, CQuiz $quiz): string
    {
        return '/api/exercise/runtime/'.$quiz->getIid().'?cid='.$course->getId().'&sid=0';
    }

    /**
     * @return array{course: Course, quiz: CQuiz, teacher: User, student: User}
     */
    private function createOrderingExercise(string $suffix): array
    {
        $em = $this->getEntityManager();

        $course = $this->createCourse('Sequence ordering answer key '.$suffix);
        $teacher = $this->createUser('ordering_teacher_'.$suffix, '', '', 'ROLE_TEACHER');
        $student = $this->createUser('ordering_student_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $em->flush();

        $quiz = (new CQuiz())
            ->setTitle('Ordering answer key '.$suffix)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        $question = (new CQuizQuestion())
            ->setQuestion('Put the three items in the right order')
            ->setType(18)
            ->setLevel(1)
            ->setPosition(1)
            ->setExtra('v')
            ->setPonderation(30.0)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizQuestionRepository::class)->create($question);

        // The question editor writes one placeholder answer per item (correct = 0)
        // and then the items themselves, whose correct field holds the expected position.
        $items = [['Item RED', 3], ['Item GREEN', 1], ['Item BLUE', 2]];
        $position = 1;

        foreach ($items as $index => $_item) {
            $em->persist(
                (new CQuizAnswer())
                    ->setQuestion($question)
                    ->setAnswer((string) ($index + 1))
                    ->setCorrect(0)
                    ->setComment('')
                    ->setPonderation(0.0)
                    ->setPosition($position)
            );
            ++$position;
        }

        foreach ($items as [$label, $targetPosition]) {
            $em->persist(
                (new CQuizAnswer())
                    ->setQuestion($question)
                    ->setAnswer($label)
                    ->setCorrect($targetPosition)
                    ->setComment('')
                    ->setPonderation(10.0)
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

        return ['course' => $course, 'quiz' => $quiz, 'teacher' => $teacher, 'student' => $student];
    }
}
