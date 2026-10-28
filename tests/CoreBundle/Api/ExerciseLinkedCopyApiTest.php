<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

final class ExerciseLinkedCopyApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private function newQuiz(Course $course, string $title, int $feedbackType = 0): CQuiz
    {
        $quiz = (new CQuiz())
            ->setTitle($title)
            ->setFeedbackType($feedbackType)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        return $quiz;
    }

    private function addQuestion(Course $course, CQuiz $quiz, string $title, int $order, ?string $destination = null): CQuizQuestion
    {
        $question = (new CQuizQuestion())
            ->setQuestion($title)
            ->setType(1)
            ->setLevel(1)
            ->setPosition($order)
            ->setPonderation(10.0)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizQuestionRepository::class)->create($question);

        $relation = (new CQuizRelQuestion())
            ->setQuiz($quiz)
            ->setQuestion($question)
            ->setQuestionOrder($order)
            ->setDestination($destination)
        ;
        $this->getEntityManager()->persist($relation);
        $this->getEntityManager()->flush();

        return $question;
    }

    private function act(Course $course, CQuiz $quiz, string $action, ?string $token = null): void
    {
        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/exercise/list/action?cid='.$course->getId().'&sid=0',
            ['json' => ['exerciseId' => (int) $quiz->getIid(), 'action' => $action]],
        );
    }

    /**
     * @return list<CQuizRelQuestion>
     */
    private function relations(CQuiz $quiz): array
    {
        return $this->getEntityManager()->getRepository(CQuizRelQuestion::class)->findBy(
            ['quiz' => $quiz],
            ['questionOrder' => 'ASC'],
        );
    }

    private function copiedQuiz(string $title): CQuiz
    {
        $this->getEntityManager()->clear();
        $result = $this->getEntityManager()->getRepository(CQuiz::class)->findOneBy(['title' => $title]);
        self::assertInstanceOf(CQuiz::class, $result);

        return $result;
    }

    public function testLinkedCopySharesOriginalQuestionsAndPreservesOrderAndDestination(): void
    {
        $course = $this->createCourse('GH 2076 linked relations');
        $source = $this->newQuiz($course, 'GH 2076 source');
        $source->setRandom(1);
        $this->getEntityManager()->flush();
        $first = $this->addQuestion($course, $source, 'First question', 1, 'next');
        $second = $this->addQuestion($course, $source, 'Second question', 2);
        $originalCount = $this->getEntityManager()->getRepository(CQuizQuestion::class)->count([]);

        $this->act($course, $source, 'copy_linked');
        $this->assertResponseIsSuccessful();

        $linked = $this->copiedQuiz('GH 2076 source - Linked');
        self::assertNotSame($source->getIid(), $linked->getIid());
        self::assertSame(1, (int) $linked->getRandom());
        self::assertSame($originalCount, $this->getEntityManager()->getRepository(CQuizQuestion::class)->count([]));

        $relations = $this->relations($linked);
        self::assertCount(2, $relations);
        self::assertSame([(int) $first->getIid(), (int) $second->getIid()], array_map(
            static fn (CQuizRelQuestion $relation): int => (int) $relation->getQuestion()->getIid(),
            $relations,
        ));
        self::assertSame([1, 2], array_map(
            static fn (CQuizRelQuestion $relation): int => (int) $relation->getQuestionOrder(),
            $relations,
        ));
        self::assertSame('next', $relations[0]->getDestination());
        self::assertCount(2, $this->relations($this->getEntityManager()->find(CQuiz::class, $source->getIid())));
    }

    public function testExistingCopyStillCreatesIndependentQuestions(): void
    {
        $course = $this->createCourse('GH 2076 traditional copy');
        $source = $this->newQuiz($course, 'GH 2076 original');
        $question = $this->addQuestion($course, $source, 'Source question', 1);

        $this->act($course, $source, 'copy');
        $this->assertResponseIsSuccessful();

        $copy = $this->copiedQuiz('GH 2076 original - Copy');
        $relations = $this->relations($copy);
        self::assertCount(1, $relations);
        self::assertNotSame($question->getIid(), $relations[0]->getQuestion()->getIid());
    }

    public function testDirectFeedbackExerciseRejectsLinkedCopy(): void
    {
        $course = $this->createCourse('GH 2076 direct feedback');
        $source = $this->newQuiz($course, 'GH 2076 adaptive', 1);
        $this->addQuestion($course, $source, 'Adaptive question', 1);

        $this->act($course, $source, 'copy_linked');
        $this->assertResponseStatusCodeSame(400);

        self::assertNull($this->getEntityManager()->getRepository(CQuiz::class)->findOneBy([
            'title' => 'GH 2076 adaptive - Linked',
        ]));
    }

    public function testStudentCannotCreateLinkedCopy(): void
    {
        $course = $this->createCourse('GH 2076 student denied');
        $student = $this->createUser('student_linked_copy_2076');
        $course->addUserAsStudent($student);
        $this->getEntityManager()->flush();
        $source = $this->newQuiz($course, 'GH 2076 restricted');

        $this->act($course, $source, 'copy_linked', $this->getUserTokenFromUser($student));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCourseTeacherCanCreateLinkedCopy(): void
    {
        $course = $this->createCourse('GH 2076 teacher');
        $teacher = $this->createUser('teacher_linked_copy_2076');
        $course->addUserAsTeacher($teacher);
        self::getContainer()->get(CourseRepository::class)->update($course);
        $source = $this->newQuiz($course, 'GH 2076 teacher source');
        $question = $this->addQuestion($course, $source, 'Teacher shared question', 1);

        $this->act($course, $source, 'copy_linked', $this->getUserTokenFromUser($teacher));
        $this->assertResponseIsSuccessful();

        $linked = $this->copiedQuiz('GH 2076 teacher source - Linked');
        self::assertSame($question->getIid(), $this->relations($linked)[0]->getQuestion()->getIid());
    }

    public function testCannotLinkExerciseFromAnotherCourse(): void
    {
        $course = $this->createCourse('GH 2076 current course');
        $foreignCourse = $this->createCourse('GH 2076 foreign course');
        $foreign = $this->newQuiz($foreignCourse, 'GH 2076 foreign');

        $this->act($course, $foreign, 'copy_linked');
        $this->assertResponseStatusCodeSame(404);

        self::assertNull($this->getEntityManager()->getRepository(CQuiz::class)->findOneBy([
            'title' => 'GH 2076 foreign - Linked',
        ]));
    }

    public function testRemovingAQuestionFromLinkedExercisePreservesOriginal(): void
    {
        $course = $this->createCourse('GH 2076 unlink question');
        $source = $this->newQuiz($course, 'GH 2076 unlink source');
        $question = $this->addQuestion($course, $source, 'Remain in original', 1);
        $this->act($course, $source, 'copy_linked');
        $this->assertResponseIsSuccessful();
        $linked = $this->copiedQuiz('GH 2076 unlink source - Linked');

        $this->createClientWithCredentials()->request(
            'POST',
            '/api/exercise/questions/'.$linked->getIid().'/action?cid='.$course->getId().'&sid=0',
            ['json' => ['action' => 'delete', 'questionId' => (int) $question->getIid()]],
        );
        $this->assertResponseIsSuccessful();
        $this->getEntityManager()->clear();
        $em = $this->getEntityManager();
        self::assertCount(0, $this->relations($em->find(CQuiz::class, $linked->getIid())));
        self::assertCount(1, $this->relations($em->find(CQuiz::class, $source->getIid())));
        self::assertNotNull($em->find(CQuizQuestion::class, $question->getIid()));
    }
}
