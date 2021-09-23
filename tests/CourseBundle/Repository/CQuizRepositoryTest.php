<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

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
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('exercise', (string) $item);
        $this->assertSame(1, $repo->count([]));

        $this->assertSame(0, $item->getQuestionsCategories()->count());

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
            ->setTitle('exercise')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $repo->create($exercise);

        $qb = $repo->findAllByCourse($course);
        $this->assertSame(1, \count($qb->getQuery()->getResult()));

        $found = $repo->findCourseResourceByTitle('exercise', $course->getResourceNode(), $course);
        $this->assertNotNull($found);

        $foundList = $repo->findCourseResourcesByTitle('exercise', $course->getResourceNode(), $course);
        $this->assertSame(1, \count($foundList));

        $found = $repo->getResourceByCreatorFromTitle('exercise', $teacher, $course->getResourceNode());
        $this->assertNotNull($found);

        $items = $repo->getResourcesByCourseOnly($course, $course->getResourceNode())->getQuery()->getResult();
        $this->assertTrue(\count($items) > 0);

        $qb = $repo->getResourcesByCreator($teacher, $course->getResourceNode());
        $this->assertSame(1, \count($qb->getQuery()->getResult()));

        $qb = $repo->getResourcesByCourseLinkedToUser($teacher, $course);
        $this->assertSame(1, \count($qb->getQuery()->getResult()));

        $qb = $repo->getResourcesByLinkedUser($teacher, $course->getResourceNode());
        $this->assertSame(0, \count($qb->getQuery()->getResult()));

        $node = $repo->getResourceFromResourceNode($exercise->getResourceNode()->getId());
        $this->assertNotNull($node);
    }
}
