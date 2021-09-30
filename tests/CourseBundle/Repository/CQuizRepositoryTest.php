<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class CQuizRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CQuizRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CQuiz())
            ->setTitle('exercise')
            ->setDescription('desc')
            ->setActive(1)
            ->setType(1)
            ->setAutoLaunch(false)
            ->setFeedbackType(1)
            ->setMaxAttempt(10)
            ->setShowPreviousButton(true)
            ->setResultsDisabled(0)
            ->setReviewAnswers(0)
            ->setPropagateNeg(0)
            ->setPageResultConfiguration([])
            ->setHideQuestionTitle(true)
            ->setRandomAnswers(false)
            ->setStartTime(new DateTime())
            ->setExpiredTime(100)
            ->setSaveCorrectAnswers(1)
            ->setDisplayCategoryName(1)
            ->setPassPercentage(1)
            ->setAccessCondition('')
            ->setRandom(0)
            ->setTextWhenFinished('text when finished')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('exercise', (string) $item);
        $this->assertSame(1, $repo->count([]));

        $this->assertSame(0, $item->getQuestionsCategories()->count());
        $this->assertSame(0, $item->getMaxScore());

        $repo->updateNodeForResource($item);

        $link = $repo->getLink($item, $this->getContainer()->get('router'));
        $this->assertSame('/main/exercise/overview.php?exerciseId='.$item->getIid(), $link);
    }

    public function testUpdateNodeForResource(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(CQuizRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CQuiz())
            ->setTitle('exercise')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $repo->create($item);

        $this->assertSame(1, $repo->count([]));

        $item->setTitle('exercise modified');
        $repo->updateNodeForResource($item);

        /** @var CQuiz $newExercise */
        $newExercise = $repo->find($item->getIid());
        $this->assertSame('exercise modified', $newExercise->getTitle());
    }

    public function testFindAllByCourse(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(CQuizRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $exercise = (new CQuiz())
            ->setTitle('exercise 1')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $repo->create($exercise);

        $exercise = (new CQuiz())
            ->setTitle('exercise 1')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $repo->create($exercise);

        $this->assertTrue($exercise->isVisible($course));

        $qb = $repo->findAllByCourse($course);
        $this->assertCount(2, $qb->getQuery()->getResult());

        $found = $repo->findCourseResourceByTitle('exercise 1', $course->getResourceNode(), $course);
        $this->assertNotNull($found);

        $found = $repo->findCourseResourceBySlug('exercise-1', $course->getResourceNode(), $course);
        $this->assertNotNull($found);

        $found = $repo->findCourseResourceBySlug('exercise-1', $course->getResourceNode(), $course);
        $this->assertNotNull($found);

        $found = $repo->getResourceByCreatorFromTitle('exercise 1', $teacher, $course->getResourceNode());
        $this->assertNotNull($found);

        $node = $repo->getResourceFromResourceNode($exercise->getResourceNode()->getId());
        $this->assertNotNull($node);

        // Find resources.
        $foundList = $repo->findCourseResourcesByTitle('exercise 1', $course->getResourceNode(), $course);
        $this->assertCount(2, $foundList);

        $items = $repo->getResourcesByCourseOnly($course, $course->getResourceNode())->getQuery()->getResult();
        $this->assertCount(2, $items);

        $qb = $repo->getResourcesByCreator($teacher, $course->getResourceNode());
        $this->assertCount(2, $qb->getQuery()->getResult());

        $qb = $repo->getResourcesByCourseLinkedToUser($teacher, $course);
        $this->assertCount(2, $qb->getQuery()->getResult());

        $qb = $repo->getResourcesByLinkedUser($teacher, $course->getResourceNode());
        $this->assertCount(0, $qb->getQuery()->getResult());

        $session = $this->createSession('session 1');

        $exercise = (new CQuiz())
            ->setTitle('exercise 2')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course, $session)
        ;
        $repo->create($exercise);

        $items = $repo->getResourcesByCourseOnly($course, $course->getResourceNode())->getQuery()->getResult();
        $this->assertCount(2, $items);

        $items = $repo->getResourcesByCourse($course)->getQuery()->getResult();
        $this->assertCount(2, $items);

        $items = $repo->getResourcesByCourse($course, $session)->getQuery()->getResult();
        $this->assertCount(3, $items);

        $this->assertFalse($exercise->isVisible($course));
        $this->assertTrue($exercise->isVisible($course, $session));
    }
}
