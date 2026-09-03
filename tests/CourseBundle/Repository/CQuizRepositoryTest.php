<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CQuizRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testUpdateNodeForResource(): void
    {
        $repo = self::getContainer()->get(CQuizRepository::class);

        $quizCountBefore = $repo->count([]);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CQuiz())
            ->setTitle('exercise')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $repo->create($item);

        $this->assertSame($quizCountBefore + 1, $repo->count([]));

        $item->setTitle('exercise modified');
        $repo->updateNodeForResource($item);

        /** @var CQuiz $newExercise */
        $newExercise = $repo->find($item->getIid());
        $this->assertSame('exercise modified', $newExercise->getTitle());
        $this->assertSame($quizCountBefore + 1, $repo->count([]));
    }

    public function testFindAllByCourse(): void
    {
        $em = $this->getEntityManager();

        $repo = self::getContainer()->get(CQuizRepository::class);
        $request_stack = $this->getMockedRequestStack([
            'session' => ['studentview' => 1],
        ]);
        $repo->setRequestStack($request_stack);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $category = (new CQuizCategory())
            ->setTitle('cat')
            ->setDescription('desc')
            ->setCourse($course)
            ->setParent($course)
            ->setCreator($teacher)
            ->setPosition(1)
        ;
        $this->assertHasNoEntityViolations($category);
        $em->persist($category);
        $em->flush();

        $exercise = (new CQuiz())
            ->setTitle('exercise 1')
            ->setParent($course)
            ->setQuizCategory($category)
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

        $qb = $repo->findAllByCourse($course, null, 'exercise 1');
        $this->assertCount(2, $qb->getQuery()->getResult());

        $qb = $repo->findAllByCourse($course, null, null, false, true, $category->getId());
        $this->assertCount(1, $qb->getQuery()->getResult());

        $found = $repo->findCourseResourceByTitle('exercise 1', $course->getResourceNode(), $course);
        $this->assertNotNull($found);

        $found = $repo->findCourseResourceBySlug('exercise-1', $course->getResourceNode(), $course);
        $this->assertNotNull($found);

        $found = $repo->findCourseResourceBySlugIgnoreVisibility('exercise-1', $course->getResourceNode(), $course);
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

        // FIXME Re-add: Why the course exercise is visible?
        // $this->assertFalse($exercise->isVisible($course));
        $this->assertTrue($exercise->isVisible($course, $session));

        // An unpublished exercise still belongs to the course, so it only drops out
        // once the caller asks for the visible ones.
        $draft = (new CQuiz())
            ->setTitle('exercise draft')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_DRAFT)
        ;
        $repo->create($draft);

        $qb = $repo->findAllByCourse($course, null, null, false, false);
        $this->assertCount(3, $qb->getQuery()->getResult());

        $qb = $repo->findAllByCourse($course, null, null, true, false);
        $this->assertCount(2, $qb->getQuery()->getResult());
    }
}
